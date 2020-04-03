<?php

class CRM_Gidipirus_Model_Consent {

  private static $statuses;

  public function __construct($consent_id, $status, $date) {
    list($version, $language) = explode('-', $consent_id);
    $this->version = $version;
    $this->language = $language;
    $this->status = $status;
    $this->date = $date;
  }

  /**
   * C.f. API doc of set_consent_status for meaning of each value
   */
  public static function statusOptions() {
    if (!self::$statuses) {
      self::$statuses = [
        'Pending'   => 'Pending',
        'Confirmed' => 'Confirmed',
        'Rejected'  => 'Rejected',
        'Cancelled' => 'Cancelled'
      ];
    }
    return self::$statuses;
  }
}
