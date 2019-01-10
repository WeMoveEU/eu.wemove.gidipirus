<?php
require_once __DIR__ . '/../../CRM/Gidipirus/BaseTest.php';

/**
 * @group e2e
 */
class api_v3_CleanTest extends CRM_Gidipirus_BaseTest {

  /**
   */
  public function testNotValidChannel() {
    $channel = 'not-valid-channel';
    $params = [
      'sequential' => 1,
      'dry_run' => 1,
      'channels' => $channel,
    ];
    $result = $this->callAPIFailure('Gidipirus', 'cleanup', $params);
    $this->assertEquals('Invalid name of channel: ' . $channel, $result['error_message']);
  }

  /**
   */
  public function testChannels() {
    $params = [
      'sequential' => 1,
      'dry_run' => 1,
      'channels' => ['IN' => CRM_Gidipirus_Model_RequestChannel::$valid],
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    $this->assertGreaterThan(0, $result['count']);
  }

  /**
   */
  public function testCleanExpired() {
    $params = [
      'sequential' => 1,
      'dry_run' => 1,
      'channels' => CRM_Gidipirus_Model_RequestChannel::EXPIRED,
    ];
    $result = $this->callAPISuccess('Gidipirus', 'cleanup', $params);
    $this->assertGreaterThan(0, $result['count']);
  }

}
