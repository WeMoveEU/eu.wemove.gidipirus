<?php
require_once __DIR__ . '/../../CRM/Gidipirus/BaseTest.php';

/**
 * @group e2e
 */
class api_v3_ConsentsTest extends CRM_Gidipirus_BaseTest {

  private static $unknown = 'unknown@wemove.test';
  private static $simple_member = 'simplemember@wemove.test';
  private static $ex_member = 'exmember@wemove.test';
  private static $youmove_member = 'youmovemember@wemove.test';
  private static $for_update = 'shortlived@wemove.test';

  private static $wemove_en = '2.1.0-en';
  private static $wemove_gp_en = '2.1.greenpeace-en';

  public static function setUpBeforeClass() {
    self::$contactIds = [];

    $result = self::createContact("Some SimpleMember", self::$simple_member, [
        ['id' => self::$wemove_en, 'date' => '2016-06-06 06:06:06', 'status' => 'Completed']
      ]);

    $result = self::createContact("Some YoumoveMember", self::$youmove_member, [
        ['id' => '2.0.somepartner-pt', 'date' => '2018-08-08 08:08:08', 'status' => 'Completed']
      ]);

    $result = self::createContact("Some ExMember", self::$ex_member, [
        ['id' => self::$wemove_en, 'date' => '2016-06-06 06:06:06', 'status' => 'Scheduled'],
        ['id' => self::$wemove_en, 'date' => '2016-06-06 07:06:06', 'status' => 'Completed'],
        ['id' => self::$wemove_en, 'date' => '2016-08-06 06:06:06', 'status' => 'Cancelled'],
        ['id' => self::$wemove_en, 'date' => '2017-07-07 07:07:07', 'status' => 'Completed'],
        ['id' => self::$wemove_en, 'date' => '2017-08-07 07:07:07', 'status' => 'Cancelled'],
      ]);
  }

  public static function tearDownAfterClass() {
    self::deleteContacts();
  }

  /**
   * An unknown contact from Germany requires request consent with 2 factors
   */
  public function testGetUnknownGermany() {
    $params = [
      'email' => self::$unknown,
      'country' => 'de',
      'consent_ids' => [self::$wemove_en]
    ];
    $result = $this->callAPISuccess('Gidipirus', 'get_consents_required', $params);
    $this->assertRequired($result, 2);
  }

  /**
   * An unknown contact from France requires requested consent with 1 factor
   */
  public function testGetUnknownFrance() {
    $params = [
      'email' => self::$unknown,
      'country' => 'fr',
      'consent_ids' => [self::$wemove_en]
    ];
    $result = $this->callAPISuccess('Gidipirus', 'get_consents_required', $params);
    $this->assertRequired($result, 1);
  }

  /**
   * A member from Poland does not require a WeMove consent
   */
  public function testGetSimpleMemberPoland() {
    $params = [
      'email' => self::$simple_member,
      'country' => 'pl',
      'consent_ids' => [self::$wemove_en]
    ];
    $result = $this->callAPISuccess('Gidipirus', 'get_consents_required', $params);
    $this->assertNotRequired($result);
  }

  /**
   * A ex-member from Poland requires a WeMove consent with 1 factor
   */
  public function testGetExMemberPoland() {
    $params = [
      'email' => self::$ex_member,
      'country' => 'pl',
      'consent_ids' => [self::$wemove_en]
    ];
    $result = $this->callAPISuccess('Gidipirus', 'get_consents_required', $params);
    $this->assertRequired($result, 1);
  }

  /**
   * A Youmove member from Belgium does not require a consent patched for another partner
   */
  public function testGetYoumoveMemberBelgium() {
    $params = [
      'email' => self::$youmove_member,
      'country' => 'be',
      'consent_ids' => [self::$wemove_gp_en]
    ];
    $result = $this->callAPISuccess('Gidipirus', 'get_consents_required', $params);
    $this->assertNotRequired($result);
  }

