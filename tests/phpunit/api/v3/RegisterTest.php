<?php
require_once __DIR__ . '/../../CRM/Gidipirus/BaseTest.php';

/**
 * @group e2e
 */
class api_v3_RegisterTest extends CRM_Gidipirus_BaseTest {

  /**
   * Check if empty contact is ready to forgetMe
   *
   * @throws \CiviCRM_API3_Exception
   */
  public function testRegisterReady() {
    $scheduledDays = CRM_Gidipirus_Settings::scheduledDays();
    $requestedDate = new DateTime();
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 0,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'register', $params);
    $activityId = $result['values'][0]['activity_id'];
    $this->assertTrue($result['values'][0]['result'] == 1);
    $this->assertGreaterThan(0, $activityId);

    $activity = $this->callAPISuccess('Activity', 'get', [
      'sequential' => 1,
      'id' => $activityId,
      'api.ActivityContact.get' => [
        'sequential' => 1,
        'activity_id' => '$value.id',
      ]
    ]);
    $scheduledId = CRM_Gidipirus_Model_Activity::scheduled();
    $this->assertEquals('RequestedDate:' . $requestedDate->format('Y-m-d'), $activity['values'][0]['subject']);
    $this->assertEquals(CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId(), $activity['values'][0]['activity_type_id']);
    $this->assertEquals($scheduledId, $activity['values'][0]['status_id']);
    $this->assertEquals(CRM_Gidipirus_Model_RequestChannel::EMAIL, $activity['values'][0]['location']);
    $this->assertEquals(2, $activity['values'][0]['api.ActivityContact.get']['count']);
    $this->assertEquals($requestedDate->modify('+' . $scheduledDays . ' days')->format('Y-m-d'), substr($activity['values'][0]['activity_date_time'], 0, 10));
    foreach ($activity['values'][0]['api.ActivityContact.get']['values'] as $item) {
      if ($item['record_type_id'] == 2) {
        $this->assertEquals(self::$loggedUserId, $item['contact_id']);
      }
      if ($item['record_type_id'] == 3) {
        $this->assertEquals(self::$emptyContactId, $item['contact_id']);
      }
    }
  }

  /**
   * Check if empty contact is ready to forgetMe
   */
  public function testRegisterReadyWithInvalidChannel() {
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'channel' => 'non-existing-channel',
      'requested_date' => date('YmdHis'),
      'dry_run' => 0,
    ];
    $result = $this->callAPIFailure('Gidipirus', 'register', $params);
    $this->assertTrue($result['is_error'] == 1);
    $this->assertEquals('Invalid name of channel', $result['error_message']);
  }

  /**
   * Check if it's possible to register for donor contact.
   * "register" api action does not check ForgetMe status.
   */
  public function testRegisterDonorContact() {
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$donorContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => date('YmdHis'),
      'dry_run' => 0,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'register', $params);
    $this->assertTrue($result['values'][0]['result'] == 1);
    $this->assertGreaterThan(0, $result['values'][0]['activity_id']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testTwoContacts() {
    $requestedDate = new DateTime();
    $contacts = [];
    $contacts[] = self::emptyContact();
    $contacts[] = self::emptyContact();
    $params = [
      'sequential' => 1,
      'contact_ids' => $contacts,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 1,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'register', $params);
    $this->assertEquals(2, $result['count']);
    $params = [
      'sequential' => 1,
      'contact_ids' => ['IN' => $contacts],
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 1,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'register', $params);
    $this->assertEquals(2, $result['count']);
  }

}
