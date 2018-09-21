<?php

class CRM_Gidipirus_Logic_Address {

  const LOCATION_TYPE = 'Home';

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
      'location_type_id' => self::LOCATION_TYPE,
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
          'supplemental_address_1' => '',
          'supplemental_address_2' => '',
          'supplemental_address_3' => '',
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
      'location_type_id' => self::LOCATION_TYPE,
    ];
    $result = civicrm_api3('Address', 'get', $params);
    if ($result['count'] > 1) {
      $adrPerCountry = [];
      $adrDelete = [];
      foreach ($result['values'] as $adr) {
        $adrPerCountry[$adr['country_id']][] = $adr['id'];
      }
      foreach ($adrPerCountry as $country) {
        if (count($country) > 1) {
          unset($country[0]);
          $adrDelete = array_merge($adrDelete, $country);
        }
      }
      if ($adrDelete) {
        self::delete($adrDelete);
      }
    }
  }

  /**
   * @param $addressIds
   *
   * @throws \CiviCRM_API3_Exception
   */
  private static function delete($addressIds) {
    foreach ($addressIds as $id) {
      $params = [
        'sequential' => 1,
        'id' => $id,
      ];
      civicrm_api3('Address', 'delete', $params);
    }
  }

}
