<?php

class CRM_Gidipirus_Settings {

  /**
   * Number of days after request to forgetme
   */
  const SCHEDULED_DAYS_DEFAULT = 30;

  /**
   * Template of email for anonymisation
   */
  const EMAIL_TEMPLATE = 'forgetme+%RANDOM%@wemove.eu';

  /**
   * Monitor contacts only with history of this group
   */
  const MEMBERS_GROUP_ID = 42;

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
      $days = self::SCHEDULED_DAYS_DEFAULT;
      Civi::settings()->set(self::scheduledDaysKey(), $days);
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
      $template = self::EMAIL_TEMPLATE;
      Civi::settings()->set(self::emailTemplateKey(), $template);
    }

    return $template;
  }

  /**
   * Get or set members group id
   *
   * @param string $value Group Id
   *
   * @return mixed
   */
  public static function membersGroupId($value = '') {
    if ($value) {
      Civi::settings()->set(self::membersGroupIdKey(), $value);
      return $value;
    }
    $value = Civi::settings()->get(self::membersGroupIdKey());
    if (!$value) {
      $value = self::MEMBERS_GROUP_ID;
      Civi::settings()->set(self::membersGroupIdKey(), self::MEMBERS_GROUP_ID);
    }

    return $value;
  }

  /**
   * Get or set members group id
   *
   * @return array
   */
  public static function scannedActivitiesId() {
    $value = Civi::settings()->get(self::scannedActivitiesIdKey());
    if (!$value) {
      $query = "SELECT
                  value activity_type_id
                FROM civicrm_option_value ov
                  JOIN civicrm_option_group og ON og.id = ov.option_group_id
                WHERE og.name = 'activity_type' AND
                  ov.name IN ('Phone Call', 'Email', 'Survey', 'Petition', 'share', 'Tweet', 'Facebook')";
      $dao = CRM_Core_DAO::executeQuery($query);
      $value = [];
      while ($dao->fetch()) {
        $value[$dao->activity_type_id] = (int) $dao->activity_type_id;
      }
      Civi::settings()->set(self::scannedActivitiesIdKey(), $value);
    }

    return $value;
  }

  private static function scheduledDaysKey() {
    return __CLASS__ . '.' . __METHOD__;
  }

  private static function emailTemplateKey() {
    return __CLASS__ . '.' . __METHOD__;
  }

  private static function membersGroupIdKey() {
    return __CLASS__ . '.' . __METHOD__;
  }

  private static function scannedActivitiesIdKey() {
    return __CLASS__ . '.' . __METHOD__;
  }

}
