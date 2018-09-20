<?php

class CRM_Gidipirus_Model_Tag {

  const FORGOTTEN = 'FORGOTTEN';

  /**
   * Get activity type id for Forgetme Fulfillment
   *
   * @return int|mixed
   * @throws \CiviCRM_API3_Exception
   */
  public static function forgottenId() {
    $key = __CLASS__ . '.' . __FUNCTION__;
    $cache = Civi::cache()->get($key);
    if (!isset($cache)) {
      $id = self::set(self::FORGOTTEN);
      Civi::cache()->set($key, $id);
      return $id;
    }
    return $cache;
  }

  /**
   * @param $name
   *
   * @return mixed
   * @throws \CiviCRM_API3_Exception
   */
  private static function set($name) {
    $params = [
      'sequential' => 1,
      'name' => $name,
    ];
    $result = civicrm_api3('Tag', 'get', $params);
    if (!$result['count']) {
      $params = array_merge($params, [
        'is_reserved' => 1,
        'used_for' => 'civicrm_contact',
        'color' => '#000000',
      ]);
      $result = civicrm_api3('Tag', 'create', $params);
    }

    return $result['id'];
  }

  /**
   * Add tag to contact
   *
   * @param int $contactId
   * @param int $tagId
   *
   * @return
   * @throws \CiviCRM_API3_Exception
   */
  public static function add($contactId, $tagId) {
    $params = array(
      'sequential' => 1,
      'entity_table' => "civicrm_contact",
      'entity_id' => $contactId,
      'tag_id' => $tagId,
    );
    $result = civicrm_api3('EntityTag', 'get', $params);
    if (!$result['count']) {
      $result = civicrm_api3('EntityTag', 'create', $params);
      return !!$result['added'];
    }

    return !!$result['count'];
  }

}
