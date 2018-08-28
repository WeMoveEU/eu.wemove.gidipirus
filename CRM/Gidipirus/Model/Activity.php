<?php

class CRM_Gidipirus_Model_Activity extends CRM_Gidipirus_Model {

  const FORGETME_REQUEST = 'Forgetme Request';
  const FORGETME_FULFILLMENT = 'Forgetme Fulfillment';

  /**
   * Get activity type id for Forgetme Request
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function forgetmeRequestId() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::activityType(self::FORGETME_REQUEST);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get activity type id for Forgetme Fulfillment
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function forgetmeFulfillmentId() {
    $key = __CLASS__ . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::activityType(self::FORGETME_FULFILLMENT);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * Get or create activity type
   *
   * @param $name
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  private static function activityType($name) {
    return self::set('activity_type', $name);
  }

}
