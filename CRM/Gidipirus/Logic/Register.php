<?php

class CRM_Gidipirus_Logic_Register {

  /**
   * Check whether contact has FulFillment Request and returns activity id.
   * Only "active" request on scheduled or completed status.
   *
   * @param int $contactId
   * @param bool $isReadyToForget Check if contact is ready to forget, when
   *   date is in the past
   *
   * @return int
   * @throws \CRM_Gidipirus_Exception_NoFulfillment
   * @throws \CRM_Gidipirus_Exception_TooManyFulfillment
   * @throws \CRM_Gidipirus_Exception_NotReadyToForget
   * @throws \CiviCRM_API3_Exception
   */
  public static function hasRequest($contactId, $isReadyToForget = FALSE) {
    $fulfillmentId = CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();
    $query = "SELECT a.id, IF(activity_date_time < NOW(), 1, 0) is_ready
              FROM civicrm_activity a
                JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 3
              WHERE a.activity_type_id = %1 AND a.status_id IN (%3, %4) AND ac.contact_id = %2";
    $params = [
      1 => [$fulfillmentId, 'Integer'],
      2 => [$contactId, 'Integer'],
      3 => [CRM_Gidipirus_Model_Activity::scheduled(), 'Integer'],
      4 => [CRM_Gidipirus_Model_Activity::completed(), 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->N > 1) {
      throw new CRM_Gidipirus_Exception_TooManyFulfillment('Too many Forgetme Fulfillment requests');
    }
    elseif ($dao->N == 1) {
      $dao->fetch();
      if ($isReadyToForget) {
        if (!$dao->is_ready) {
          throw new CRM_Gidipirus_Exception_NotReadyToForget('Contact is not ready to forget because of fulfillment date is in the future');
        }
      }
      return $dao->id;
    }
    else {
      throw new CRM_Gidipirus_Exception_NoFulfillment('Forgetme Fulfillment activity does not exist');
    }
  }

  /**
   * Get FulFillment Request for contact.
   * Only "active" request on scheduled or completed status.
   *
   * @param int $contactId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function getRequest($contactId) {
    $fulfillmentId = CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();
    $query = "SELECT a.id, a.activity_date_time, a.location, REPLACE(a.subject, 'RequestedDate:', '') requested_date
              FROM civicrm_activity a
                JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 3
              WHERE a.activity_type_id = %1 AND a.status_id IN (%3, %4) AND ac.contact_id = %2";
    $params = [
      1 => [$fulfillmentId, 'Integer'],
      2 => [$contactId, 'Integer'],
      3 => [CRM_Gidipirus_Model_Activity::scheduled(), 'Integer'],
      4 => [CRM_Gidipirus_Model_Activity::completed(), 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    if ($dao->N == 1) {
      $dao->fetch();
      return [
        'id' => $dao->id,
        'activity_date_time' => $dao->activity_date_time,
        'requested_date' => $dao->requested_date,
        'channel' => $dao->location,
      ];
    }

    return [];
  }

  /**
   * Register request with fulfillment date set to now so contact is ready to anonymize
   *
   * @param int $contactId
   * @param string $channel
   * @param string $requestedDate
   * @param int $parentActivityId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function now($contactId, $channel, $requestedDate, $parentActivityId = 0) {
    $fulfillmentDate = date('YmdHis');
    return self::add($contactId, $channel, $requestedDate, $fulfillmentDate, $parentActivityId);
  }

  /**
   * Register request with fulfillment date based on configuration scheduled days
   *
   * @param int $contactId
   * @param string $channel
   * @param string $requestedDate
   * @param int $parentActivityId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  public static function future($contactId, $channel, $requestedDate, $parentActivityId = 0) {
    $fulfillmentDate = self::date($requestedDate);
    return self::add($contactId, $channel, $requestedDate, $fulfillmentDate, $parentActivityId);
  }

  /**
   * @param int $contactId
   * @param string $channel
   * @param string $requestedDate
   * @param string $fullfillmentDate
   * @param int $parentActivityId
   *
   * @return array
   * @throws \CiviCRM_API3_Exception
   */
  private static function add($contactId, $channel, $requestedDate, $fullfillmentDate, $parentActivityId = 0) {
    $fulfillmentId = CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();
    $params = [
      'sequential' => 1,
      'activity_type_id' => $fulfillmentId,
      'activity_date_time' => $fullfillmentDate,
      'status_id' => CRM_Gidipirus_Model_Activity::scheduled(),
      'subject' => self::subject($requestedDate),
      'location' => $channel,
      'api.ActivityContact.create' => [
        0 => [
          'activity_id' => '$value.id',
          'contact_id' => CRM_Core_Session::getLoggedInContactID(),
          'record_type_id' => 2,
        ],
        1 => [
          'activity_id' => '$value.id',
          'contact_id' => $contactId,
          'record_type_id' => 3,
        ],
      ],
    ];
    if ($parentActivityId) {
      $params['parent_id'] = $parentActivityId;
    }
    $result = civicrm_api3('Activity', 'create', $params);
    if ($result['id']) {
      return [
        'id' => $contactId,
        'result' => 1,
        'activity_id' => $result['id'],
      ];
    }
    return [
      'id' => $contactId,
      'result' => 0,
    ];
  }

  /**
   * Complete Forgetme Fulfillment request activity
   *
   * @param int $requestId Id of Forgetme Fulfillment request activity
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function complete($requestId) {
    $params = [
      'sequential' => 1,
      'id' => $requestId,
      'status_id' => CRM_Gidipirus_Model_Activity::completed(),
      'activity_date_time' => date('YmdHis'),
    ];
    $result = civicrm_api3('Activity', 'create', $params);
    return !!$result['count'];
  }

  /**
   * Cancel Forgetme Fulfillment request activity
   *
   * @param int $requestId Id of Forgetme Fulfillment request activity
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function cancel($requestId) {
    $params = [
      'sequential' => 1,
      'id' => $requestId,
      'status_id' => CRM_Gidipirus_Model_Activity::cancelled(),
      'activity_date_time' => date('YmdHis'),
    ];
    $result = civicrm_api3('Activity', 'create', $params);
    return !!$result['count'];
  }

  /**
   * Set fulfillment date to now
   *
   * @param int $requestId Id of Forgetme Fulfillment request activity
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function setDateNow($requestId) {
    $params = [
      'sequential' => 1,
      'id' => $requestId,
      'activity_date_time' => date('YmdHis'),
    ];
    $result = civicrm_api3('Activity', 'create', $params);
    return !!$result['count'];
  }

  /**
   * Calculate fulfillment date based on requested date and scheduled days from configuration
   *
   * @param string $requestedDate
   *
   * @return string
   */
  private static function date($requestedDate) {
    $scheduledDays = CRM_Gidipirus_Settings::scheduledDays();
    $rd = substr(str_replace('-', '', $requestedDate), 0, 8);
    $dt = DateTime::createFromFormat('Ymd', $rd);
    return $dt->modify('+' . $scheduledDays . ' days')->format('Y-m-d');
  }

  /**
   * Prepare subject for request activity
   *
   * @param string $requestedDate
   *
   * @return string
   */
  private static function subject($requestedDate) {
    $rd = substr(str_replace('-', '', $requestedDate), 0, 8);
    $dt = DateTime::createFromFormat('Ymd', $rd);
    return 'RequestedDate:' . $dt->format('Y-m-d');
  }

}
