<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_status_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => E::ts('Contact Id'),
    'description' => E::ts('Contact Id'),
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
function civicrm_api3_gidipirus_status(&$params) {
  $start = microtime(TRUE);
  $contactId = $params['contact_id'];
  $query = "SELECT
              CASE
                WHEN af.status_id = 1 AND DATE_FORMAT(af.activity_date_time, '%Y-%m-%d') >= CURRENT_DATE THEN %4
                WHEN af.status_id = 1 AND DATE_FORMAT(af.activity_date_time, '%Y-%m-%d') < CURRENT_DATE THEN %5
                WHEN af.status_id = 2 THEN %6
                ELSE %7
              END forgetme_status
            FROM civicrm_activity ar
              JOIN civicrm_activity_contact acr ON acr.activity_id = ar.id AND acr.record_type_id = 3
              LEFT JOIN civicrm_activity af ON af.parent_id = ar.id AND af.activity_type_id = %3
            WHERE ar.activity_type_id = %2 AND acr.contact_id = %1";
  $queryParams = [
    1 => [$contactId, 'Integer'],
    2 => [CRM_Gidipirus_Model_Activity::forgetmeRequestId(), 'Integer'],
    3 => [CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId(), 'Integer'],
    4 => [CRM_Gidipirus_Model_ForgetmeStatus::IN_PROGRESS_VALUE, 'Integer'],
    5 => [CRM_Gidipirus_Model_ForgetmeStatus::OBSOLETE_VALUE, 'Integer'],
    6 => [CRM_Gidipirus_Model_ForgetmeStatus::COMPLETED_VALUE, 'Integer'],
    7 => [CRM_Gidipirus_Model_ForgetmeStatus::INVALID_REQUEST_VALUE, 'Integer'],
  ];
  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
  if ($dao->N > 1) {
    $forgetmeStatus = CRM_Gidipirus_Model_ForgetmeStatus::TOO_MANY_REQUESTS_VALUE;
  }
  elseif ($dao->N = 1) {
    $dao->fetch();
    $forgetmeStatus = $dao->forgetme_status;
  }
  else {
    // todo check if donor
    $forgetmeStatus = CRM_Gidipirus_Model_ForgetmeStatus::BLOCKED_VALUE;
    // todo check if alone fulfillment
    $forgetmeStatus = CRM_Gidipirus_Model_ForgetmeStatus::INVALID_REQUEST_VALUE;
  }
  $values = [
    $contactId => [
      'status' => $forgetmeStatus,
    ],
  ];
  $dao->free();
  $extraReturnValues = array(
    'time' => microtime(TRUE) - $start,
  );
  return civicrm_api3_create_success($values, $params, 'Gidipirus', 'status', $blank, $extraReturnValues);
}
