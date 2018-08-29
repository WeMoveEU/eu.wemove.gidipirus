<?php

class CRM_Gidipirus_Model_ForgetmeStatus extends CRM_Gidipirus_Model {

  const OPTION_GROUP_TITLE = 'Forgetme Status';

  const READY = 'Ready';
  const IN_PROGRESS = 'In Progress';
  const OBSOLETE = 'Obsolete';
  const COMPLETED = 'Completed';
  const BLOCKED = 'Blocked';
  const INVALID_REQUEST = 'Invalid Request';
  const TOO_MANY_REQUESTS = 'Too many requests';

  /**
   * Get Forgetme status - Ready
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function ready() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::READY);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Forgetme status - In Progress
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function inProgress() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::IN_PROGRESS);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Forgetme status - Obsolete
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function obsolete() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::OBSOLETE);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Forgetme status - Completed
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function completed() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::COMPLETED);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Forgetme status - Blocked
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function blocked() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::BLOCKED);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Forgetme status - Invalid Request
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function invalidRequest() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::INVALID_REQUEST);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get Forgetme status - Too many requests
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function tooManyRequests() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::TOO_MANY_REQUESTS);
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

}
