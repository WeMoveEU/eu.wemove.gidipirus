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
      $fulfillmentId = CRM_Gidipirus_Logic_Register::hasRequest($contactId);
      if (!$fulfillmentId) {
        // todo create new fulfillment
      }
      $result = CRM_Gidipirus_Logic_Forget::forget($contactId);
      $values[$contactId] = [
        'result' => $result,
      ];
    }
    catch (CRM_Gidipirus_Exception_TooManyFulfillment $exception) {
      $values[$contactId] = [
        'result' => 0,
        'error' => $exception->getMessage(),
      ];
    }
    // todo catch contactId doesnt exist (throw new exception at forget() method)
  }
  $extraReturnValues = array(
    'time' => microtime(TRUE) - $start,
  );
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
