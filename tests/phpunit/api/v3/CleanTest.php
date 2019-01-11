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
    self::inactiveMembersContact();
    $params = [
      'sequential' => 1,
      'dry_run' => 1,
      'channels' => CRM_Gidipirus_Model_RequestChannel::EXPIRED,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    $this->assertGreaterThan(0, $result['count']);
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
      'dry_run' => 1,
      'channels' => CRM_Gidipirus_Model_RequestChannel::EXPIRED,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    foreach ($result['values'] as $value) {
      if ($value['id'] == $firstContactId) {
        $this->assertEquals(0, $value['result']);
        $this->assertEquals('This expired registration request is already not relevant.', $value['error']);
      }
    }
  }

}
