<?php

class CRM_Gidipirus_Logic_Scan {

  /**
   * Check if Forgetme Fulfillment with expired reason is still relevant
   *
   * @param $requestId
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   * @throws \CRM_Gidipirus_Exception_NotExpired
   */
  public static function isRelevantRequestIfExpired($requestId) {
    $query = "SELECT ac.contact_id
              FROM civicrm_activity a
                JOIN civicrm_activity_contact ac ON ac.activity_id = a.id AND ac.record_type_id = 3
              WHERE a.id = %1 AND a.location = %2";
    $queryParams = [
      1 => [$requestId, 'Integer'],
      2 => [CRM_Gidipirus_Model_RequestChannel::EXPIRED, 'String'],
    ];
    $contactId = CRM_Core_DAO::singleValueQuery($query, $queryParams);
    if ($contactId) {
      $scannedActivitiesId = CRM_Gidipirus_Settings::scannedActivitiesId();
      $query = "SELECT
                  DISTINCTROW c.id
                FROM civicrm_contact c
                  JOIN (
                         SELECT ac1.contact_id, MAX(a1.activity_date_time) latest_date_time
                         FROM civicrm_activity_contact ac1
                           JOIN civicrm_activity a1 ON a1.id = ac1.activity_id
                         WHERE a1.activity_type_id IN (" . implode(', ', $scannedActivitiesId) . ")
                           AND ac1.contact_id = %1
                         GROUP BY ac1.contact_id
                       ) latest_ac ON latest_ac.contact_id = c.id
                  LEFT JOIN (
                              SELECT contact_id
                              FROM civicrm_contribution ct
                              WHERE ct.contribution_status_id = 1 AND ct.contact_id = %1
                            ) donors ON donors.contact_id = c.id
                  LEFT JOIN civicrm_group_contact gc ON gc.group_id = %2 AND gc.status = 'Added' AND gc.contact_id = c.id
                  JOIN civicrm_subscription_history sh ON sh.group_id = %2 AND sh.contact_id = c.id
                WHERE c.contact_type = 'Individual' AND c.id = %1
                    AND donors.contact_id IS NULL
                    AND gc.id IS NULL
                    AND latest_ac.latest_date_time < (CURRENT_DATE() - INTERVAL 1 YEAR)";
      $queryParams = [
        1 => [$contactId, 'Integer'],
        2 => [CRM_Gidipirus_Settings::membersGroupId(), 'Integer'],
      ];
      $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
      $dao->fetch();
      if (!$dao->N) {
        throw new CRM_Gidipirus_Exception_NotExpired('This expired registration request is already not relevant.');
      }
    }

    return TRUE;
  }

}
