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
  public function testDonorContactIsBlocked() {
    $params = [
      'sequential' => 1,
      'contact_id' => self::$donorContactId,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'status', $params);
    $this->assertEquals(CRM_Gidipirus_Model_ForgetmeStatus::BLOCKED_VALUE, $result['values'][0]['status']);
  }

}
