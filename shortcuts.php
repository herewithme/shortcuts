<?php
/*
Plugin Name: Shorcuts
Version: 2.0
Plugin URI: http://www.beapi.fr
Description: Create addresses simple and SEO friendly to make complex query on your contents. A kind of "Views drupal" for WordPress.
Author: BeAPI
Author URI: http://www.beapi.fr

Copyright 2010 - BeAPI Team (technique@beapi.fr)

TODO :
	Admin
		Link preview
	Widget
		List all shortcuts
	Javascript
		
	Client
		Merge WP Query with custom shortcut datas
		body_class
*/

define( 'SHORT_VERSION', 		'2.0' );
define( 'SHORT_FOLDER', 		'shortcuts' );
define( 'SHORT_OPTIONS_NAME', 	'shortcuts' ); // Option name for save settings

define( 'SHORT_CPT', 			'shortcut' ); // CPT
define( 'SHORT_QUERY', 			'shortcut' ); // Query var array

define( 'SHORT_URL', 			plugins_url('', __FILE__) );
define( 'SHORT_DIR', 			dirname(__FILE__) );

require( SHORT_DIR . '/inc/functions.plugin.php');
require( SHORT_DIR . '/inc/functions.template.php');
require( SHORT_DIR . '/inc/class.client.php');

// Activation, uninstall
register_activation_hook( __FILE__, 'Shortcuts_Install'   );
register_uninstall_hook ( __FILE__, 'Shortcuts_Uninstall' );

// Init LifeDeal
function Shortcuts_Init() {
	global $shortcuts;

	// Load translations
	load_plugin_textdomain ( 'shortcuts', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages' );
	
	// Load client
	$shortcuts['client'] = new Shortcuts_Client();
	
	// Admin
	if ( is_admin() ) {
		require( SHORT_DIR . '/inc/class.admin.php' );
		$shortcuts['admin'] = new Shortcuts_Admin();
	}
	
	// Widget
	// add_action( 'widgets_init', create_function('', 'return register_widget("Shortcuts_Widget");') );
}
add_action( 'plugins_loaded', 'Shortcuts_Init' );
?>