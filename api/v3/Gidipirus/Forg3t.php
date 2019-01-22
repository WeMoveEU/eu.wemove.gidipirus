<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_forg3t_spec(&$spec) {
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
    'api.required' => 1,
    'api.default' => 0,
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
function civicrm_api3_gidipirus_forg3t(&$params) {
  $start = microtime(TRUE);
  $dryRun = (int) $params['dry_run'];
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
    $requestId = 0;
    $tx = new CRM_Core_Transaction();
    try {
      $requestId = CRM_Gidipirus_Logic_Register::hasRequest($contactId, TRUE);
      CRM_Gidipirus_Logic_Scan::isRelevantRequestIfExpired($requestId);
      if ($dryRun) {
        $valueResult = 1;
      }
      else {
        $valueResult = CRM_Gidipirus_Logic_Forget::anonymise($contactId);
        CRM_Gidipirus_Logic_Register::complete($requestId);
      }
      $values[$contactId] = [
        'id' => $contactId,
        'activity_id' => $requestId,
        'result' => $valueResult,
      ];
      $tx->commit();
      $tx = NULL;
    }
    catch (CRM_Gidipirus_Exception_NotExpired $exception) {
      $tx->rollback();
      $tx = NULL;
      if ($dryRun) {
        $valueResult = 1;
      }
      else {
        $valueResult = (int) CRM_Gidipirus_Logic_Register::cancel($requestId);
      }
      $values[$contactId] = [
        'id' => $contactId,
        'activity_id' => $requestId,
        'result' => $valueResult,
        'error' => $exception->getMessage(),
      ];
    }
    catch (CRM_Extension_Exception $exception) {
      $tx->rollback();
      $tx = NULL;
      $values[$contactId] = [
        'id' => $contactId,
        'activity_id' => $requestId,
        'result' => 0,
        'error' => $exception->getMessage(),
      ];
    }
  }
  $stats = CRM_Gidipirus_Logic_Forget::stats($values);
  $extraReturnValues = array_merge(['time' => microtime(TRUE) - $start], $stats);
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
