<?php
use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_get_consents_required_spec(&$spec) {
  $spec['email'] = [
    'name' => 'email',
    'title' => 'Email address of the user',
    'description' => 'Email address of the user',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['consent_ids'] = [
    'name' => 'consent_ids',
    'title' => 'List of consent ids',
    'description' => 'List of public ids (consent version + consent language)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['country'] = [
    'name' => 'country',
    'title' => 'Country code of the user',
    'description' => 'Country code of the user',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
}

/**
 * For the given contact (identified by email) and context (country), return which of the given consent_ids
 * are currently not confirmed by the contact and should therefore be requested when processing an action.
 * For each returned consent id, a property 'factor' indicates whether the consent can be requested synchronously with the action (value = 1)
 * or should be requested in a separate channel (value = 2)
 */
function civicrm_api3_gidipirus_get_consents_required($params) {
  $c = new CRM_Gidipirus_Logic_Consent();
  $result = ['consents_required' => $c->getRequiredConsentsSimplified($params['email'], $params['country'], $params['consent_ids'])];
  return civicrm_api3_create_success($result, $params);
}
