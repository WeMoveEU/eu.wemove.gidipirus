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
  protected static $donorContactId;

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public static function setUpBeforeClass() {
    $_SESSION['CiviCRM']['userID'] = self::$loggedUserId;
    self::emptyContact();
    self::donorContact();
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public static function tearDownAfterClass() {
    self::tearDownEmptyContactEris();
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
  private static function tearDownEmptyContactEris() {
    $params = [
      'sequential' => 1,
      'id' => self::$emptyContactId,
    ];
    civicrm_api3('Contact', 'delete', $params);
  }

}
