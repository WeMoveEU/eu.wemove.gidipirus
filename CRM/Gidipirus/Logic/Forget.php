<?php

class CRM_Gidipirus_Logic_Forget {

  /**
   * Anonymise all required data and completed request with current date
   *
   * @param int $contactId Contact Id
   * @param int $requestId Forgetme Fulfillment activity for given contact
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function forget($contactId, $requestId) {
    $tagId = CRM_Gidipirus_Model_Tag::forgottenId();
    CRM_Gidipirus_Model_Tag::add($contactId, $tagId);
    CRM_Gidipirus_Logic_Email::anonymize($contactId, CRM_Gidipirus_Settings::emailTemplate());

    return 0;
  }

}
