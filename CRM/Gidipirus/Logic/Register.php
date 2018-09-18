<?php

class CRM_Gidipirus_Logic_Register {

  /**
   * @param $contactId
   * @param $channel
   * @param $requestedDate
   * @param int $parentActivityId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function add($contactId, $channel, $requestedDate, $parentActivityId = 0) {
    $fulfillmentId = CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();
    $params = [
      'sequential' => 1,
      'activity_type_id' => $fulfillmentId,
      'activity_date_time' => self::date($requestedDate),
      'status_id' => 'Scheduled',
      'subject' => self::subject($requestedDate),
      'source_contact_id' => $contactId,
      'location' => $channel,
    ];
    if ($parentActivityId) {
      $params['parent_id'] = $parentActivityId;
    }
    // todo set target (contactId) and added contact (logged user)
    $result = civicrm_api3('Activity', 'create', $params);
    if ($result['id']) {
      return [
        'result' => 1,
        'activity_id' => $result['id'],
      ];
    }
    return [
      'result' => 0,
    ];
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
