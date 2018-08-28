<?php

class CRM_Gidipirus_Model {

  /**
   * Get or create new option value.
   *
   * @param string $optionGroupName
   * @param string $name
   * @param array $options
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  protected static function set($optionGroupName, $name, $options = []) {
    $params = array(
      'sequential' => 1,
      'option_group_id' => $optionGroupName,
      'name' => $name,
    );
    $result = civicrm_api3('OptionValue', 'get', $params);
    if ($result['count'] == 0) {
      $params['is_active'] = 1;
      $params['title'] = $name;
      $params = array_merge($params, $options);
      $result = civicrm_api3('OptionValue', 'create', $params);
    }
    return $result['values'][0]['value'];
  }

}
