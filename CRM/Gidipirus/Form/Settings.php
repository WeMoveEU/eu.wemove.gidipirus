<?php

use CRM_Gidipirus_ExtensionUtil as E;
require_once 'CRM/Core/Form.php';

/**
 * Form controller class
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Gidipirus_Form_Settings extends CRM_Core_Form {
  public $fields = [];

  public function __construct($state = NULL, $action = CRM_Core_Action::NONE, $method = 'post', $name = NULL) {
    $this->fields = [
      'scheduled_days' => [
        'type' => 'text',
        'label' => E::ts("Scheduled days"),
        'options' => [],
        'required' => TRUE,
        'default' => CRM_Gidipirus_Settings::scheduledDays(),
        'order' => 10,
      ],
      'email_template' => [
        'type' => 'text',
        'label' => E::ts("Email template"),
        'options' => [],
        'required' => TRUE,
        'default' => CRM_Gidipirus_Settings::emailTemplate(),
        'order' => 20,
      ],
    ];
    parent::__construct($state, $action, $method, $name);
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

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(E::ts('Gidipirus Settings Page'));
    foreach ($this->fields as $key => $field) {
      $this->add($field['type'], $key, $field['label'], ['' => '- select -'] + $field['options'], $field['required'], @$field['extra']);
    }
    $this->addButtons([['type' => 'submit', 'name' => ts('Submit'), 'isDefault' => TRUE]]);
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    CRM_Gidipirus_Settings::scheduledDays($values['scheduled_days']);
    CRM_Gidipirus_Settings::emailTemplate($values['email_template']);
    CRM_Core_Session::setStatus(E::ts('Settings are updated'), 'Gidipirus', 'success');
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  private function getRenderableElementNames() {
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
