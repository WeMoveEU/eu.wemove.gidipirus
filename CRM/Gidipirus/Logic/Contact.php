<?php

class CRM_Gidipirus_Logic_Contact {

  const FORGOTTEN_FIRST_NAME = 'Forgotten';
  const FORGOTTEN_LAST_NAME = 'Contact';

  /**
   * @param $contactId
   *
   * @return bool
   * @throws \CiviCRM_API3_Exception
   */
  public static function clean($contactId) {
    $params = [
      'sequential' => 1,
      'id' => $contactId,
      'first_name' => self::FORGOTTEN_FIRST_NAME,
      'last_name' => self::FORGOTTEN_LAST_NAME,
      'middle_name' => '',
      'legal_name' => '',
      'nick_name' => '',
      'prefix_id' => '',
      'suffix_id' => '',
      'formal_title' => '',
      'communication_style_id' => '',
      'email_greeting_id' => 1,
      'postal_greeting_id' => 1,
      'postal_greeting_custom' => '',
      'addressee_id' => 1,
      'addressee_custom' => '',
      'job_title' => '',
    ];
    $result = civicrm_api3('Contact', 'create', $params);
    return !$result['is_error'];
  }

}
