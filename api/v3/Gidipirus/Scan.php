<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_scan_spec(&$spec) {
  $spec['dry_run'] = [
    'name' => 'dry_run',
    'title' => E::ts('Dry run'),
    'description' => E::ts('Only checking whether contact has valid Forgetme Fulfillment activity'),
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 0,
    'api.default' => FALSE,
  ];
}

/**
 * Scan all contacts and choose inactive of them.
 *
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_gidipirus_scan(&$params) {
  $start = microtime(TRUE);
  $dryRun = (bool) $params['dry_run'];
  $fulfillmentId = CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();
  $groupId = 42;

  $query = "SELECT
              c.id
            FROM civicrm_contact c
              JOIN (
                SELECT ac1.contact_id, MAX(a1.activity_date_time) latest_date_time
                FROM civicrm_activity_contact ac1
                  JOIN civicrm_activity a1 ON a1.id = ac1.activity_id
                 WHERE a1.activity_type_id IN (2, 3, 28, 32, 54, 59, 67) -- todo move to table
                GROUP BY ac1.contact_id
              ) latest_ac ON latest_ac.contact_id = c.id
              LEFT JOIN (
                SELECT contact_id
                FROM civicrm_contribution ct
                WHERE ct.contribution_status_id = 1
              ) donors ON donors.contact_id = c.id
              LEFT JOIN (
                SELECT ac2.contact_id, ac2.activity_id
                FROM civicrm_activity_contact ac2
                  JOIN civicrm_activity a2 ON a2.id = ac2.activity_id
                WHERE a2.activity_type_id = %2
              ) request ON request.contact_id = c.id
              LEFT JOIN civicrm_group_contact gc ON gc.group_id = %1 AND gc.status = 'Added' AND gc.contact_id = c.id
            WHERE c.contact_type = 'Individual'
                AND donors.contact_id IS NULL
                AND request.contact_id IS NULL
                AND gc.id IS NULL
                AND latest_ac.latest_date_time < (CURRENT_DATE() - INTERVAL 1 YEAR)
            LIMIT 50"; // todo move limit to parameter
  $queryParams = [
    1 => [$groupId, 'Integer'],
    2 => [$fulfillmentId, 'Integer'],
  ];
  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
  $contactIds = [];
  while ($dao->fetch()) {
    $contactIds[$dao->id] = $dao->id;
  }
  $values = [];
  if ($contactIds) {
    $registerParams = [
      'sequential' => 1,
      'contact_ids' => $contactIds,
      'channel' => CRM_Gidipirus_Model_RequestChannel::EXPIRED,
      'requested_date' => date('YmdHis'),
      // todo implement dry_run (register or force does not have dry_run param!)
      'dry_run' => $dryRun,
    ];
    // todo change to force?
    $result = civicrm_api3('Gidipirus', 'register', $registerParams);
    $values = $result['values'];
  }
  $stats = CRM_Gidipirus_Logic_Forget::stats($values);
  $extraReturnValues = array_merge(['time' => microtime(TRUE) - $start], $stats);
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'scan', $blank, $extraReturnValues);
}
