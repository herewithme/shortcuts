<?php
/**
 * Function call by WordPress when plugin is actived
 *
 * @return void
 * @author Amaury Balmer
 */
function Shortcuts_Install() {
		// Remove old role if needed to reset the caps
		remove_role( 'shortcutor' );

		// Create the new role
		add_role( 'shortcutor', __('Shortcutor', 'shortcuts') );

		// Get the role and add the caps
		$role = &get_role( 'shortcutor' );
		$role->add_cap( 'upload_files' );
		$role->add_cap( 'read' );

		// Attachements
		$role->remove_cap( 'edit_others_attachment' );
		$role->remove_cap( 'read_others_attachment' );
		$role->remove_cap( 'delete_others_attachment' );
		$role->remove_cap( 'delete_attachment' );

		// Add caps translation
		Shortcuts_Translation_Caps( $role );

		// Administrator
		$role = &get_role( 'administrator' );
		Shortcuts_Translation_Caps( $role );
	}

/**
 * Add caps shortcut for a role
 *
 * @param object $role 
 * @return void
 * @author Amaury Balmer
 */
function Shortcuts_Translation_Caps( &$role ) {
	$role->add_cap( 'edit_' . SHORT_CPT );
	$role->add_cap( 'read_' . SHORT_CPT );
	$role->add_cap( 'delete_' . SHORT_CPT );

	$role->add_cap( 'edit_' . SHORT_CPT . 's' );
	$role->add_cap( 'edit_others_' . SHORT_CPT . 's' );
	$role->add_cap( 'publish_' . SHORT_CPT . 's' );
	$role->add_cap( 'read_private_' . SHORT_CPT . 's' );

	$role->add_cap( 'delete_' . SHORT_CPT . 's' );
	$role->add_cap( 'delete_private_' . SHORT_CPT . 's' );
	$role->add_cap( 'delete_published_' . SHORT_CPT . 's' );
	$role->add_cap( 'delete_others_' . SHORT_CPT . 's' );
	$role->add_cap( 'edit_private_' . SHORT_CPT . 's' );
	$role->add_cap( 'edit_published_' . SHORT_CPT . 's' );
}

/**
 * Function call by WordPress when plugin is uninstalled
 *
 * @return void
 * @author Amaury Balmer
 */
function Shortcuts_Uninstall() {
}
?>