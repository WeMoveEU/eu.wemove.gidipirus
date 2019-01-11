<?php
require_once __DIR__ . '/../../CRM/Gidipirus/BaseTest.php';

/**
 * @group e2e
 */
class api_v3_ScanTest extends CRM_Gidipirus_BaseTest {

  /**
   * @throws \CRM_Gidipirus_Exception_NoFulfillment
   * @throws \CRM_Gidipirus_Exception_NotReadyToForget
   * @throws \CRM_Gidipirus_Exception_TooManyFulfillment
   * @throws \CiviCRM_API3_Exception
   */
  public function testExpiredRequestIsAdded() {
    self::inactiveMembersContact();
    $result = $this->callAPISuccess('Gidipirus', 'scan', ['dry_run' => 0]);
    $this->assertEquals(1, $result['count']);

    $requestId = CRM_Gidipirus_Logic_Register::hasRequest(self::$inactiveMemberContactId);
    $this->assertGreaterThan(0, $requestId);

    $result = civicrm_api3('Activity', 'get', [
      'sequential' => 1,
      'id' => $requestId,
    ]);
    $this->assertEquals(CRM_Gidipirus_Model_RequestChannel::EXPIRED, $result['values'][0]['location']);
  }

}
