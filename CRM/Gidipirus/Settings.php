<?php

class CRM_Gidipirus_Settings {

  /**
   * Number of days after request to forgetme
   */
  const SCHEDULED_DAYS_DEFAULT = 21;

  /**
   * Get or set scheduled days.
   *
   * @param int $days
   *
   * @return mixed
   */
  public static function scheduledDays($days = 0) {
    if ($days) {
      Civi::settings()->set(self::scheduledDaysKey(), $days);
      return $days;
    }
    $days = Civi::settings()->get(self::scheduledDaysKey());
    if (!$days) {
      Civi::settings()->set(self::scheduledDaysKey(), self::SCHEDULED_DAYS_DEFAULT);
    }

    return $days;
  }

  private static function scheduledDaysKey() {
    return __CLASS__ . '.' . __METHOD__;
  }

}
