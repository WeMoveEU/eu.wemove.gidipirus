<?php

class CRM_Gidipirus_Logic_Register {

  /**
   * @param $contactId
   * @param $channel
   * @param $requestedDate
   * @param int $parentActivityId
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function add($contactId, $channel, $requestedDate, $parentActivityId = 0) {
    $fulfillmentId = CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();
    $scheduledDays = CRM_Gidipirus_Settings::scheduledDays();
    return 0;
  }

}
