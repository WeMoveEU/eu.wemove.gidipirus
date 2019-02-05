<?php
require_once __DIR__ . '/../../CRM/Gidipirus/BaseTest.php';

/**
 * @group e2e
 */
class api_v3_ForgetTest extends CRM_Gidipirus_BaseTest {

  /**
   *
   */
  public function testEmptyContactHasNoFullfilmentRequest() {
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$emptyContactId,
      'dry_run' => 0,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'forg3t', $params);
    $this->assertTrue($result['values'][0]['result'] == 0);
    $this->assertTrue($result['values'][0]['error'] == 'Forgetme Fulfillment activity does not exist');
    $this->assertEquals(0, $result['updated']);
    $this->assertEquals(1, $result['not_updated']);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testFullContactRegisterAndForget() {
    $requestedDate = date('Y-m-d');
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$fullContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => $requestedDate,
      'dry_run' => 0,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'register', $params);
    $activityId = $result['values'][0]['activity_id'];

    $params = [
      'sequential' => 1,
      'contact_ids' => self::$fullContactId,
      'dry_run' => 0,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'forg3t', $params);
    $this->assertTrue($result['values'][0]['result'] == 0);
    $this->assertEquals('Contact is not ready to forget because of fulfillment date is in the future', $result['values'][0]['error']);
    $this->assertEquals(0, $result['updated']);
    $this->assertEquals(1, $result['not_updated']);

    civicrm_api3('Activity', 'delete', ['id' => $activityId]);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testFullContactForceAndForget() {
    $requestedDate = date('Y-m-d');
    $params = [
      'sequential' => 1,
      'contact_ids' => self::$fullContactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => $requestedDate,
      'dry_run' => 0,
    ];
    $this->callAPISuccess('Gidipirus', 'force', $params);
    sleep(1);

    $params = [
      'sequential' => 1,
      'contact_ids' => self::$fullContactId,
      'dry_run' => 0,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'forg3t', $params);
    $this->assertTrue($result['values'][0]['result'] == 1);
    $this->assertEquals(1, $result['updated']);
    $this->assertEquals(0, $result['not_updated']);

    $isEmpty = [
      'middle_name',
      'legal_name',
      'nick_name',
      'prefix_id',
      'suffix_id',
      'formal_title',
      'communication_style_id',
      'postal_greeting_custom',
      'addressee_custom',
      'job_title',
    ];
    $isOne = [
      'addressee_id',
      'postal_greeting_id',
      'email_greeting_id',
    ];
    $result = civicrm_api3('Contact', 'get', [
      'sequential' => 1,
      'id' => self::$fullContactId,
      'return' => implode(',', array_merge($isEmpty, $isOne)) . ',first_name,last_name',
    ]);
    $this->assertEquals(CRM_Gidipirus_Logic_Contact::FORGOTTEN_FIRST_NAME, $result['values'][0]['first_name']);
    $this->assertEquals(CRM_Gidipirus_Logic_Contact::FORGOTTEN_LAST_NAME, $result['values'][0]['last_name']);
    foreach ($isOne as $item) {
      $this->assertEquals(1, $result['values'][0][$item], $item);
    }
    foreach ($isEmpty as $item) {
      $this->assertEmpty($result['values'][0][$item], $item);
    }

    $params = array(
      'sequential' => 1,
      'entity_table' => "civicrm_contact",
      'entity_id' => self::$fullContactId,
      'tag_id' => CRM_Gidipirus_Model_Tag::forgottenId(),
    );
    $result = civicrm_api3('EntityTag', 'get', $params);
    $this->assertEquals(1, $result['count']);

    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'contact_id' => self::$fullContactId,
      'location_type_id' => ['<>' => "Billing"],
    ]);
    $emailTemplate = str_replace('+', '\+', CRM_Gidipirus_Settings::emailTemplate());
    $emailTemplate = str_replace('.', '\.', $emailTemplate);
    $emailTemplate = '/' . str_replace('%RANDOM%', '[a-z0-9]*', $emailTemplate) . '/';
    foreach ($result['values'] as $item) {
      $this->assertRegExp($emailTemplate, $item['email']);
    }

    $isEmpty = [
      'street_address',
      'city',
      'county_id',
      'state_province_id',
      'postal_code',
      'geo_code_1',
      'geo_code_2',
      'supplemental_address_1',
      'supplemental_address_2',
      'supplemental_address_3',
    ];
    $result = civicrm_api3('Address', 'get', [
      'sequential' => 1,
      'contact_id' => self::$fullContactId,
      'location_type_id' => CRM_Gidipirus_Logic_Address::LOCATION_TYPE,
      'return' => implode(',', $isEmpty),
    ]);
    foreach ($result['values'] as $item) {
      foreach ($isEmpty as $key) {
        $this->assertArrayNotHasKey($key, $item, $key);
      }
    }

    // todo check phones

    $query = "SELECT a.id, a.subject, a.details
              FROM civicrm_activity a
              JOIN civicrm_activity_contact ac ON ac.activity_id = a.id
              WHERE ac.contact_id = %1 AND a.activity_type_id = %2";
    $params = [
      1 => [self::$fullContactId, 'Integer'],
      2 => [CRM_Gidipirus_Model_Activity::inboundEmailId(), 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    $this->assertGreaterThanOrEqual(1, $dao->N, 'There is no any Inbound Email activities');
    while ($dao->fetch()) {
      $this->assertEquals(CRM_Gidipirus_Model_Activity::FORGOTTEN_SUBJECT, $dao->subject);
      $this->assertEquals(CRM_Gidipirus_Model_Activity::FORGOTTEN_DETAILS, $dao->details);
    }

  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function testForgetDeletedContacts() {
    $contactId = self::emptyContact();
    $requestedDate = new DateTime();
    $params = [
      'sequential' => 1,
      'contact_ids' => $contactId,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EMAIL,
      'requested_date' => $requestedDate->format('Y-m-d'),
      'dry_run' => 0,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'force', $params);

    $this->callAPISuccess('Contact', 'delete', [
      'sequential' => 1,
      'id' => $contactId,
    ]);

    $params = [
      'sequential' => 1,
      'contact_ids' => $contactId,
      'dry_run' => 0,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'forg3t', $params);
    $this->assertTrue($result['values'][0]['result'] == 1);
    $this->assertEquals(1, $result['updated']);
    $this->assertEquals(0, $result['not_updated']);

  }

}
