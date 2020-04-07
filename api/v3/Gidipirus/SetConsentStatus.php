<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_set_consent_status_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact id',
    'description' => 'CiviCRM id of the user',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['consent_id'] = [
    'name' => 'consent_id',
    'title' => 'Consent id',
    'description' => 'Id of the consent being updated by the user',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['status'] = [
    'name' => 'status',
    'title' => 'Status',
    'description' => 'New status of the consent',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => CRM_Gidipirus_Model_Consent::statusOptions(),
    'api.required' => 1,
  ];
  $spec['date'] = [
    'name' => 'date',
    'title' => 'Date',
    'description' => 'When the consent was updated by the user',
    'type' => CRM_Utils_Type::T_TIMESTAMP,
    'api.required' => 1,
  ];
  $spec['is_member'] = [
    'name' => 'is_member',
    'title' => 'Is currently member',
    'description' => 'Whether the user was a member before changing the consent status.',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.required' => 1,
  ];
  $spec['method'] = [
    'name' => 'method',
    'title' => 'Method',
    'Description' => 'How the user requested change of consent status',
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
 * Store the fact that a contact (identified by contact_id) has been requested or has answered a consent (identified by consent_id).
 * Values for status:
 *  - Pending: the consent was requested to the contact asynchronously, and is awaiting an answer
 *  - Confirmed: the consent is accepted by the contact
 *  - Rejected: the consent is not accepted by the contact
 *  - Cancelled: the previously accepted consent is withdrawn by the contact
 */
function civicrm_api3_gidipirus_set_consent_status($params) {
  $c = new CRM_Gidipirus_Logic_Consent();
  $consent = CRM_Gidipirus_Model_Consent::fromId($params['consent_id'], $params['status'], $params['date']);
  $attribution = new CRM_Gidipirus_Model_Attribution(
    $params['method'], $params['campaign_id'], $params['utm_source'], $params['utm_medium'], $params['utm_campaign']
  );
  if ($c->addConsent($params['contact_id'], $consent, $params['is_member'], $attribution)) {
    return civicrm_api3_create_success(NULL, $params);
  } else {
    return civicrm_api3_create_error("Unknown error", $params);
  }
}
