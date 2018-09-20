<?php

class CRM_Gidipirus_Logic_Address {

  /**
   * @param $contactId
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function clean($contactId) {
    $params = [
      'sequential' => 1,
      'contact_id' => $contactId,
      'location_type_id' => 'Home',
    ];
    $result = civicrm_api3('Address', 'get', $params);
    if ($result['count']) {
      foreach ($result['values'] as $address) {
        $params = [
          'sequential' => 1,
          'id' => $address['id'],
          'street_address' => '',
          'city' => '',
          'county_id' => '',
          'state_province_id' => '',
          'postal_code' => '',
          'geo_code_1' => '',
          'geo_code_2' => '',
        ];
        civicrm_api3('Address', 'create', $params);
      }
    }

    return (int) $result['count'];
  }

  /**
   * @param $contactId
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function dedupe($contactId) {
    $params = [
      'sequential' => 1,
      'contact_id' => $contactId,
      'location_type_id' => 'Home',
    ];
    $result = civicrm_api3('Address', 'get', $params);
    // todo find addresses with the same country
    // todo delete additional addresses
  }

}
