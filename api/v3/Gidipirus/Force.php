<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_force_spec(&$spec) {
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
function civicrm_api3_gidipirus_force(&$params) {
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

  $channel = $params['channel'];
  if (!CRM_Gidipirus_Model_RequestChannel::isValid($channel)) {
    throw new CiviCRM_API3_Exception(E::ts('Invalid name of channel'), -1);
  }
  $requestedDate = $params['requested_date'];
  $activityParentId = $params['activity_parent_id'];

  $values = [];
  foreach ($contactIds as $contactId) {
    try {
      $requestId = CRM_Gidipirus_Logic_Register::hasRequest($contactId);
      if ($dryRun) {
        $setDate = 1;
      }
      else {
        $setDate = CRM_Gidipirus_Logic_Register::setDateNow($requestId);
      }
      $values[$contactId] = [
        'id' => $contactId,
        'result' => (int) $setDate,
        'activity_id' => $requestId,
      ];
    }
    catch (CRM_Gidipirus_Exception_NoFulfillment $exception) {
      if ($dryRun) {
        $result = [
          'id' => $contactId,
          'result' => 1,
        ];
      }
      else {
        $result = CRM_Gidipirus_Logic_Register::now($contactId, $channel, $requestedDate, $activityParentId);
        if ($result['result']) {
          CRM_Gidipirus_Logic_Email::holdEmails($contactId);
        }
      }
      $values[$contactId] = $result;
    }
    catch (CRM_Extension_Exception $exception) {
      $values[$contactId] = [
        'id' => $contactId,
        'result' => 0,
        'error' => $exception->getMessage(),
      ];
    }
  }
  $extraReturnValues = array(
    'time' => microtime(TRUE) - $start,
  );
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