  /**
   * Setting a consent status to a contact creates the corresponding activity
   */
  public function testSetExMemberPending() {
    $r = self::createContact("Clean ExMember", __METHOD__ . '@wemove.test', [
        ['id' => self::$wemove_en, 'date' => '2016-06-06 07:06:06', 'status' => 'Completed'],
        ['id' => self::$wemove_en, 'date' => '2016-08-06 06:06:06', 'status' => 'Cancelled'],
      ], 'Removed');
    $contactId = $r['id'];
    $params = [
      'contact_id' => $contactId,
      'date' => '2019-09-09 09:09:09',
      'consent_id' => self::$wemove_gp_en,
      'status' => 'Pending',
      'is_member' => 0,
      'method' => 'does not matter'
    ];
    $result = $this->callAPISuccess('Gidipirus', 'set_consent_status', $params);
    $this->assertHasConsent($params);
    $this->assertHasEmptyGdprFields($params);
    $this->assertMember($contactId, 0);
  }

  /**
   * Setting a consent status to a contact creates the corresponding activity
   */
  public function testSetExMemberConfirmed() {
    $r = self::createContact("Clean ExMember", __METHOD__ . '@wemove.test', [
        ['id' => self::$wemove_en, 'date' => '2016-06-06 07:06:06', 'status' => 'Completed'],
        ['id' => self::$wemove_en, 'date' => '2016-08-06 06:06:06', 'status' => 'Cancelled'],
      ], 'Removed');
    $contactId = $r['id'];
    $params = [
      'contact_id' => $contactId,
      'date' => '2019-09-09 09:09:09',
      'consent_id' => self::$wemove_gp_en,
      'status' => 'Confirmed',
      'is_member' => 0,
      'method' => 'unit-test'
    ];
    $now = date('Y-m-d H:i:s');
    $result = $this->callAPISuccess('Gidipirus', 'set_consent_status', $params);
    $this->assertHasConsent($params);
    $this->assertHasGdprFields($params);
    //The second of now might not be the same as the second of join creation...
    $this->assertHasJoin(['date' => ['>=' => $now]] + $params);
    $this->assertMember($contactId, 1);
  }

  /**
   * Setting a consent status to a contact creates the corresponding activity
   */
  public function testSetMemberCancelled() {
    $r = self::createContact("Clean Member", __METHOD__ . '@wemove.test', [
        ['id' => self::$wemove_en, 'date' => '2016-06-06 07:06:06', 'status' => 'Completed'],
      ], 'Added');
    $contactId = $r['id'];
    $params = [
      'contact_id' => $contactId,
      'date' => '2019-09-09 09:09:09',
      'consent_id' => self::$wemove_en,
      'status' => 'Cancelled',
      'is_member' => 1,
      'method' => 'unit-test'
    ];
    $now = date('Y-m-d H:i:s');
    $result = $this->callAPISuccess('Gidipirus', 'set_consent_status', $params);
    $this->assertHasConsent($params);
    $this->assertHasEmptyGdprFields($params);
    //The second of now might not be the same as the second of leave creation...
    $this->assertHasLeave(['date' => ['>=' => $now]] + $params);
    $this->assertMember($contactId, 0);
  }

  /**
   * Cancelling a simple member creates a cancel activity, a leave activity and clears the gdpr fields
   */
  public function testCancelMember() {
    $r = self::createContact("Clean Member", __METHOD__ . '@wemove.test', [
        ['id' => self::$wemove_en, 'date' => '2016-06-06 07:06:06', 'status' => 'Completed'],
      ], 'Added');
    $contactId = $r['id'];
    $params = [
      'contact_id' => $contactId,
      'date' => '2019-09-09 09:09:09',
      'method' => 'unit-test',
    ];
    $now = date('Y-m-d H:i:s');
    $result = $this->callAPISuccess('Gidipirus', 'cancel_consents', $params);
    $this->assertHasConsent($params + ['status' => 'Cancelled', 'consent_id' => self::$wemove_en]);
    //The second of now might not be the same as the second of leave creation...
    $this->assertHasLeave(['date' => ['>=' => $now]] + $params);
    $this->assertHasEmptyGdprFields($params);
    $this->assertMember($contactId, 0);
    self::deleteContact($contactId);
  }

  public function assertNotRequired($result) {
    $this->assertEquals(array(), $result['values']['consents_required']);
  }

  public function assertRequired($result, $factors) {
    $this->assertEquals(
      [[ 'consent_id' => self::$wemove_en, 'factors' => $factors]],
      $result['values']['consents_required']
    );
  }

