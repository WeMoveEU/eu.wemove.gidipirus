<?php

class CRM_Gidipirus_Logic_Activity {

  /**
   * Clean out inbound emails (subject, details)
   *
   * @param int $contactId
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function clean($contactId) {
    $inboundEmailId = CRM_Gidipirus_Model_Activity::inboundEmailId();
    $query = "SELECT a.id
              FROM civicrm_activity a
                JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 2
              WHERE a.activity_type_id = %1 AND ac.contact_id = %2";
    $params = [
      1 => [$inboundEmailId, 'Integer'],
      2 => [$contactId, 'Integer'],
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $params);
    while ($dao->fetch()) {
      $params = [
        'sequential' => 1,
        'id' => $dao->id,
        'subject' => '',
        'details' => '',
      ];
      $result = civicrm_api3('Activity', 'create', $params);
    }
  }

}