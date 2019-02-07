CRM.$(document).on('crmLoad', '.contact-activity-selector-activity', function() {
  CRM.$.each(CRM.$('[data-entity=activity]').find('a[href*="atype=12"]').parent(), function(index, value) {
    var hasLink = CRM.$(value).find('a[href*="gidipirus"]');
    if (!hasLink.length) {
      var aClass = CRM.$(value).find('a[title="View Activity"]').attr('class');
      var link = CRM.$(value).find('a[title="View Activity"]').attr('href');
      var id = gidipirusGetParam(link, 'id');
      var cid = gidipirusGetParam(link, 'cid');
      var newLink = '/civicrm/gidipirus/forgetme?reset=1&cid=' + cid + '&aid=' + id;
      CRM.$(value).append('<a href="' + newLink + '" class="' + aClass + '" title="Register forget request">Register request</a>');
    }
  });
});
function gidipirusGetParam(link, name) {
  return decodeURIComponent((new RegExp('[?|&]' + name + '=' + '([^&;]+?)(&|#|;|$)').exec(link)||[,""])[1].replace(/\+/g, '%20'))||null;
}
