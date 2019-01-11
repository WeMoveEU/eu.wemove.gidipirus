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
    $this->assertGreaterThanOrEqual(1, $result['count']);

    $requestId = CRM_Gidipirus_Logic_Register::hasRequest(self::$inactiveMemberContactId);
    $this->assertGreaterThan(0, $requestId);

    $result = civicrm_api3('Activity', 'get', [
      'sequential' => 1,
      'id' => $requestId,
    ]);
    $this->assertEquals(CRM_Gidipirus_Model_RequestChannel::EXPIRED, $result['values'][0]['location']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testNoMembersContactsShouldNotBeScanned() {
    $contactId = self::inactiveMembersContact();
    $result = $this->callAPISuccess('Gidipirus', 'scan', ['dry_run' => 1]);
    $this->assertEquals(1, $result['count']);
    foreach ($result['values'] as $r) {
      if ($r['id'] == $contactId) {
        $this->assertEquals(1, $r['result']);
      }
    }

    $query = "DELETE FROM civicrm_subscription_history WHERE contact_id = %1";
    CRM_Core_DAO::executeQuery($query, [1 => [$contactId, 'Integer']]);
    $query = "DELETE FROM civicrm_group_contact WHERE contact_id = %1";
    CRM_Core_DAO::executeQuery($query, [1 => [$contactId, 'Integer']]);
    self::inactiveMembersContact();
    $result = $this->callAPISuccess('Gidipirus', 'scan', ['dry_run' => 1]);
    $markedAsExpired = FALSE;
    foreach ($result['values'] as $r) {
      if ($r['id'] == $contactId) {
        $markedAsExpired = TRUE;
      }
    }
    $this->assertFalse($markedAsExpired);
  }

}
