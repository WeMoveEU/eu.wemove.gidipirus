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
  $contactIds = $params['contact_ids'];
  if (!is_array($contactIds)) {
    $contactIds = [$contactIds];
  }
  $values = [];
  foreach ($contactIds as $contactId) {
    try {
      $requestId = CRM_Gidipirus_Logic_Register::hasRequest($contactId);
      if ($dryRun) {
        $result = 1;
      }
      else {
        $result = CRM_Gidipirus_Logic_Forget::anonymise($contactId);
        CRM_Gidipirus_Logic_Register::complete($requestId);
      }
      $values[$contactId] = [
        'id' => $contactId,
        'result' => $result,
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
