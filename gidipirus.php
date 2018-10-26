<?php

require_once 'gidipirus.civix.php';
use CRM_Gidipirus_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function gidipirus_civicrm_config(&$config) {
  _gidipirus_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function gidipirus_civicrm_xmlMenu(&$files) {
  _gidipirus_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function gidipirus_civicrm_install() {
  _gidipirus_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function gidipirus_civicrm_postInstall() {
  _gidipirus_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function gidipirus_civicrm_uninstall() {
  _gidipirus_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function gidipirus_civicrm_enable() {
  _gidipirus_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function gidipirus_civicrm_disable() {
  _gidipirus_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function gidipirus_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _gidipirus_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function gidipirus_civicrm_managed(&$entities) {
  _gidipirus_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function gidipirus_civicrm_caseTypes(&$caseTypes) {
  _gidipirus_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function gidipirus_civicrm_angularModules(&$angularModules) {
  _gidipirus_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function gidipirus_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _gidipirus_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

function gidipirus_civicrm_links($op, $objectName, $objectId, &$links, &$mask, &$values) {
  if ('Contact' == $objectName) {
    switch ($op) {
      case 'contact.selector.actions':
        $links[] = array(
          'name' => E::ts('Forget Me'),
          'url' => 'civicrm/gidipirus/forgetme',
          'qs' => 'cid=%%id%%',
          'title' => 'Forget Me',
          'ref' => 'forgetme',
          'class' => 'no-popup',
          'bit' => 100000,
        );
        break;
    }
  }
}

function gidipirus_civicrm_summaryActions(&$actions, $contactID) {
  $actions['otherActions']['forgetme'] = [
    'title' => E::ts("Forget Me"),
    'weight' => 1000,
    'ref' => 'forgetme',
    'key' => 'forgetme',
    'href' => CRM_Utils_System::url('civicrm/gidipirus/forgetme', ['reset' => 1]),
    'icon' => 'crm-i fa-envelope-open',
    'permissions' => ['access CiviCRM']
  ];
}

/**
 * Implements hook_civicrm_pageRun().
 */
function gidipirus_civicrm_pageRun(&$page) {
  if (is_a($page, 'CRM_Activity_Page_Tab')) {
    CRM_Core_Resources::singleton()->addScriptFile(E::LONG_NAME, 'js/activity-links.js');
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function gidipirus_civicrm_navigationMenu(&$menu) {
  _gidipirus_civix_insert_navigation_menu($menu, NULL, array(
    'label' => E::ts('The Page'),
    'name' => 'the_page',
    'url' => 'civicrm/the-page',
    'permission' => 'access CiviReport,access CiviContribute',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _gidipirus_civix_navigationMenu($menu);
} // */
