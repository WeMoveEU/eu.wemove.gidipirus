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
    CRM_Gidipirus_Model_Activity::forgetmeRequestId();
    CRM_Gidipirus_Model_Activity::forgetmeFulfillmentId();

    CRM_Gidipirus_Model_ForgetmeStatus::installOptionGroup();
    CRM_Gidipirus_Model_ForgetmeStatus::ready();
    CRM_Gidipirus_Model_ForgetmeStatus::inProgress();
    CRM_Gidipirus_Model_ForgetmeStatus::obsolete();
    CRM_Gidipirus_Model_ForgetmeStatus::completed();
    CRM_Gidipirus_Model_ForgetmeStatus::blocked();
    CRM_Gidipirus_Model_ForgetmeStatus::invalidRequest();
    CRM_Gidipirus_Model_ForgetmeStatus::tooManyRequests();


    return TRUE;
  }

}
