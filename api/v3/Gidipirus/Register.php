<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_register_spec(&$spec) {
  $spec['contact_ids'] = [
    'name' => 'contact_ids',
    'title' => E::ts('Array of contacts id'),
    'description' => E::ts('Array of contacts id'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
  ];
  $spec['channel'] = [
    'name' => 'channel',
    'title' => E::ts('Channel'),
    'description' => E::ts('Channel name'),
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['requested_date'] = [
    'name' => 'requested_date',
    'title' => E::ts('Requested date'),
    'description' => E::ts('Requested date'),
    'type' => CRM_Utils_Type::T_DATE,
    'api.required' => 1,
  ];
  $spec['activity_parent_id'] = [
    'name' => 'activity_parent_id',
    'title' => E::ts('Parent activity id'),
    'description' => E::ts('Parent activity id, source of forgetme request'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
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
function civicrm_api3_gidipirus_register(&$params) {
  $start = microtime(TRUE);
  $dryRun = (bool) $params['dry_run'];
  $contactIds = $params['contact_ids'];
  if (!is_array($contactIds)) {
    $contactIds = [$contactIds];
  }
  $channel = $params['channel'];
  if (!CRM_Gidipirus_Model_RequestChannel::isValid($channel)) {
    throw new CiviCRM_API3_Exception(E::ts('Invalid name of channel'), -1);
  }
  $requestedDate = $params['requested_date'];
  $activityParentId = $params['activity_parent_id'];

  $values = [];
  foreach ($contactIds as $contactId) {
    if ($dryRun) {
      $result = [
        'id' => $contactId,
        'result' => 1,
      ];
    }
    else {
      $result = CRM_Gidipirus_Logic_Register::future($contactId, $channel, $requestedDate, $activityParentId);
      if ($result['result']) {
        CRM_Gidipirus_Logic_Email::holdEmails($contactId);
      }
    }
    $values[$contactId] = $result;
  }
  $extraReturnValues = array(
    'time' => microtime(TRUE) - $start,
  );
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
