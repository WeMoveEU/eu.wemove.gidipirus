<?php

use CRM_Gidipirus_ExtensionUtil as E;

class CRM_Gidipirus_Page_Tab extends CRM_Core_Page {

  /**
   * @return null|void
   * @throws \CRM_Core_Exception
   */
  public function run() {
    CRM_Utils_System::setTitle(E::ts('Data processing'));
    $contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->assign('extensionKey', E::LONG_NAME);
    $this->assign('contactId', $contactId);
    parent::run();
  }

}
