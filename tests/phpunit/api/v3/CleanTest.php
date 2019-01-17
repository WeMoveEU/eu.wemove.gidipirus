<?php
require_once __DIR__ . '/../../CRM/Gidipirus/BaseTest.php';

/**
 * @group e2e
 */
class api_v3_CleanTest extends CRM_Gidipirus_BaseTest {

  /**
   */
  public function testNotValidChannel() {
    $channel = 'not-valid-channel';
    $params = [
      'sequential' => 1,
      'dry_run' => 1,
      'channels' => $channel,
    ];
    $result = $this->callAPIFailure('Gidipirus', 'cleanup', $params);
    $this->assertEquals('Invalid name of channel: ' . $channel, $result['error_message']);
  }

  /**
   */
  public function testChannels() {
    $params = [
      'sequential' => 1,
      'dry_run' => 1,
      'channels' => CRM_Gidipirus_Model_RequestChannel::$valid,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    $this->assertGreaterThan(0, $result['count']);
    $params = [
      'sequential' => 1,
      'dry_run' => 1,
      'channels' => ['IN' => CRM_Gidipirus_Model_RequestChannel::$valid],
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    $this->assertGreaterThan(0, $result['count']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testCleanExpired() {
    $contacts[] = self::inactiveMembersContact();
    $contacts[] = self::inactiveMembersContact();
    $result = $this->callAPISuccess('Gidipirus', 'scan', ['dry_run' => 0]);
    $this->assertGreaterThanOrEqual(1, $result['count']);

    $params = [
      'sequential' => 1,
      'dry_run' => 0,
      'channels' => CRM_Gidipirus_Model_RequestChannel::EXPIRED,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    $this->assertGreaterThan(0, $result['count']);
    $cleanedUp = 0;
    foreach ($result['values'] as $value) {
      if (in_array($value['id'], $contacts) && $value['result']) {
        $cleanedUp++;
      }
    }
    $this->assertEquals(2, $cleanedUp, 'Two inactive members with expired requests are cleaned up properly');
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testNotRelevantRequest() {
    $firstContactId = self::inactiveMembersContact();
    $result = $this->callAPISuccess('Gidipirus', 'scan', ['dry_run' => 0]);
    $this->assertGreaterThan(0, $result['count']);

    $result = civicrm_api3('Activity', 'create', [
      'source_contact_id' => $firstContactId,
      'activity_type_id' => "Phone Call",
      'activity_date_time' => date('Y-m-d'),
    ]);

    $params = [
      'sequential' => 1,
      'dry_run' => 0,
      'channels' => CRM_Gidipirus_Model_RequestChannel::EXPIRED,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    foreach ($result['values'] as $value) {
      if ($value['id'] == $firstContactId) {
        $this->assertEquals(1, $value['result']);
        $this->assertGreaterThan(0, $value['activity_id']);
        $this->assertEquals('This expired registration request is already not relevant.', $value['error']);

        $resultA = civicrm_api3('Activity', 'get', [
          'sequential' => 1,
          'id' => $value['activity_id'],
        ]);
        $this->assertEquals(CRM_Gidipirus_Model_Activity::cancelled(), $resultA['values'][0]['status_id']);
        $this->assertEquals(CRM_Gidipirus_Model_RequestChannel::EXPIRED, $resultA['values'][0]['location']);
      }
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testNotRelevantExpiredRequestAndNewProper() {
    $requestedDate = new DateTime();
    $firstContactId = self::inactiveMembersContact();
    $result = $this->callAPISuccess('Gidipirus', 'scan', ['dry_run' => 0]);
    $this->assertGreaterThan(0, $result['count']);

    civicrm_api3('Activity', 'create', [
      'source_contact_id' => $firstContactId,
      'activity_type_id' => "Phone Call",
      'activity_date_time' => date('Y-m-d'),
    ]);

    $params = [
      'sequential' => 1,
      'dry_run' => 0,
      'channels' => CRM_Gidipirus_Model_RequestChannel::EXPIRED,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    $expiredActivityId = 0;
    foreach ($result['values'] as $value) {
      if ($value['id'] == $firstContactId) {
        $expiredActivityId = $value['activity_id'];
        break;
      }
    }

    $result = $this->callAPISuccess('Gidipirus', 'register', [
      'sequential' => 1,
      'contact_ids' => $firstContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => FALSE,
    ]);
    $activityId = $result['values'][0]['activity_id'];
    $this->assertTrue($result['values'][0]['result'] == 1);
    $this->assertGreaterThan(0, $activityId, 'Activity is created');

    $resultEmail = $this->callAPISuccess('Activity', 'get', [
      'sequential' => 1,
      'id' => $activityId,
    ]);
    $this->assertEquals(CRM_Gidipirus_Model_Activity::scheduled(), $resultEmail['values'][0]['status_id']);
    $this->assertEquals(CRM_Gidipirus_Model_RequestChannel::EMAIL, $resultEmail['values'][0]['location']);

    $resultExpired = $this->callAPISuccess('Activity', 'get', [
      'sequential' => 1,
      'id' => $expiredActivityId,
    ]);
    $this->assertEquals(CRM_Gidipirus_Model_Activity::cancelled(), $resultExpired['values'][0]['status_id']);
    $this->assertEquals(CRM_Gidipirus_Model_RequestChannel::EXPIRED, $resultExpired['values'][0]['location']);

  }

}
