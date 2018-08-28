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

    return TRUE;
  }

}
