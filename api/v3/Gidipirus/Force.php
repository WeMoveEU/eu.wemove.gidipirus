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
    try {
      $requestId = CRM_Gidipirus_Logic_Register::hasRequest($contactId);
      $setDate = CRM_Gidipirus_Logic_Register::setDateNow($requestId);
      $values[$contactId] = [
        'id' => $contactId,
        'result' => (int) $setDate,
      ];
    } catch (CRM_Gidipirus_Exception_NoFulfillment $exception) {
      $result = CRM_Gidipirus_Logic_Register::now($contactId, $channel, $requestedDate, $activityParentId);
      if ($result['result']) {
        CRM_Gidipirus_Logic_Email::holdEmails($contactId);
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
