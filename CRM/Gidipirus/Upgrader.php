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

    CRM_Gidipirus_Settings::membersGroupId();
    CRM_Gidipirus_Settings::scheduledDays();
    CRM_Gidipirus_Settings::emailTemplate();
    CRM_Gidipirus_Settings::scannedActivitiesId();

    $this->upgrade_01_cleanup_job();

    return TRUE;
  }

  /**
   * @throws \CiviCRM_API3_Exception
   */
  public function upgrade_01_cleanup_job() {
    $result = civicrm_api3('Job', 'get', [
      'sequential' => 1,
      'api_entity' => "Gidipirus",
      'api_action' => "cleanup",
    ]);
    if ($result['count']) {
      foreach ($result['values'] as $v) {
        civicrm_api3('Job', 'delete', [
          'sequential' => 1,
          'id' => $v['id'],
        ]);
      }
    }

    $params = [
      'sequential' => 1,
      'api_entity' => "Gidipirus",
      'api_action' => "cleanup",
      'run_frequency' => "Hourly",
      'parameters' => "channels=" . implode(',', array_keys(CRM_Gidipirus_Model_RequestChannel::$values)),
      'name' => "Forget ALL contacts (except expired) that are due to be forgotten",
      'is_active' => 0,
    ];
    civicrm_api3('Job', 'create', $params);

    $params = [
      'sequential' => 1,
      'api_entity' => "Gidipirus",
      'api_action' => "cleanup",
      'run_frequency' => "Daily",
      'parameters' => "channels=" . CRM_Gidipirus_Model_RequestChannel::EXPIRED,
      'name' => "Forget ONLY EXPIRED contacts that are due to be forgotten",
      'is_active' => 0,
    ];
    civicrm_api3('Job', 'create', $params);

    $params = [
      'sequential' => 1,
      'api_entity' => "Gidipirus",
      'api_action' => "scan",
      'run_frequency' => "Daily",
      'parameters' => "limit=1000",
      'name' => "Scan inactive members and mark them as expired",
      'is_active' => 0,
    ];
    civicrm_api3('Job', 'create', $params);

    return TRUE;
  }

}
