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
  $contactIds = $params['contact_ids'];
  if (!is_array($contactIds)) {
    $contactIds = [$contactIds];
  }
  $values = [];
  foreach ($contactIds as $contactId) {
    try {
      $requestId = CRM_Gidipirus_Logic_Register::hasRequest($contactId);
      $result = CRM_Gidipirus_Logic_Forget::anonymise($contactId);
      CRM_Gidipirus_Logic_Register::complete($requestId);
      $values[$contactId] = [
        'result' => $result,
      ];
    }
    catch (CRM_Extension_Exception $exception) {
      $values[$contactId] = [
        'result' => 0,
        'error' => $exception->getMessage(),
      ];
    }
  }
  $stats = stats($values);
  $extraReturnValues = array_merge(['time' => microtime(TRUE) - $start], $stats);
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}

function stats($values) {
  $stats = [
    'updated' => 0,
    'not_updated' => 0,
  ];
  foreach ($values as $v) {
    if ($v['result']) {
      $stats['updated']++;
    }
    else {
      $stats['not_updated']++;
    }
  }
  return $stats;
}
