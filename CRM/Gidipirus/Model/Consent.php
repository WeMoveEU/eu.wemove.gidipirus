<?php

class CRM_Gidipirus_Model_Consent {

  private static $statuses;

  public function __construct($version, $language, $status, $date) {
    $this->version = $version;
    $this->language = $language;
    $this->status = $status;
    $this->date = $date;
    $this->method = $method;
  }

  public function id() {
    return $this->version . '-' . $this->language;
  }

  public static function fromId($consentId, $status, $date) {
    list($version, $language) = explode('-', $consentId);
    return new self($version, $language, $status, $date);
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
