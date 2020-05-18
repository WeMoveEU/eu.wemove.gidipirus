<?php

class CRM_Gidipirus_Page_Optout extends CRM_Gidipirus_Page_ConsentEmail {

  /**
   * @return void
   * @throws \CiviCRM_API3_Exception
   */
  public function run() {
    $this->setValues();
    $campaign = new CRM_Gidipirus_Model_Campaign($this->campaignId);

    $location = '';
    if ($this->isGroupContactAdded($this->contactId, $this->memberGroupId)) {
      $location = 'removed from Members after optout link';
      civicrm_api3('Gidipirus', 'cancel_consents', ['contact_id' => $this->contactId, 'date' => date('Y-m-d H:i:s'), 'method' => 'confirmation_link']);
    }

    $locale = $campaign->getLanguage();
    $language = substr($locale, 0, 2);
    $this->setLanguageTag($this->contactId, $language);
    civicrm_api3('Contact', 'create', ['id' => $this->contactId, 'is_opt_out' => 1]);
    
    // Either the contact was member and cancel_consents was called, or the contact was not: in both cases it is not a member at this stage
    $this->setConsentStatus($campaign->getConsentIds(), 'Rejected', FALSE);
    $this->setActivityStatus($this->activityId, 'optout', $location);

    $redirect = $campaign->getRedirectOptout();
    $url = $this->determineRedirectUrl('post_optout', $language, $redirect);
    CRM_Utils_System::redirect($url);
  }

}
