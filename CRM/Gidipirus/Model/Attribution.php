<?php

class CRM_Gidipirus_Model_Attribution {

  public $method;

  public $campaignId;

  public $source;

  public $medium;

  public $campaign;

  public $sourceActivity;

  public function __construct($method, $campaign_id, $utm_source, $utm_medium, $utm_campaign, $source_activity = NULL) {
    $this->method = $method;
    $this->campaignId = $campaign_id;
    $this->source = $utm_source;
    $this->medium = $utm_medium;
    $this->campaign = $utm_campaign;
    $this->sourceActivity = $source_activity;
  }

}
