jQuery(document).ready(function () {
  "use strict";
  jQuery('button.copy-client-id').click(function () {
    var jQuerytemp = jQuery("<input>");
    jQuery("body").append(jQuerytemp);
    jQuerytemp.val(jQuery("#app-client-id").text()).select();
    document.execCommand("copy");
    jQuerytemp.remove();
  });
  jQuery('button.client-secret-show').click(function () {
    jQuery(this).hide();
    var secret = jQuery("#app-client-secret-data").data("client-secret");
    jQuery("#app-client-secret").text(secret);
    jQuery(".client-secret-hide").show();
  });
  jQuery('button.client-secret-hide').click(function () {
    jQuery(this).hide();
    jQuery(".client-secret-show").show();
    jQuery("#app-client-secret").html("&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;");
  });
  jQuery('button.client-secret-reset').click(function () {
    jQuery("button.client-secret-show").prop("disabled", true);
    jQuery("button.client-secret-hide").prop("disabled", true);
    var appId = jQuery("#app-id").data("app-id");
    var appGroupId = jQuery("#app-group-id").data("app-group-id");
    jQuery.ajax({
      url: "/myapp/" + appGroupId + "/" + appId + "/reset",
      success: function (result) {
        if (result.response === true) {
          if (jQuery(".client-secret-hide").is(':visible')) {
            jQuery(".client-secret-hide").show();
            jQuery(".client-secret-show").hide();
            jQuery('#app-client-secret-data').data('client-secret', result.secret);
            jQuery("#app-client-secret").text(result.secret);
          } else if (jQuery(".client-secret-show").is(':visible')) {
            jQuery(".client-secret-hide").hide();
            jQuery(".client-secret-show").show();
            jQuery('#app-client-secret-data').data('client-secret', result.secret);
            jQuery("#app-client-secret").text("****************************");
          }
          jQuery("button.client-secret-show").prop("disabled", false);
          jQuery("button.client-secret-hide").prop("disabled", false);
        }
      }
    });
  });

  jQuery('.sidebar ul li').click(function () {
    jQuery(this).siblings().addBack().children().remove();
    var a = jQuery(this).siblings().toggle();
    jQuery(this).parent().prepend(this);
  });
  jQuery(".user-operations-list li a").on("click", function () {
    setTimeout(function () {
      jQuery(".user-operations-list").addClass("hidden");
    }, 1000);
  });
  jQuery(".table-responsive").on("click", function () {
    jQuery(".user-operations-list").addClass("hidden");
  });
  jQuery(".user-operations-button").on("click", function (event) {
    event.stopPropagation();
    jQuery(".user-operations-list").addClass("hidden");
    jQuery(this).next(".user-operations-list").removeClass("hidden");
  });
});

