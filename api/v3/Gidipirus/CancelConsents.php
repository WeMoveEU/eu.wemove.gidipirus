<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_cancel_consents_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact id',
    'description' => 'CiviCRM id of the user',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['date'] = [
    'name' => 'date',
    'title' => 'Date',
    'description' => 'When the consent was updated by the user',
    'type' => CRM_Utils_Type::T_TIMESTAMP,
    'api.required' => 1,
  ];
  $spec['method'] = [
    'name' => 'method',
    'title' => 'Method',
    'Description' => 'How the user requested cancellation of consents',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => 'Campaign id',
    'description' => 'CiviCRM Id of the campaign that made the user update the consent',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 0,
    'api.default' => NULL,
  ];
  $spec['utm_source'] = [
    'name' => 'utm_source',
    'title' => 'utm_source',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => NULL,
  ];
  $spec['utm_campaign'] = [
    'name' => 'utm_campaign',
    'title' => 'utm_campaign',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => NULL,
  ];
  $spec['utm_medium'] = [
    'name' => 'utm_medium',
    'title' => 'utm_medium',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => NULL,
  ];
}

/**
 * Cancel all the currently confirmed consents of the contact (identified by contact_id)
 * The date and attribution parameters are used to record the cancellation event.
 * Return the ids of the cancelled consents.
 */
function civicrm_api3_gidipirus_cancel_consents($params) {
  $c = new CRM_Gidipirus_Logic_Consent();
  $attribution = new CRM_Gidipirus_Model_Attribution(
    $params['method'], $params['campaign_id'], $params['utm_source'], $params['utm_medium'], $params['utm_campaign']
  );
  $cancelledConsents = $c->cancelConsents($params['contact_id'], $params['date'], $attribution);
  $cancelledIds = array_map(function ($c) { return $c->id(); }, $cancelledConsents);
  return civicrm_api3_create_success($cancelledIds, $params);
}
