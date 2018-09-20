<?php

class CRM_Gidipirus_Logic_Forget {

  /**
   * @param $contactId
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function forget($contactId) {
    $fulfillmentId = CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();

    return 0;
  }

  private static function date($requestedDate) {
    $scheduledDays = CRM_Gidipirus_Settings::scheduledDays();
    $rd = substr(str_replace('-', '', $requestedDate), 0, 8);
    $dt = DateTime::createFromFormat('Ymd', $rd);
    return $dt->modify('+' . $scheduledDays . ' days')->format('Y-m-d');
  }

  private static function subject($requestedDate) {
    $rd = substr(str_replace('-', '', $requestedDate), 0, 8);
    $dt = DateTime::createFromFormat('Ymd', $rd);
    return 'RequestedDate:' . $dt->format('Y-m-d');
  }

}
