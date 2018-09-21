<?php

class CRM_Gidipirus_Logic_Phone {

  /**
   * Anonymise all phones
   *
   * @param $contactId
   *
   * @throws \Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function anonymize($contactId) {
    $result = civicrm_api3('Phone', 'get', [
      'sequential' => 1,
      'contact_id' => $contactId,
    ]);
    if ($result['count']) {
      foreach ($result['values'] as $phone) {
        civicrm_api3('Phone', 'create', [
          'sequential' => 1,
          'id' => $phone['id'],
          'phone' => CRM_Gidipirus_Model::hash(12),
          'phone_ext' => '',
        ]);
      }
    }
  }

}
