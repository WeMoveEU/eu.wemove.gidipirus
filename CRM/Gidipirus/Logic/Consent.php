<?php

class CRM_Gidipirus_Logic_Consent {

  private static $dpaType;
  private static $consentStatuses;

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
            WHERE e.email = %1 AND e.is_primary = 1
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
   * Mapping ConsentStatus => ActivityStatus
   */
  public static function consentActivityStatus($status = NULL) {
    if (!self::$consentStatuses) {
      self::$consentStatuses = [
        'Pending'   => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Scheduled'),
        'Confirmed' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Completed'),
        'Rejected'  => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'optout'),
        'Cancelled' => CRM_Core_PseudoConstant::getKey('CRM_Activity_BAO_Activity', 'status_id', 'Cancelled'),
      ];
    }
		if ($status) {
      return self::$consentStatuses[$status];
    } else {
      return self::$consentStatuses;
    }
  }
}
