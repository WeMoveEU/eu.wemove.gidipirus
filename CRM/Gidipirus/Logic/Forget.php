<?php

class CRM_Gidipirus_Logic_Forget {

  /**
   * Anonymise all required data of contact
   *
   * @param int $contactId Contact Id
   *
   * @return int
   * @throws \CiviCRM_API3_Exception
   */
  public static function anonymise($contactId) {
    $tagId = CRM_Gidipirus_Model_Tag::forgottenId();
    CRM_Gidipirus_Model_Tag::add($contactId, $tagId);
    CRM_Gidipirus_Logic_Email::anonymize($contactId, CRM_Gidipirus_Settings::emailTemplate());
    CRM_Gidipirus_Logic_Contact::clean($contactId);
    $addresses = CRM_Gidipirus_Logic_Address::clean($contactId);
    if ($addresses > 1) {
      CRM_Gidipirus_Logic_Address::dedupe($contactId);
    }
    CRM_Gidipirus_Logic_Phone::anonymize($contactId);
    CRM_Gidipirus_Logic_Activity::clean($contactId);

    return 1;
  }

}