  public function assertHasConsent($params) {
    list($version, $language) = explode('-', $params['consent_id']);
    $getParams = [
      'source_contact_id' => $params['contact_id'],
      'activity_type_id' => 'SLA Acceptance',
      'status_id' => CRM_Gidipirus_Logic_Consent::consentActivityStatus($params['status']),
      'subject' => $version,
      'location' => $language,
      'activity_date_time' => $params['date'],
    ];
    $result = civicrm_api3('Activity', 'get', $getParams);
    $this->assertEquals(1, $result['count'], "The contact does not have the expected consent activity");
  }

  public function assertHasLeave($params) {
    $getParams = [
      'source_contact_id' => $params['contact_id'],
      'activity_type_id' => 'Leave',
      'status_id' => 'Completed',
      'subject' => $params['method'],
      'activity_date_time' => $params['date'],
    ];
    $result = civicrm_api3('Activity', 'get', $getParams);
    $this->assertEquals(1, $result['count'], "The contact does not have the expected leave activity");
  }

  public function assertHasJoin($params) {
    $getParams = [
      'source_contact_id' => $params['contact_id'],
      'activity_type_id' => 'Join',
      'status_id' => 'Completed',
      'subject' => $params['method'],
      'activity_date_time' => $params['date'],
    ];
    $result = civicrm_api3('Activity', 'get', $getParams);
    $this->assertEquals(1, $result['count'], "The contact does not have the expected join activity");
  }

  public function assertHasGdprFields($params) {
    $getParams = [
      'id' => $params['contact_id'],
      'return' => array_values(CRM_Gidipirus_Logic_Consent::field()),
    ];
    $result = civicrm_api3('Contact', 'getsingle', $getParams);
    $consentId = $result[CRM_Gidipirus_Logic_Consent::field('gdpr.Consent_version')] . '-' . $result[CRM_Gidipirus_Logic_Consent::field('gdpr.Consent_language')];
    $this->assertEquals($params['consent_id'], $consentId);
  }

  public function assertHasEmptyGdprFields($params) {
    $getParams = [
      'id' => $params['contact_id'],
      'return' => array_values(CRM_Gidipirus_Logic_Consent::field()),
    ];
    $result = civicrm_api3('Contact', 'getsingle', $getParams);
    $this->assertEmpty($result[CRM_Gidipirus_Logic_Consent::field('gdpr.Consent_version')]);
  }

  public function assertMember($contactId, $expected) {
    $getParams = [
      'contact_id' => $contactId,
      'status' => 'Added',
      'group_id' => CRM_Gidipirus_Settings::membersGroupId()
    ];
    $result = civicrm_api3('GroupContact', 'get', $getParams);
    $not = $expected ? 'not' : '';
    $this->assertEquals($expected, $result['count'], "The contact $contactId is $not a member");
  }

  public static function createContact($name, $email, $consents, $memberStatus = NULL) {
		$splitName = explode(' ', $name);
    $params = [
			'contact_type' => "Individual",
			'first_name' => $splitName[0],
			'last_name' => $splitName[1],
			'api.Email.create' => ['is_primary' => true, 'email' => $email],
		];
    foreach ($consents as $consent) {
      $consentId = explode('-', $consent['id']);
      $params['api.Activity.create'][] = [
        'source_contact_id' => '$value.id',
        'activity_type_id' => "SLA Acceptance",
        'activity_date_time' => $consent['date'],
        'subject' => $consentId[0],
        'location' => $consentId[1],
        'status_id' => $consent['status'] //Expecting activity status, not consent status!
      ];
    }
    if ($memberStatus) {
      $params['api.GroupContact.create'] = [
        'contact_id' => '$value.id',
        'group_id' => CRM_Gidipirus_Settings::membersGroupId(),
        'status' => $memberStatus
      ];
    }
		$result = civicrm_api3('Contact', 'create', $params);
    self::$contactIds[$email] = $result['id'];
    return $result;
  }

  private static function deleteContact($contactId) {
    $query = "
      DELETE a, ac, c
      FROM civicrm_contact c
      LEFT JOIN civicrm_activity_contact ac ON ac.contact_id = c.id
      LEFT JOIN civicrm_activity a ON a.id = ac.activity_id
      WHERE c.id = %1
    ";
    $params = [ 1 => [$contactId, 'Integer'] ];
    CRM_Core_DAO::executeQuery($query, $params);
  }

  private static function deleteContacts() {
    foreach (self::$contactIds as $email => $contactId) {
      self::deleteContact($contactId);
    }
  }
}
