<?php
use CRM_Gidipirus_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Gidipirus_Upgrader extends CRM_Gidipirus_Upgrader_Base {

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function install() {
    CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();

    CRM_Gidipirus_Model_ForgetmeStatus::installOptionGroup();
    CRM_Gidipirus_Model_ForgetmeStatus::ready();
    CRM_Gidipirus_Model_ForgetmeStatus::inProgress();
    CRM_Gidipirus_Model_ForgetmeStatus::obsolete();
    CRM_Gidipirus_Model_ForgetmeStatus::completed();
    CRM_Gidipirus_Model_ForgetmeStatus::tooManyRequests();
    CRM_Gidipirus_Model_ForgetmeStatus::notApplicable();

    CRM_Gidipirus_Model_RequestChannel::installOptionGroup();
    CRM_Gidipirus_Model_RequestChannel::email();
    CRM_Gidipirus_Model_RequestChannel::phone();
    CRM_Gidipirus_Model_RequestChannel::personal();
    CRM_Gidipirus_Model_RequestChannel::paperLetter();
    CRM_Gidipirus_Model_RequestChannel::expired();
    CRM_Gidipirus_Model_RequestChannel::thirdPartyMailjet();

    CRM_Gidipirus_Settings::membersGroupId();
    CRM_Gidipirus_Settings::scheduledDays();
    CRM_Gidipirus_Settings::emailTemplate();
    CRM_Gidipirus_Settings::scannedActivitiesId();

    $this->upgrade_01_cleanup_job();

    $this->installGdprCustomFields();

    return TRUE;
  }

  public function installGdprCustomFields() {
		$result = civicrm_api3('CustomGroup', 'get', [ 'name' => "GDPR_temporary" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomGroup', 'create', [
        'title' => "GDPR temporary",
        'extends' => "Individual",
        'name' => "GDPR_temporary",
        'table_name' => "civicrm_value_gdpr_temporary",
      ]);
    }

    $result = civicrm_api3('CustomField', 'get', [ 'name' => "Consent_date", 'custom_group_id' => "GDPR_temporary" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "GDPR_temporary",
        'label' => "Consent date",
        'name' => "Consent_date",
        'column_name' => "consent_date",
        'data_type' => "Date",
        'html_type' => "Select Date",
        'is_view' => 0,
      ]);
    }

    $result = civicrm_api3('CustomField', 'get', [ 'name' => "Consent_version", 'custom_group_id' => "GDPR_temporary" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "GDPR_temporary",
        'label' => "Consent version",
        'name' => "Consent_version",
        'column_name' => "consent_version",
        'data_type' => "String",
        'html_type' => "Text",
        'is_view' => 0,
				'text_length' => 32
      ]);
    }

    $result = civicrm_api3('CustomField', 'get', [ 'name' => "campaign_id", 'custom_group_id' => "GDPR_temporary" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "GDPR_temporary",
        'label' => "Campaign id",
        'name' => "campaign_id",
        'column_name' => "campaign_id",
        'data_type' => "Int",
        'html_type' => "Text",
        'is_view' => 0,
      ]);
    }

    $result = civicrm_api3('CustomField', 'get', [ 'name' => "Consent_language", 'custom_group_id' => "GDPR_temporary" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "GDPR_temporary",
        'label' => "Consent language",
        'name' => "Consent_language",
        'column_name' => "consent_language",
        'data_type' => "String",
        'html_type' => "Text",
        'is_view' => 0,
        'text_length' => 2,
      ]);
    }

    $result = civicrm_api3('CustomField', 'get', [ 'name' => "utm_source", 'custom_group_id' => "GDPR_temporary" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "GDPR_temporary",
        'label' => "UTM source",
        'name' => "utm_source",
        'column_name' => "utm_source",
        'data_type' => "String",
        'html_type' => "Text",
        'is_view' => 0,
        'text_length' => 255,
      ]);
    }

    $result = civicrm_api3('CustomField', 'get', [ 'name' => "utm_medium", 'custom_group_id' => "GDPR_temporary" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "GDPR_temporary",
        'label' => "UTM medium",
        'name' => "utm_medium",
        'column_name' => "utm_medium",
        'data_type' => "String",
        'html_type' => "Text",
        'is_view' => 0,
        'text_length' => 255,
      ]);
    }

    $result = civicrm_api3('CustomField', 'get', [ 'name' => "utm_campaign", 'custom_group_id' => "GDPR_temporary" ]);
    if ($result['count'] == 0) {
      civicrm_api3('CustomField', 'create', [
        'custom_group_id' => "GDPR_temporary",
        'label' => "UTM campaign",
        'name' => "utm_campaign",
        'column_name' => "utm_campaign",
        'data_type' => "String",
        'html_type' => "Text",
        'is_view' => 0,
        'text_length' => 255,
      ]);
    }
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function upgrade_01_cleanup_job() {
    $parameters = [
      'channels=' . implode(',', array_keys(CRM_Gidipirus_Model_RequestChannel::$values)),
      'limit=500',
    ];
    $params = [
      'sequential' => 1,
      'api_entity' => "Gidipirus",
      'api_action' => "cleanup",
      'run_frequency' => "Hourly",
      'parameters' => implode("\n", $parameters),
      'name' => "Forget ALL contacts (except expired) that are due to be forgotten",
      'is_active' => 0,
    ];
    civicrm_api3('Job', 'create', $params);

    $parameters = [
      'channels=' . CRM_Gidipirus_Model_RequestChannel::EXPIRED,
      'limit=500',
    ];
    $params = [
      'sequential' => 1,
      'api_entity' => "Gidipirus",
      'api_action' => "cleanup",
      'run_frequency' => "Daily",
      'parameters' => implode("\n", $parameters),
      'name' => "Forget ONLY EXPIRED contacts that are due to be forgotten",
      'is_active' => 0,
    ];
    civicrm_api3('Job', 'create', $params);

    $params = [
      'sequential' => 1,
      'api_entity' => "Gidipirus",
      'api_action' => "scan",
      'run_frequency' => "Daily",
      'parameters' => "limit=500",
      'name' => "Scan inactive members and mark them as expired",
      'is_active' => 0,
    ];
    civicrm_api3('Job', 'create', $params);

    return TRUE;
  }

}
