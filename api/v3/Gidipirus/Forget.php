<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_forget_spec(&$spec) {
  $spec['contact_ids'] = [
    'name' => 'contact_ids',
    'title' => E::ts('Array of contacts id'),
    'description' => E::ts('Array of contacts id'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
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
 * Calculate Forgetme Status
 *
 * @param $params
 *
 * @return array
 * @throws \CiviCRM_API3_Exception
 */
function civicrm_api3_gidipirus_forget(&$params) {
  $start = microtime(TRUE);
  $dryRun = (bool) $params['dry_run'];
  if (is_array($params['contact_ids']) && array_key_exists('IN', $params['contact_ids'])) {
    $contactIds = $params['contact_ids']['IN'];
  }
  elseif (is_array($params['contact_ids'])) {
    $contactIds = $params['contact_ids'];
  }
  else {
    $contactIds = [$params['contact_ids']];
  }
  $values = [];
  foreach ($contactIds as $contactId) {
    try {
      $requestId = CRM_Gidipirus_Logic_Register::hasRequest($contactId, TRUE);
      if ($dryRun) {
        $valueResult = 1;
      }
      else {
        $valueResult = CRM_Gidipirus_Logic_Forget::anonymise($contactId);
        CRM_Gidipirus_Logic_Register::complete($requestId);
      }
      $values[$contactId] = [
        'id' => $contactId,
        'result' => $valueResult,
      ];
    }
    catch (CRM_Extension_Exception $exception) {
      $values[$contactId] = [
        'id' => $contactId,
        'result' => 0,
        'error' => $exception->getMessage(),
      ];
    }
  }
  $stats = CRM_Gidipirus_Logic_Forget::stats($values);
  $extraReturnValues = array_merge(['time' => microtime(TRUE) - $start], $stats);
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
