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
          'on_hold' => 1,
          'email' => self::random($emailTemplate),
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
    return str_replace('%RANDOM%', self::hash(), $emailTemplate);
  }

  /**
   * @param int $lenght
   *
   * @return bool|string
   * @throws \Exception
   */
  private static function hash($lenght = 15) {
    if (function_exists("random_bytes")) {
      $bytes = random_bytes(ceil($lenght / 2));
    }
    elseif (function_exists("openssl_random_pseudo_bytes")) {
      $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
    }
    else {
      throw new Exception("no cryptographically secure random function available");
    }

    return substr(bin2hex($bytes), 0, $lenght);
  }

}
