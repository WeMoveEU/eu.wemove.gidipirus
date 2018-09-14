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

    CRM_Gidipirus_Model_RequestChannel::installOptionGroup();
    CRM_Gidipirus_Model_RequestChannel::email();
    CRM_Gidipirus_Model_RequestChannel::phone();
    CRM_Gidipirus_Model_RequestChannel::personal();
    CRM_Gidipirus_Model_RequestChannel::paperLetter();
    CRM_Gidipirus_Model_RequestChannel::expired();

    CRM_Gidipirus_Settings::scheduledDays();

    return TRUE;
  }

}
