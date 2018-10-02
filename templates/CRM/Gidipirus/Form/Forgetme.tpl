{crmScope extensionKey=$extensionKey}
  <div class="form-item">
    <div class="crm-block crm-form-block">

      <div class="crm-section">
        <div class="label">Status</div>
        <div class="content">{$forgetmeValue[$statusId]}</div>
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

