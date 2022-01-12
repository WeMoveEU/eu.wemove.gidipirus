<?php

use CRM_Gidipirus_ExtensionUtil as E;

class CRM_Gidipirus_Logic_Consent {

  private static $dpaType;
  private static $joinType;
  private static $leaveType;
  private static $consentStatuses;
  private static $customFields;

  /**
   * Simplified version og getRequiredConsents based on whether the contact belongs to the Members group,
   * instead of looking at consents history and evaluating each consent independently
   */
  public function getRequiredConsentsSimplified($email, $country, $consent_ids) {
    $query = "
      SELECT 1 AS is_member
      FROM civicrm_group_contact gc
      JOIN civicrm_email e ON gc.contact_id = e.contact_id AND e.is_primary
      WHERE gc.status = 'Added' AND gc.group_id = %2
        AND e.email = %1;
    ";
    $queryParams = [
      1 => [$email, 'String'],
      2 => [CRM_Gidipirus_Settings::membersGroupId(), 'Integer'],
    ];
    $isMember = FALSE;
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    while ($dao->fetch()) {
      $isMember = TRUE;
    }

    $requiredConsents = [];
    if (!$isMember) {
      if (in_array($country, ['de', 'at'])) {
        $factors = 2;
      } else {
        $factors = 1;
      }

      foreach ($consent_ids as $consentId) {
        $requiredConsents[] = [ 'consent_id' => $consentId, 'factors' => $factors ];
      }
    }

    return $requiredConsents;
  }

  /**
   * c.f. API doc of get_consents_required
   */
  public function getRequiredConsents($email, $country, $consent_ids) {
    $query = "
        SELECT consent_version active_consent_version
        FROM
          (SELECT
            consent_version, max(completed_date) max_completed_date, max(cancelled_date) max_cancelled_date
          FROM
            (SELECT
              a.subject consent_version,
              if(a.status_id = %3, max(a.activity_date_time), '1970-01-01') completed_date,
              if(a.status_id = %4, max(a.activity_date_time), '1970-01-01') cancelled_date
            FROM civicrm_email e
              JOIN civicrm_activity_contact ac ON ac.contact_id = e.contact_id
              JOIN civicrm_activity a ON a.id = ac.activity_id AND a.activity_type_id = %2
            WHERE e.email = %1 AND e.is_primary = 1 AND a.status_id IN (%3, %4)
            GROUP BY consent_version, a.status_id) t
          GROUP BY consent_version) t2
        WHERE max_completed_date > max_cancelled_date
    ";
    $queryParams = [
      1 => [$email, 'String'],
      2 => [self::consentActivityType(), 'Integer'],
      3 => [self::consentActivityStatus('Confirmed'), 'Integer'],
      4 => [self::consentActivityStatus('Cancelled'), 'Integer']
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);
    $activeConsentVersions = [];
    while ($dao->fetch()) {
      $version = $this->getComparableVersion($dao->active_consent_version);
      $activeConsentVersions[] = $version;
      //If this is a partner consent, add the major version in active versions
      if (strpos($version, '_')) {
        $activeConsentVersions[] = explode('.', $version)[0];
      }
    }

    $requiredConsents = [];
    if (in_array($country, ['de', 'at'])) {
      $factors = 2;
    } else {
      $factors = 1;
    }

    foreach ($consent_ids as $consentId) {
      if (!in_array($this->getComparableVersion($consentId), $activeConsentVersions)) {
        $requiredConsents[] = [ 'consent_id' => $consentId, 'factors' => $factors ];
      }
    }

    return $requiredConsents;
  }

