jQuery(document).ready(function() {
	// Switch simple/advanced
	toggleMode();
	jQuery('input[name="query_mode"]').change(function() {
		toggleMode();
	});

	// Accordeon, with cookie for keep open tab
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

	// Fake action on link for accordeon...
	jQuery("#accordion h3 a").click(function(event) {
		event.preventDefault();
		jQuery(this).parent().click();
	});
	
	// Clone tax meta query box
	jQuery("a#add-another-tax_query").click(function(event) {
		event.preventDefault();
		addTaxoMetaQuery();
	});
	
	// Clone tax meta query box
	jQuery("a#add-another-post_query").click(function(event) {
		event.preventDefault();
		addPostMetaQuery();
	});
});

function addTaxoMetaQuery() {
	var counter = jQuery(".tax_meta_query_col").size();
	
	// Display relation field if counter > 1
	if ( counter > 0 ) {
		jQuery("#relation_tax_query_wrap").show();
	}
	
	// Make ajax call for get new form
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: "action=shortcut_taxo_meta_query&counter="+counter,
		success: function(msg) {
			jQuery(".tax_meta_query_col:last").after( msg );
		}
	});
}

function addPostMetaQuery() {
	var counter = jQuery(".post_meta_query_col").size();
	
	// Make ajax call for get new form
	jQuery.ajax({
		type: "POST",
		url: ajaxurl,
		data: "action=shortcut_post_meta_query&counter="+counter,
		success: function(msg) {
			jQuery(".post_meta_query_col:last").after( msg );
		}
	});
}

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
