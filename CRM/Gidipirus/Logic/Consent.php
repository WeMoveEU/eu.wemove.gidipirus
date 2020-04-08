<?php

class CRM_Gidipirus_Logic_Consent {

  private static $dpaType;
  private static $joinType;
  private static $leaveType;
  private static $consentStatuses;
  private static $customFields;

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
      $this->join($contactId, $consent->date, $attribution);
    }
    else if (($consent->status == 'Rejected' || $consent->status == 'Cancelled') && $isMember) {
      $this->leave($contactId, $consent->date, $attribution);
    }
    return TRUE;
  }

  /**
   * c.f. API doc of cancel_consents
   * Create an activity for each cancelled consent, create a leave activity, leave the members group and clear the GDPR fields
   */
  public function cancelConsents($contactId, $cancelDate, $attribution) {
    $activeConsents = $this->getConfirmedConsents($contactId);
    foreach ($activeConsents as $consent) {
      $cancelConsent = new CRM_Gidipirus_Model_Consent($consent->version, $consent->language, 'Cancelled', $cancelDate);
      $this->addConsentActivity($contactId, $cancelConsent, $attribution);
    }

    if (isset($cancelConsent)) {
      $this->setGdprFields($contactId, $cancelConsent, $attribution);
      $this->leave($contactId, $cancelDate, $attribution);
    }

    return $activeConsents;
  }

  private function join($contactId, $joinDate, $attribution) {
    $this->addJoinActivity($contactId, $joinDate, $attribution);
    $this->setMember($contactId, 'Added');
	}

  private function leave($contactId, $cancelDate, $attribution) {
    $this->addLeaveActivity($contactId, $cancelDate, $attribution);
    $this->setMember($contactId, 'Removed');
  }

  private function addConsentActivity($contactId, $consent, $attribution) {
    $this->addActivity($contactId, self::consentActivityType(), $consent->date, $consent->version,
                       $attribution, self::consentActivityStatus($consent->status), $consent->language);
  }

  private function addJoinActivity($contactId, $joinDate, $attribution) {
    $this->addActivity($contactId, self::joinActivityType(), $joinDate, $attribution->method, $attribution);
  }

  private function addLeaveActivity($contactId, $cancelDate, $attribution) {
    $this->addActivity($contactId, self::leaveActivityType(), $cancelDate, $attribution->method, $attribution);
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
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_source') => $attribution->source,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_medium') => $attribution->medium,
      CRM_Core_BAO_Setting::getItem('Speakcivi API Preferences', 'field_activity_campaign') => $attribution->campaign,
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
}
