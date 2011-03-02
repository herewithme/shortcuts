jQuery(document).ready(function() {
	toggleMode();

	jQuery('input[name="query_mode"]').change(function() {
		toggleMode();
	});

	var userpanel = jQuery("#accordion");
	var index = jQuery.cookie("accordion");
	var active;
	if (index !== undefined) {
		active = userpanel.find("h3:eq(" + index + ")");
	}
	userpanel.accordion({
		autoHeight: false,
		active: active,
		change: function(event, ui) {
			var index = jQuery(this).find("h3").index(ui.newHeader[0]);
			jQuery.cookie("accordion", index);
		}
	});

	jQuery("#accordion h3 a").click(function(event) {
		event.preventDefault();
		jQuery(this).parent().click();
	});
});

function toggleMode() {
	jQuery('#simple-mode-query, #advanced-mode-query').removeClass('hide-if-no-js');
	if (jQuery('input[name="query_mode"]:checked').val() == 'advanced') {
		jQuery('#simple-mode-query').hide();
		jQuery('#advanced-mode-query').show();
	} else {
		jQuery('#simple-mode-query').show();
		jQuery('#advanced-mode-query').hide();
	}
}
