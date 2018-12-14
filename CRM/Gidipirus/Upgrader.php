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
    CRM_Gidipirus_Model_ForgetmeStatus::blocked();
    CRM_Gidipirus_Model_ForgetmeStatus::tooManyRequests();
    CRM_Gidipirus_Model_ForgetmeStatus::notApplicable();

    CRM_Gidipirus_Model_RequestChannel::installOptionGroup();
    CRM_Gidipirus_Model_RequestChannel::email();
    CRM_Gidipirus_Model_RequestChannel::phone();
    CRM_Gidipirus_Model_RequestChannel::personal();
    CRM_Gidipirus_Model_RequestChannel::paperLetter();
    CRM_Gidipirus_Model_RequestChannel::expired();

    CRM_Gidipirus_Settings::scheduledDays();

    $params = [
      'sequential' => 1,
      'api_entity' => "Gidipirus",
      'api_action' => "cleanup",
      'run_frequency' => "Hourly",
      'parameters' => "",
      'name' => "Forget all contacts that are due to be forgotten (based on activity_date_time)",
      'is_active' => 0,
    ];
    civicrm_api3('Job', 'create', $params);

    return TRUE;
  }

}
