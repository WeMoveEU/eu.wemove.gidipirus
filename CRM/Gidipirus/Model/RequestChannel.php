<?php

class CRM_Gidipirus_Model_RequestChannel extends CRM_Gidipirus_Model {

  const OPTION_GROUP_TITLE = 'Request Channel';

  const EMAIL = 'email';
  const PHONE = 'phone';
  const PERSONAL = 'personal';
  const PAPER_LETTER = 'paper letter';
  const EXPIRED = 'expired';

  private static $valid = [
    self::EMAIL,
    self::PHONE,
    self::PERSONAL,
    self::PAPER_LETTER,
    self::EXPIRED,
  ];

  /**
   * Get Request channel - Ready
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function email() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::EMAIL);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Request channel - In Progress
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function phone() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::PHONE);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Request channel - Obsolete
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function personal() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::PERSONAL);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Request channel - Completed
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function paperLetter() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::PAPER_LETTER);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Request channel - Blocked
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function expired() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::EXPIRED);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function installOptionGroup() {
    return self::optionGroup(self::OPTION_GROUP_TITLE);
  }

  /**
   * @param $name
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private static function set($name) {
    return self::optionValue(self::sanitize(self::OPTION_GROUP_TITLE), $name);
  }

  /**
   * @param string $name Name of channel
   *
   * @return bool
   */
  public static function isValid($name) {
    return in_array($name, self::$valid);
  }

}
