// todo extract contact id
// todo extract activity type
// todo prepare a href by copying
CRM.$(document).on('crmLoad', '.contact-activity-selector-activity', function() {
  CRM.$('[data-entity=activity]').find('a[href*="atype=12"]').parent().append('<span>Set request</span>');
});
