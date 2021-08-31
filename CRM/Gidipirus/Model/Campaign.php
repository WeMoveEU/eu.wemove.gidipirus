<?php

class CRM_Gidipirus_Model_Campaign {

  public $fieldLanguage;

  public $fieldSenderMail;

  public $fieldSubjectNew;

  public $fieldSubjectCurrent;

  public $fieldMessageNew;

  public $fieldConsentIds;

  public $fieldRedirectConfirm;

  public $fieldRedirectOptout;

  private $campArray;

  function __construct($campaignId) {
    $this->fieldLanguage = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_language');
    $this->fieldSenderMail = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_sender_mail');
    $this->fieldSubjectNew = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_subject_new');
    $this->fieldSubjectCurrent = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_subject_current');
    $this->fieldMessageNew = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_message_new');
    $this->fieldConsentIds = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_campaign_consent_ids');
    $this->fieldRedirectConfirm = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_redirect_confirm');
    $this->fieldRedirectOptout = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_redirect_optout');
    $this->campArray = $this->getCiviCampaign($campaignId);
  }

  public function getCiviCampaign($campaign_id) {
    // UGLINESS WARNING
    // WeAct depends on Gidipirus so we cannot benefit from CRM_WeAct_CampaignCache without introducing a circular dependency
    // Instead we use the same cache key, so we that we don't duplicate cache entries for WeAct and Gidipirus
    // A cleaner approach would be to use a different key and implement hooks to update the cache on campaign updates, or have the cache in its own extension
    $key = "WeAct:Campaign:{$campaign_id}";
    $entry = Civi::cache()->get($key);
    if (!$entry) {
      $entry = civicrm_api3('Campaign', 'getsingle', ['id' => $campaign_id]);
      $this->setCiviCampaign($entry);
    }
    return $entry;
  }

  public function setCiviCampaign($campaign) {
    $key = "WeAct:Campaign:{$campaign['id']}";
    Civi::cache()->set($key, $campaign);
  }

  public function getLanguage() {
    return $this->campArray[$this->fieldLanguage];
  }

  public function getSenderMail() {
    return $this->campArray[$this->fieldSenderMail];
  }

  public function getSubjectNew() {
    return $this->campArray[$this->fieldSubjectNew];
  }

  public function getSubjectCurrent() {
    return $this->campArray[$this->fieldSubjectCurrent];
  }

  public function getMessageNew() {
    return $this->campArray[$this->fieldMessageNew];
  }

  public function getConsentIds() {
    return explode(',', $this->campArray[$this->fieldConsentIds]);
  }

  public function getRedirectConfirm() {
		return $this->campArray[$this->fieldRedirectConfirm];
  }

  public function getRedirectOptout() {
		return $this->campArray[$this->fieldRedirectOptout];
  }
}
