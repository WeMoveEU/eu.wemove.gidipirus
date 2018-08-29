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
  protected static function optionValue($optionGroupName, $name, $options = []) {
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

  /**
   * Get or create new option group.
   *
   * @param string $title
   * @param array $options
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  protected static function optionGroup($title, $options = []) {
    $params = array(
      'sequential' => 1,
      'name' => self::sanitize($title),
    );
    $result = civicrm_api3('OptionGroup', 'get', $params);
    if ($result['count'] == 0) {
      $params['is_active'] = 1;
      $params['title'] = $title;
      $params = array_merge($params, $options);
      $result = civicrm_api3('OptionGroup', 'create', $params);
    }
    return $result['id'];

  }

  /**
   * @param $title
   *
   * @return mixed
   */
  protected static function sanitize($title) {
    return str_replace([' ', '.', ','], '_', strtolower($title));
  }

}
