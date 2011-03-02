jQuery(document).ready(function() {
	toggleMode();
	
	jQuery('input[name="query_mode"]').change(function() {
		toggleMode();
	});
	
	jQuery(function() {
		jQuery( "#accordion" ).accordion({ autoHeight: false });
	});
	
	jQuery( "#accordion h3 a" ).click(function(event) {
		event.preventDefault();
		event.parent().click();
	});
});

function toggleMode() {
	jQuery('#simple-mode-query, #advanced-mode-query').removeClass('hide-if-no-js');
	if ( jQuery('input[name="query_mode"]:checked').val() == 'advanced' ) {
		jQuery('#simple-mode-query').hide();
		jQuery('#advanced-mode-query').show();
	} else {
		jQuery('#simple-mode-query').show();
		jQuery('#advanced-mode-query').hide();
	}
}
