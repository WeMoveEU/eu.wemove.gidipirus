<?php

class CRM_Gidipirus_Logic_Email {

  /**
   * Anonymise no billing emails
   *
   * @param $contactId
   * @param $emailTemplate
   *
   * @throws \Exception
   * @throws \CiviCRM_API3_Exception
   */
  public static function anonymize($contactId, $emailTemplate) {
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'contact_id' => $contactId,
      'location_type_id' => ['<>' => "Billing"],
    ]);
    if ($result['count']) {
      foreach ($result['values'] as $email) {
        civicrm_api3('Email', 'create', [
          'sequential' => 1,
          'id' => $email['id'],
          'email' => self::random($emailTemplate),
        ]);
      }
    }
  }

  /**
   * Hold on each email for contact (including Billing email)
   *
   * @param $contactId
   *
   * @throws \CiviCRM_API3_Exception
   */
  public static function holdEmails($contactId) {
    $result = civicrm_api3('Email', 'get', [
      'sequential' => 1,
      'contact_id' => $contactId,
    ]);
    if ($result['count']) {
      foreach ($result['values'] as $email) {
        civicrm_api3('Email', 'create', [
          'sequential' => 1,
          'id' => $email['id'],
          'on_hold' => 1,
        ]);
      }
    }
  }

  /**
   * @param $emailTemplate
   *
   * @return mixed
   * @throws \Exception
   */
  private static function random($emailTemplate) {
    return str_replace('%RANDOM%', CRM_Gidipirus_Model::hash(), $emailTemplate);
  }

}
