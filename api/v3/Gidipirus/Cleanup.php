<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_cleanup_spec(&$spec) {
  $spec['channels'] = [
    'name' => 'channels',
    'title' => E::ts('Array of channels'),
    'description' => E::ts('Array of channels'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['limit'] = [
    'name' => 'limit',
    'title' => E::ts('Limit'),
    'description' => E::ts('How many contacts will be anonymised'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => 100,
  ];
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
 * Forget all contacts that are due to be forgotten (based on activity_date_time)
 *
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_gidipirus_cleanup(&$params) {
  $start = microtime(TRUE);
  $channels = [];
  if (is_array($params['channels']) && array_key_exists('IN', $params['channels'])) {
    $channels = $params['channels']['IN'];
  }
  elseif (is_array($params['channels'])) {
    $channels = $params['channels'];
  }
  elseif ($params['channels']) {
    $channels = explode(',', $params['channels']);
  }
  foreach ($channels as $channel) {
    if (!CRM_Gidipirus_Model_RequestChannel::isValid($channel)) {
      throw new CiviCRM_API3_Exception(E::ts('Invalid name of channel: %1', [1 => $channel]), -1);
    }
  }
  $limit = (int) $params['limit'];
  if (!$limit) {
    $limit = 100;
  }
  $dryRun = (bool) $params['dry_run'];
  $query = "SELECT DISTINCT ac.contact_id
            FROM civicrm_activity af
              JOIN civicrm_activity_contact ac ON ac.activity_id = af.id AND ac.record_type_id = 3
            WHERE af.activity_type_id = %1 AND af.status_id = %2 AND af.activity_date_time < NOW()
              AND af.location IN ('" . implode("', '", $channels) . "')
            LIMIT %3";
  $queryParams = [
    1 => [CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId(), 'Integer'],
    2 => [CRM_Gidipirus_Model_Activity::scheduled(), 'Integer'],
    3 => [$limit, 'Integer'],
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
      'dry_run' => $dryRun,
    ];
    $result = civicrm_api3('Gidipirus', 'forget', $forgetParams);
    $values = $result['values'];
  }
  $stats = CRM_Gidipirus_Logic_Forget::stats($values);
  $extraReturnValues = array_merge(['time' => microtime(TRUE) - $start], $stats);
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
