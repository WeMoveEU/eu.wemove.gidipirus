<?php
require_once __DIR__ . '/../../CRM/Gidipirus/BaseTest.php';

/**
 * @group e2e
 */
class api_v3_RegisterTest extends CRM_Gidipirus_BaseTest {

  /**
   * Check if empty contact is ready to forgetMe
   */
  public function testRegister() {
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactErisId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => date('YmdHis'),
    ];
    $result = $this->callAPISuccess('Gidipirus', 'register', $params);
    $this->assertTrue($result['values'][0]['result'] == 1);
    $this->assertGreaterThan(0, $result['values'][0]['activity_id']);
  }

}
