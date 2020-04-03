<?php

class CRM_Gidipirus_Model_Attribution {

  public function __construct($campaign_id, $utm_source, $utm_medium, $utm_campaign) {
    $this->campaignId = $campaign_id;
    $this->source = $utm_source;
    $this->medium = $utm_medium;
    $this->campaign = $utm_campaign;
  }

}
