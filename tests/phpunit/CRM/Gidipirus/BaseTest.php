<?php

use CRM_Gidipirus_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;

/**
 * @group e2e
 * @see cv
 */
class CRM_Gidipirus_BaseTest extends \PHPUnit_Framework_TestCase implements EndToEndInterface {

  use \Civi\Test\Api3TestTrait;

  protected static $loggedUserId = 1;
  protected static $emptyContactId;
  protected static $fullContactId;
  protected static $donorContactId;

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public static function setUpBeforeClass() {
    $_SESSION['CiviCRM']['userID'] = self::$loggedUserId;
    self::emptyContact();
    self::fullContact();
    self::donorContact();
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public static function tearDownAfterClass() {
    self::deleteContacts();
  }

  public function setUp() {
    parent::setUp();
  }

  public function tearDown() {
    parent::tearDown();
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private static function emptyContact() {
    $params = [
      'sequential' => 1,
      'contact_type' => 'Individual',
      'first_name' => 'Eris',
      'last_name' => 'Eris',
    ];
    $result = civicrm_api3('Contact', 'create', $params);
    self::$emptyContactId = $result['id'];
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private static function fullContact() {
    $params = [
      'sequential' => 1,
      'contact_type' => 'Individual',
      'first_name' => 'Zeus',
      'last_name' => 'Dzeus',
    ];
    $result = civicrm_api3('Contact', 'create', $params);
    self::$fullContactId = $result['id'];
    civicrm_api3('Email', 'create', [
      'sequential' => 1,
      'contact_id' => self::$fullContactId,
      'email' => "zeus@dzeus.org",
      'location_type_id' => "Home",
    ]);
    civicrm_api3('Email', 'create', [
      'sequential' => 1,
      'contact_id' => self::$fullContactId,
      'email' => "contact@dzeus.org",
      'location_type_id' => "Home",
    ]);
    civicrm_api3('Address', 'create', [
      'contact_id' => self::$fullContactId,
      'location_type_id' => "Home",
      'street_address' => "ul. Abcde 11",
      'city' => "Ateny",
      'postal_code' => '01-234',
    ]);
    civicrm_api3('Address', 'create', [
      'contact_id' => self::$fullContactId,
      'location_type_id' => "Home",
      'street_address' => "ul. Abcde 22",
      'city' => "Ateny",
      'postal_code' => '56-789',
    ]);
    $params = [
      'sequential' => 1,
      'activity_type_id' => CRM_Gidipirus_Model_Activity::inboundEmailId(),
      'activity_date_time' => date('YmdHis'),
      'status_id' => 'Completed',
      'subject' => 'subject of inbound email',
      'details' => 'details of inbound email',
      'api.ActivityContact.create' => [
        0 => [
          'activity_id' => '$value.id',
          'contact_id' => self::$loggedUserId,
          'record_type_id' => 2,
        ],
        1 => [
          'activity_id' => '$value.id',
          'contact_id' => self::$fullContactId,
          'record_type_id' => 3,
        ],
      ],
    ];
    civicrm_api3('Activity', 'create', $params);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private static function donorContact() {
    $params = [
      'sequential' => 1,
      'contact_type' => 'Individual',
      'first_name' => 'Plutos',
      'last_name' => 'Plutos',
    ];
    $result = civicrm_api3('Contact', 'create', $params);
    self::$donorContactId = $result['id'];
    civicrm_api3('Contribution', 'create', [
      'debug' => 1,
      'financial_type_id' => "Donation",
      'total_amount' => "66.6",
      'contact_id' => self::$donorContactId,
      'contribution_status_id' => "Completed",
    ]);
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private static function deleteContacts() {
    $params = [
      'sequential' => 1,
      'id' => self::$emptyContactId,
    ];
    civicrm_api3('Contact', 'delete', $params);
    $params = [
      'sequential' => 1,
      'id' => self::$fullContactId,
    ];
    civicrm_api3('Contact', 'delete', $params);
    $params = [
      'sequential' => 1,
      'id' => self::$donorContactId,
    ];
    civicrm_api3('Contact', 'delete', $params);
  }

}