  public function getConfirmationEmail($contactId, $attribution) {
    $campaignId = $attribution->campaignId;
    $campaign = new CRM_Gidipirus_Model_Campaign($campaignId);
    $locale = $campaign->getLanguage();
    $email['from'] = $campaign->getSenderMail();
    $email['format'] = NULL;

    $contact = [];
    $paramsContact = [
      'id' => $contactId,
      'sequential' => 1,
      'return' => ["id", "display_name", "first_name", "last_name", "hash", "email", "email_greeting"],
    ];
    $result = civicrm_api3('Contact', 'get', $paramsContact);
    if ($result['count'] == 1) {
      $contact = $result['values'][0];
      $contact['checksum'] = CRM_Contact_BAO_Contact_Utils::generateChecksum($contactId, NULL, NULL, $contact['hash']);
      $email['toEmail'] = $contact['email'];
    }

    $hash = sha1(CIVICRM_SITE_KEY . $contactId);
    $baseAcceptUrl = 'civicrm/consent/confirm';
    $baseRejectUrl = 'civicrm/consent/optout';
    $utmSource = $attribution->source;
    $utmMedium = $attribution->medium;
    $utmCampaign = $attribution->campaign;

    $urlAccept = CRM_Utils_System::url($baseAcceptUrl,
      "id=$contactId&cid=$campaignId&hash=$hash&utm_source=$utmSource&utm_medium=$utmMedium&utm_campaign=$utmCampaign", TRUE);
    $urlReject = CRM_Utils_System::url($baseRejectUrl,
      "id=$contactId&cid=$campaignId&hash=$hash&utm_source=$utmSource&utm_medium=$utmMedium&utm_campaign=$utmCampaign", TRUE);

    $template = CRM_Core_Smarty::singleton();
    $template->assign('url_confirm_and_keep', $urlAccept);
    $template->assign('url_confirm_and_not_receive', $urlReject);
    $template->assign('contact', $contact);

    $email['subject'] = $template->fetch('string:' . $campaign->getSubjectNew());

    $message = $campaign->getMessageNew();
    if (!$message) {
      $message = CRM_Speakcivi_Tools_Dictionary::getMessageNew($locale);
    }
    $message = $template->fetch('string:' . $message);

    $locales = self::getLocale($locale);
    $confirmationBlockHtml = $template->fetch(E::path('templates/CRM/Gidipirus/Confirmation/ConfirmationBlock.' . $locales['html'] . '.html.tpl'));
    $confirmationBlockText = $template->fetch(E::path('templates/CRM/Gidipirus/Confirmation/ConfirmationBlock.' . $locales['text'] . '.text.tpl'));
    $privacyBlock = $template->fetch(E::path('templates/CRM/Gidipirus/Confirmation/PrivacyBlock.' . $locales['html'] . '.tpl'));
    $messageHtml = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockHtml, $message);
    $messageText = str_replace("#CONFIRMATION_BLOCK", $confirmationBlockText, $message);
    $messageHtml = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageHtml);
    $messageText = str_replace("#PRIVACY_BLOCK", $privacyBlock, $messageText);

    $email['html'] = html_entity_decode($messageHtml);
    $email['text'] = html_entity_decode(self::convertHtmlToText($messageText));
    $email['groupName'] = 'WemoveConsent.request';
    $email['custom-campaign-id'] = $campaignId;

    return $email;
  }

  /**
   * Store the fact that a contact (identified by contact_id) has been requested or has answered a consent (identified by consent_id):
   *  - Create a consent activity
   *  - Set the contact GDPR custom fields
   *  - Create a join or leave activity if applicable
   */
  public function addConsent($contactId, $consent, $isMember, $attribution) {
    $this->addConsentActivity($contactId, $consent, $attribution);
    $this->setGdprFields($contactId, $consent, $attribution);
    if ($consent->status == 'Confirmed' && !$isMember) {
      $this->join($contactId, $attribution);
    }
    else if (($consent->status == 'Rejected' || $consent->status == 'Cancelled') && $isMember) {
      $this->leave($contactId, $attribution);
    }
    return TRUE;
  }

  /**
   * c.f. API doc of cancel_consents
   * Create an activity for each cancelled consent, create a leave activity, leave the members group and clear the GDPR fields
   */
  public function cancelConsents($contactId, $cancelDate, $attribution) {
    $tx = new CRM_Core_Transaction();
    try {
      $activeConsents = $this->getConfirmedConsents($contactId);
      foreach ($activeConsents as $consent) {
        $cancelConsent = new CRM_Gidipirus_Model_Consent($consent->version, $consent->language, 'Cancelled', $cancelDate);
        $this->addConsentActivity($contactId, $cancelConsent, $attribution);
      }

      if (isset($cancelConsent)) {
        $this->setGdprFields($contactId, $cancelConsent, $attribution);
        $this->leave($contactId, $attribution);
      }
      $tx->commit();
    }
    catch (Exception $ex) {
      $tx->rollback()->commit();
      throw $ex;
    }

    return $activeConsents;
  }

  private function join($contactId, $attribution) {
    $this->addJoinActivity($contactId, $attribution);
    $this->setMember($contactId, 'Added');
	}

  private function leave($contactId, $attribution) {
    $this->addLeaveActivity($contactId, $attribution);
    $this->setMember($contactId, 'Removed');
  }

  private function addConsentActivity($contactId, $consent, $attribution) {
    $this->addActivity($contactId, self::consentActivityType(), $consent->date, $consent->version,
                       $attribution, self::consentActivityStatus($consent->status), $consent->language);
  }

  private function addJoinActivity($contactId, $attribution) {
    $this->addActivity($contactId, self::joinActivityType(), date('Y-m-d H:i:s'), $attribution->method, $attribution);
  }

  private function addLeaveActivity($contactId, $attribution) {
    $this->addActivity($contactId, self::leaveActivityType(), date('Y-m-d H:i:s'), $attribution->method, $attribution);
  }

  private function addActivity($contactId, $actType, $actDate, $subject, $attribution, $status = 'Completed', $location = NULL) {
		$params = [
      'source_contact_id' => $contactId,
      'campaign_id' => $attribution->campaignId,
      'activity_type_id' => $actType,
      'activity_date_time' => $actDate,
      'subject' => $subject,
      'location' => $location,
      'status_id' => $status,
      'parent_id' => $attribution->sourceActivity,
      self::field('activity.utm_source') => $attribution->source,
      self::field('activity.utm_medium') => $attribution->medium,
      self::field('activity.utm_campaign') => $attribution->campaign,
    ];
    $get = civicrm_api3('Activity', 'get', $params);
    if ($get['count'] == 0) {
      $result = civicrm_api3('Activity', 'create', $params);
      if ($result['is_error']) {
        throw new Exception("CRM_Gidipirus_Logic_Consent.addActivity: {$result['error_message']}");
      }
    }
  }

  private function setMember($contactId, $status) {
		$result = civicrm_api3('GroupContact', 'create', [
			'group_id' => CRM_Gidipirus_Settings::membersGroupId(),
			'contact_id' => $contactId,
			'status' => $status,
		]);
  }

  private function setGdprFields($contactId, $consent, $attribution) {
    if ($consent->status != 'Confirmed') {
      $params= [
        'id' => $contactId,
        self::field('gdpr.Consent_version')  => 'null',
        self::field('gdpr.Consent_date')     => 'null',
        self::field('gdpr.Consent_language') => 'null',
        self::field('gdpr.campaign_id')      => 'null',
        self::field('gdpr.utm_source')       => 'null',
        self::field('gdpr.utm_medium')       => 'null',
        self::field('gdpr.utm_campaign')     => 'null',
      ];
    } else {
      $params= [
        'id' => $contactId,
        self::field('gdpr.Consent_version')  => $consent->version,
        self::field('gdpr.Consent_date')     => $consent->date,
        self::field('gdpr.Consent_language') => $consent->language,
        self::field('gdpr.campaign_id')      => $attribution->campaignId,
        self::field('gdpr.utm_source')       => $attribution->source,
        self::field('gdpr.utm_medium')       => $attribution->medium,
        self::field('gdpr.utm_campaign')     => $attribution->campaign,
      ];
    }
    $result = civicrm_api3('Contact', 'create', $params);
    if ($result['is_error']) {
      CRM_Core_Error::debug_log_message("Could not update the GDPR custom fields for contact $contactId");
    }
  }

  private function getConfirmedConsents($contactId) {
    $query = "
        SELECT version, a2.location AS language, max_completed_date AS `date`
        FROM (
          SELECT
            version, MAX(completed_date) AS max_completed_date, MAX(cancelled_date) AS max_cancelled_date
          FROM (
            SELECT
              a.subject AS version,
              IF(a.status_id = %3, MAX(a.activity_date_time), '1970-01-01') AS completed_date,
              IF(a.status_id = %4, MAX(a.activity_date_time), '1970-01-01') AS cancelled_date
            FROM civicrm_activity_contact ac
            JOIN civicrm_activity a ON a.id = ac.activity_id AND a.activity_type_id = %2
            WHERE ac.contact_id = %1 AND a.status_id IN (%3, %4)
            GROUP BY version, a.status_id
          ) t
          GROUP BY version
        ) t2
        JOIN civicrm_activity_contact ac2 ON ac2.contact_id = %1
        JOIN civicrm_activity a2
          ON a2.id = ac2.activity_id AND a2.subject = t2.version
          AND a2.activity_date_time = max_completed_date AND a2.activity_type_id = %2
        WHERE max_completed_date > max_cancelled_date
    ";

    $queryParams = [
      1 => [$contactId, 'Integer'],
      2 => [self::consentActivityType(), 'Integer'],
      3 => [self::consentActivityStatus('Confirmed'), 'Integer'],
      4 => [self::consentActivityStatus('Cancelled'), 'Integer']
    ];
    $dao = CRM_Core_DAO::executeQuery($query, $queryParams);

    $activeConsents = [];
    while ($dao->fetch()) {
      $activeConsents[] = new CRM_Gidipirus_Model_Consent($dao->version, $dao->language, 'Confirmed', $dao->date);
    }
    return $activeConsents;
  }

  /**
   * From a consent id, return the string to compare to previous consent version in order to check its compatibility
   * If the consent id contains a '_' (partner consent), returns the full version (id stripped of language)
   * Otherwise return the major version only
   */
  public function getComparableVersion($consentId) {
    if (strpos($consentId, '_') === FALSE) {
      $version = explode('.', $consentId)[0];
    } else {
      $version = explode('-', $consentId)[0];
    }
    return $version;
  }

  /**
   * Activity type used to store consents
   * For historical reasons, its name is 'SLA Acceptance' but its label is 'Digital Policy Acceptance' (hence $dpaType)
   */
  public static function consentActivityType() {
    if (!self::$dpaType) {
      self::$dpaType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'SLA Acceptance');
    }
    return self::$dpaType;
  }

  /**
   * Activity type used to record when contacts join the main membership group
   */
  public static function joinActivityType() {
    if (!self::$joinType) {
      self::$joinType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Join');
    }
    return self::$joinType;
  }

  /**
   * Activity type used to record when contacts leave the main membership group
   */
  public static function leaveActivityType() {
    if (!self::$leaveType) {
      self::$leaveType = CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'activity_type_id', 'Leave');
    }
    return self::$leaveType;
  }

  /**
   * Mapping ConsentStatus => ActivityStatus
   */
  public static function consentActivityStatus($status = NULL) {
    if (!self::$consentStatuses) {
      self::$consentStatuses = [
        'Pending'   => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'),
        'Confirmed' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
        'Rejected'  => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'optout'), //Comes from Speakcivi
        'Cancelled' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Cancelled'),
      ];
    }
		if ($status) {
      return self::$consentStatuses[$status];
    } else {
      return self::$consentStatuses;
    }
  }

  public static function field($name = NULL) {
    if (!self::$customFields) {
      $gdprFields = ['Consent_date', 'Consent_version', 'campaign_id', 'Consent_language', 'utm_source', 'utm_medium', 'utm_campaign'];
      foreach ($gdprFields as $field) {
        $result = civicrm_api3('CustomField', 'get', ['custom_group_id' => 'GDPR_temporary', 'name' => $field]);
        if ($result['count'] == 1) {
          self::$customFields["gdpr.$field"] = 'custom_' . $result['id'];
        } else {
          throw new Exception("Could not find GDPR custom field $field");
        }
      }
      $activityUtmFields = ['source', 'medium', 'campaign'];
      foreach ($activityUtmFields as $field) {
        self::$customFields["activity.utm_$field"] = CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', "field_activity_$field");
      }
    }
    if ($name) {
      return self::$customFields[$name];
    } else {
      return self::$customFields;
    }
  }

  public static function createActivityTypes() {
    self::$dpaType = CRM_Gidipirus_Model::optionValue('activity_type', 'SLA Acceptance', ['title' => 'Data Policy Acceptance']);
    self::$joinType = CRM_Gidipirus_Model::optionValue('activity_type', 'Join', ['title' => 'Join']);
    self::$leaveType = CRM_Gidipirus_Model::optionValue('activity_type', 'Leave', ['title' => 'Leave']);
  }

  /**
   * todo refactor!
   * Get locale version for locale from params. Default is a english version.
   * @param string $locale Locale, so format is xx_YY (language_COUNTRY), ex. en_GB
   * @return array
   */
  public static function getLocale($locale) {
    $localeTab = array(
      'html' => 'en_GB',
      'text' => 'en_GB',
    );
    foreach ($localeTab as $type => $localeType) {
      if (file_exists(E::path('templates/CRM/Gidipirus/Confirmation/ConfirmationBlock.' . $locale . '.' . $type . '.tpl'))) {
        $localeTab[$type] = $locale;
      }
    }
    return $localeTab;
  }


  /**
   * todo refactor
   */
  public static function convertHtmlToText($html) {
    $html = str_ireplace(array('<br>', '<br/>', '<br />'), "\n", $html);
    $html = strip_tags($html, '<a>');
    $re = '/<a href="(.*)">(.*)<\/a>/';
    if (preg_match_all($re, $html, $matches)) {
      foreach ($matches[0] as $id => $tag) {
        $html = str_replace($tag, $matches[2][$id] . "\n" . str_replace(' ', '+', $matches[1][$id]), $html);
      }
    }
    return $html;
  }
}
