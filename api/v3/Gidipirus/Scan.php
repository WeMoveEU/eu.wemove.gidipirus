<?php

function _civicrm_api3_gidipirus_scan_spec(&$spec) {
}

/**
 * Scan all contacts and choose inactive of them.
 * @param $params
 *
 * @return array
 */
function civicrm_api3_gidipirus_scan(&$params) {
  $start = microtime(TRUE);

  // todo calc proper results
  $values = array(1, 2, 3);

  $extraReturnValues = array(
    'time' => microtime(TRUE) - $start,
  );
  if (1) {
    $blank = NULL;
    return civicrm_api3_create_success($values, $params, NULL, NULL, $blank, $extraReturnValues);
  }
  else {
    return civicrm_api3_create_error(ts('CUSTOM ERROR'), $params);
  }
}
