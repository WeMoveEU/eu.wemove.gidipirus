<?php

use CRM_Gidipirus_ExtensionUtil as E;
use Civi\Test\EndToEndInterface;

/**
 * @group e2e
 * @see cv
 */
class CRM_Gidipirus_BaseTest extends \PHPUnit_Framework_TestCase implements EndToEndInterface {

  use \Civi\Test\Api3TestTrait;

  protected static $emptyContactErisId;

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public static function setUpBeforeClass() {
    $_SESSION['CiviCRM']['userID'] = 1;
    self::setUpEmptyContactEris();
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
  private static function setUpEmptyContactEris() {
    $params = [
      'sequential' => 1,
      'contact_type' => 'Individual',
      'first_name' => 'Eris',
      'last_name' => 'Eris',
    ];
    $result = civicrm_api3('Contact', 'create', $params);
    self::$emptyContactErisId = $result['id'];
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  private static function tearDownEmptyContactEris() {
    $params = [
      'sequential' => 1,
      'id' => self::$emptyContactErisId,
    ];
    civicrm_api3('Contact', 'delete', $params);
  }

}
