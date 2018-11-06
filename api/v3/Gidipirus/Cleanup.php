<?php

function _civicrm_api3_gidipirus_cleanup_spec(&$spec) {
}

/**
 * Forget all contacts that are due to be forgotten (based on activity_date_time)
 *
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_gidipirus_cleanup(&$params) {
  $start = microtime(TRUE);
  $query = "SELECT DISTINCT ac.contact_id
            FROM civicrm_activity af
              JOIN civicrm_activity_contact ac ON ac.activity_id = af.id AND ac.record_type_id = 3
            WHERE af.activity_type_id = %1 AND af.status_id = 1 AND af.activity_date_time < NOW()";
  $queryParams = [
    1 => [CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId(), 'Integer'],
  ];
  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
  $contactIds = [];
  while ($dao->fetch()) {
    $contactIds[] = $dao->contact_id;
  }
  $values = [];
  if ($contactIds) {
    $forgetParams = [
      'sequential' => 1,
      'contact_ids' => $contactIds,
    ];
    $result = civicrm_api3('Gidipirus', 'forget', $forgetParams);
    $values = $result['values'];
  }
  $stats = CRM_Gidipirus_Logic_Forget::stats($values);
  $extraReturnValues = array_merge(['time' => microtime(TRUE) - $start], $stats);
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
