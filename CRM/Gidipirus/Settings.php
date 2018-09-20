<?php

class CRM_Gidipirus_Settings {

  /**
   * Number of days after request to forgetme
   */
  const SCHEDULED_DAYS_DEFAULT = 21;

  /**
   * Template of email for anonymisation
   */
  const EMAIL_TEMPLATE = 'forgetme+%RANDOM%@wemove.eu';

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

  /**
   * Get or set email template
   *
   * @param string $template
   *
   * @return mixed
   */
  public static function emailTemplate($template = '') {
    if ($template) {
      Civi::settings()->set(self::emailTemplateKey(), $template);
      return $template;
    }
    $template = Civi::settings()->get(self::emailTemplateKey());
    if (!$template) {
      Civi::settings()->set(self::emailTemplateKey(), self::EMAIL_TEMPLATE);
    }

    return $template;
  }

  private static function scheduledDaysKey() {
    return __CLASS__ . '.' . __METHOD__;
  }

  private static function emailTemplateKey() {
    return __CLASS__ . '.' . __METHOD__;
  }

}
