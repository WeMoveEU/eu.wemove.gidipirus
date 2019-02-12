<?php

class CRM_Gidipirus_Model_ForgetmeStatus extends CRM_Gidipirus_Model {

  const OPTION_GROUP_TITLE = 'Forgetme Status';

  const READY = 'Ready';
  const IN_PROGRESS = 'In Progress';
  const OBSOLETE = 'Obsolete';
  const COMPLETED = 'Completed';
  const TOO_MANY_REQUESTS = 'Too Many Requests';
  const NOT_APPLICABLE = 'Not Applicable';

  const READY_VALUE = 10;
  const IN_PROGRESS_VALUE = 20;
  const OBSOLETE_VALUE = 30;
  const COMPLETED_VALUE = 40;
  const TOO_MANY_REQUESTS_VALUE = 60;
  const NOT_APPLICABLE_VALUE = 70;

  const READY_DESC = 'Contact does not have a registered request yet';
  const IN_PROGRESS_DESC = 'Request is recorded and fullfilment date is in future';
  const OBSOLETE_DESC = 'Request is recorded and fullfilment date is in past';
  const COMPLETED_DESC = 'Forgetme processed successfully';
  const BLOCKED_DESC = 'Contact is a donor and can not be processed';
  const TOO_MANY_REQUESTS_DESC = 'Contact has too many requests';
  const NOT_APPLICABLE_DESC = 'Contact type is not an Individual';

  static $statusToValue = [
    self::READY => self::READY_VALUE,
    self::IN_PROGRESS => self::IN_PROGRESS_VALUE,
    self::OBSOLETE => self::OBSOLETE_VALUE,
    self::COMPLETED => self::COMPLETED_VALUE,
    self::TOO_MANY_REQUESTS => self::TOO_MANY_REQUESTS_VALUE,
    self::NOT_APPLICABLE => self::NOT_APPLICABLE_VALUE,
  ];

  static $value = [
    self::READY_VALUE => self::READY,
    self::IN_PROGRESS_VALUE => self::IN_PROGRESS,
    self::OBSOLETE_VALUE => self::OBSOLETE,
    self::COMPLETED_VALUE => self::COMPLETED,
    self::TOO_MANY_REQUESTS_VALUE => self::TOO_MANY_REQUESTS,
    self::NOT_APPLICABLE_VALUE => self::NOT_APPLICABLE,
  ];

  static $description = [
    self::READY_VALUE => self::READY_DESC,
    self::IN_PROGRESS_VALUE => self::IN_PROGRESS_DESC,
    self::OBSOLETE_VALUE => self::OBSOLETE_DESC,
    self::COMPLETED_VALUE => self::COMPLETED_DESC,
    self::TOO_MANY_REQUESTS_VALUE => self::TOO_MANY_REQUESTS_DESC,
    self::NOT_APPLICABLE_VALUE => self::NOT_APPLICABLE_DESC,
  ];

  static $nameToValue = [
    'ready' => self::READY_VALUE,
    'inprogress' => self::IN_PROGRESS_VALUE,
    'obsolete' => self::OBSOLETE_VALUE,
    'completed' => self::COMPLETED_VALUE,
    'toomanyrequest' => self::TOO_MANY_REQUESTS_VALUE,
    'notapplicable' => self::NOT_APPLICABLE_VALUE,
  ];

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
   * Get Forgetme status - Not applicable
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function notApplicable() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::NOT_APPLICABLE);
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
    return self::optionValue(
      self::sanitize(self::OPTION_GROUP_TITLE),
      $name,
      ['value' => self::$statusToValue[$name]]
    );
  }

}
