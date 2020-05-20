<?php

require_once 'CRM/Core/Page.php';

class CRM_Gidipirus_Page_Confirm extends CRM_Gidipirus_Page_ConsentEmail {

  /**
   * Register consent confirmation and set activity as optin if applicable
   *
   * @throws \Exception
   */
  public function run() {
    $this->setValues();
    $campaign = new CRM_Gidipirus_Model_Campaign($this->campaignId);
    $contact = civicrm_api3('Contact', 'getsingle', ['id' => $this->contactId, 'return' => ['email', 'on_hold', 'country']]);

    if ($contact['on_hold'] != 0) {
      $this->unholdEmail($contact['email_id']);
    }

		$locale = $campaign->getLanguage();
		$language = substr($locale, 0, 2);
		$this->setLanguageTag($this->contactId, $language);

    $contactParams = $this->getContactMemberParams();
		// HACK to avoid a DB call to retrieve country code: 
		// It does not matter which, as long as it is not GB, so we just hardcode DE
		$countryCode = $contact['country'] == 'United Kingdom' ? 'GB' : 'DE';
		$rlg = $this->setLanguageGroup($this->contactId, $language, $countryCode);
		if ($rlg == 1) {
			$contactParams['preferred_language'] = $locale;
		}
    civicrm_api3('Contact', 'create', $contactParams);

    $isMember = $this->isGroupContactAdded($this->contactId, $this->memberGroupId);
    $this->setConsentStatus($campaign->getConsentIds(), 'Confirmed', $isMember);
    $this->setActivityStatus($this->activityId, 'optin');

    // TODO move this into a hook
    $speakcivi = new CRM_Speakcivi_Page_Speakcivi();
    $speakcivi->sendConfirm($contact['email'], $this->contactId, $this->activityId, $this->campaignId, FALSE, FALSE, 'post-confirm');

    $this->redirect($campaign, 'post_confirm');
  }

}
