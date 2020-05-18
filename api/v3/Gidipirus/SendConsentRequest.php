<?php

use CRM_Gidipirus_ExtensionUtil as E;

function _civicrm_api3_gidipirus_send_consent_request_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => E::ts('Contact Id'),
    'description' => E::ts('Contact Id'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => E::ts('Campaign Id'),
    'description' => E::ts('Campaign Id from which will be consent id, language and others variables'),
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'api.default' => '',
  ];
  $spec['utm_source'] = [
    'name' => 'utm_source',
    'title' => ts('utm source'),
    'description' => 'utm source',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['utm_medium'] = [
    'name' => 'utm_medium',
    'title' => ts('utm medium'),
    'description' => 'utm medium',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
  $spec['utm_campaign'] = [
    'name' => 'utm_campaign',
    'title' => ts('utm campaign'),
    'description' => 'utm campaign',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 0,
    'api.default' => '',
  ];
}

/**
 * Send a request by email to contact for accepting or rejecting consent.
 */
function civicrm_api3_gidipirus_send_consent_request(&$params) {
  $contactId = $params['contact_id'];
  $campaignId = $params['campaign_id'];
  $utmSource = $params['utm_source'];
  $utmMedium = $params['utm_medium'];
  $utmCampaign = $params['utm_campaign'];

  $consentLogic = new CRM_Gidipirus_Logic_Consent();
  $attribution = new CRM_Gidipirus_Model_Attribution(
    'email', $params['campaign_id'], $params['utm_source'], $params['utm_medium'], $params['utm_campaign']
  );
  $email = $consentLogic->getConfirmationEmail($contactId, $attribution);
  try {
    $sent = CRM_Utils_Mail::send($email);
    if ($sent) {
      $camp = new CRM_Gidipirus_Model_Campaign($campaignId);
      foreach ($camp->getConsentIds() as $consentId) {
        CRM_Core_Error::debug_var('consentid', $consentId);
        $consent = CRM_Gidipirus_Model_Consent::fromId($consentId, 'Pending', date('Y-m-d H:i:s'));
        $sent = $sent && $consentLogic->addConsent($contactId, $consent, FALSE, $attribution);
      }
    }
    return civicrm_api3_create_success($sent, $params);
  }
  catch (CiviCRM_API3_Exception $exception) {
    $data = array(
      'params' => $params,
      'email' => $email,
      'exception' => $exception,
    );
    return civicrm_api3_create_error('Problem with sending the confirmation email', $data);
  }
}

