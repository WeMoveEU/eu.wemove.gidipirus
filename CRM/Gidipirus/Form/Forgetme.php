<?php

use CRM_Gidipirus_ExtensionUtil as E;
require_once 'CRM/Core/Form.php';

class CRM_Gidipirus_Form_Forgetme extends CRM_Core_Form {

  private $buttons = [];
  private $fields = [];
  private $contactId;
  private $activityId;
  private $subName;
  private $statusId;

  public function __construct($state = NULL, $action = CRM_Core_Action::NONE, $method = 'post', $name = NULL) {
    CRM_Utils_System::setTitle(E::ts('Data processing'));
    $this->fields = [
      'request_date' => [
        'type' => 'datepicker',
        'label' => E::ts("Request date"),
        'options' => [],
        'required' => TRUE,
        'extra' => array('time' => FALSE, 'date' => 'yy-mm-dd'),
        'default' => date('Y-m-d'),
        'order' => 10,
      ],
      'request_channel' => [
        'type' => 'Select',
        'label' => E::ts("Request channel"),
        'options' => CRM_Gidipirus_Model_RequestChannel::$values,
        'required' => TRUE,
        'default' => '',
        'order' => 20,
      ],
    ];
    $this->buttons = [
      [
        'type' => 'submit',
        'name' => ts('Register forget request'),
        'isDefault' => TRUE,
        'icon' => 'fa-envelope-open',
        'subName' => 'register',
      ],
      [
        'type' => 'done',
        'name' => ts('Force forget'),
        'isDefault' => FALSE,
        'icon' => 'fa-eraser',
        'subName' => 'forget',
      ],
    ];
    parent::__construct($state, $action, $method, $name);
  }

  /**
   * @throws \CRM_Core_Exception
   * @throws \CiviCRM_API3_Exception
   */
  public function preProcess() {
    $this->contactId = CRM_Utils_Request::retrieve('cid', 'Positive', $this, TRUE);
    $this->activityId = CRM_Utils_Request::retrieve('aid', 'Positive', $this, FALSE);
    $result = civicrm_api3('Gidipirus', 'status', array(
      'sequential' => 1,
      'contact_id' => $this->contactId,
    ));
    $this->statusId = (int) $result['values'][0]['status'];
    $this->subName = $this->controller->_actionName[1];

    $this->assign('statusId', $this->statusId);
    $this->assign('subName', $this->subName);
    $this->assign('forgetmeStatus', CRM_Gidipirus_Model_ForgetmeStatus::$nameToValue);
    $this->assign('extensionKey', E::LONG_NAME);
  }

  public function buildQuickForm() {
    foreach ($this->fields as $key => $field) {
      $this->add($field['type'], $key, $field['label'], ['' => '- select -'] + $field['options'], $field['required'], @$field['extra']);
    }
    $this->addButtons($this->buttons);
    parent::buildQuickForm();
  }

  public function setDefaultValues() {
    $defaults = array();
    foreach ($this->fields as $key => $setting) {
      if (array_key_exists('default', $setting)) {
        $defaults[$key] = $setting['default'];
      }
    }
    return $defaults;
  }

  public function postProcess() {
    switch ($this->subName) {
      case 'submit':
        break;

      case 'done':
        break;
    }

    CRM_Core_Session::singleton()->pushUserContext(CRM_Utils_System::url('civicrm/gidipirus/forgetme', ['cid' => $this->contactId]));
  }

}
