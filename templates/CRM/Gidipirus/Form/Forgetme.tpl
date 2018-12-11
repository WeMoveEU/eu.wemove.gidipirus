{crmScope extensionKey=$extensionKey}
{capture assign=contactUrl}{crmURL p='civicrm/contact/view' q="reset=1&cid=`$contactId`"}{/capture}
  <h3><a href="{$contactUrl}">{$displayName} (id {$contactId})</a></h3>
  <div class="form-item">
    <div class="crm-block crm-form-block">

      <div class="crm-section">
        <div class="label"><strong>Status</strong></div>
        <div class="content"><strong>{$forgetmeValue[$statusId]}:</strong> {$forgetmeDescription[$statusId]}</div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">Request source</div>
        <div class="content">
          {if $activity.id}
            {capture assign=activityUrl}
              {crmURL
                p='civicrm/contact/view/activity'
                q="atype=12&action=view&reset=1&id=`$activity.id`&cid=`$contactId`&context=activity&searchContext=activity"}
            {/capture}
            <a href="{$activityUrl}">Inbound Email on {$activity.activity_date_time} with subject "{$activity.subject}"</a>
          {else}
            ---
          {/if}
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.request_date.label}</div>
        <div class="content">{$form.request_date.html}</div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.request_channel.label}</div>
        <div class="content">{$form.request_channel.html}</div>
        <div class="clear"></div>
      </div>

      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}
      </div>

    </div>
  </div>
{/crmScope}

