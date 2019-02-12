<?php
require_once __DIR__ . '/../../CRM/Gidipirus/BaseTest.php';

/**
 * @group e2e
 */
class api_v3_StatusTest extends CRM_Gidipirus_BaseTest {

  /**
   * Check if empty contact is ready to forgetMe
   */
  public function testEmptyContactIsReady() {
    $params = [
      'sequential' => 1,
      'contact_id' => self::$emptyContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::READY_VALUE, $result['values'][0]['status']);
    $params = [
      'sequential' => 1,
      'contact_id' => self::$fullContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::READY_VALUE, $result['values'][0]['status']);
  }

  /**
   * Check if donor contact has blocked status
   */
  public function testDonorContactIsReady() {
    $params = [
      'sequential' => 1,
      'contact_id' => self::$donorContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::READY_VALUE, $result['values'][0]['status']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testInProgress() {
    self::emptyContact();
    $requestedDate = new DateTime();
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::PERSONAL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 0,
    ];
    $result1 = $this->callAPISuccess('Gidipirus', 'register', $params);
    $params = [
      'sequential' => 1,
      'contact_id' => self::$emptyContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::IN_PROGRESS_VALUE, $result['values'][0]['status']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testObsoleteNow() {
    self::emptyContact();
    $requestedDate = new DateTime();
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::PERSONAL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 0,
    ];
    $result1 = $this->callAPISuccess('Gidipirus', 'force', $params);
    $params = [
      'sequential' => 1,
      'contact_id' => self::$emptyContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::OBSOLETE_VALUE, $result['values'][0]['status']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testObsoleteWaitSecond() {
    self::emptyContact();
    $requestedDate = new DateTime();
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::PERSONAL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 0,
    ];
    $result1 = $this->callAPISuccess('Gidipirus', 'force', $params);
    sleep(1);
    $params = [
      'sequential' => 1,
      'contact_id' => self::$emptyContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::OBSOLETE_VALUE, $result['values'][0]['status']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testCompleted() {
    self::emptyContact();
    $requestedDate = new DateTime();
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::PERSONAL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 0,
    ];
    $result1 = $this->callAPISuccess('Gidipirus', 'force', $params);
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'dry_run' => 0,
    ];
    $result2 = $this->callAPISuccess('Gidipirus', 'forg3t', $params);
    $params = [
      'sequential' => 1,
      'contact_id' => self::$emptyContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::COMPLETED_VALUE, $result['values'][0]['status']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testTooManyRequests() {
    self::emptyContact();
    $requestedDate = new DateTime();
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::PERSONAL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 0,
    ];
    $result1 = $this->callAPISuccess('Gidipirus', 'register', $params);
    $result2 = $this->callAPISuccess('Gidipirus', 'register', $params);

    $params = [
      'sequential' => 1,
      'contact_id' => self::$emptyContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::TOO_MANY_REQUESTS_VALUE, $result['values'][0]['status']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testNotApplicable() {
    foreach (['Organization', 'Household'] as $contactType) {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'contact_type' => $contactType,
        'options' => ['limit' => 1],
      ]);
      if ($result['count']) {
        $params = [
          'sequential' => 1,
          'contact_id' => $result['id'],
        ];
        $result = $this->callAPISuccess('Gidipirus', 'status', $params);
        $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::NOT_APPLICABLE_VALUE, $result['values'][0]['status'], $contactType);
      }
    }
  }

}
