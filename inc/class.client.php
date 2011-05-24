<?php
/**
 * Client class
 *
 * @package Shortcuts
 * @author Amaury Balmer
 */
class Shortcuts_Client {
	private $current_shortcut = null;
	
	/**
	 * Constructor, register hooks
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function Shortcuts_Client() {
		// CPT
		add_action( 'init', array(&$this, 'registerCPT') );
		
		// WP_Query
		add_action( 'parse_query', array(&$this, 'parseQueryStep1') );
		add_filter( 'query_vars', array(&$this, 'addQueryVar') );
		
		// Rewriting rules
		add_action( 'generate_rewrite_rules', array( &$this, 'addRewriteRules') );
		add_action( 'save_post', array(&$this, 'resetRewritingRules'), 10, 2 );
	}
	
	function registerCPT() {
		register_post_type( SHORT_CPT, array(
			'labels' 				=> array(
				'name' => _x('Shortcuts', 'shortcuts post type general name', 'shortcuts'),
				'singular_name' => _x('Shortcut', 'shortcuts post type singular name', 'shortcuts'),
				'add_new' => _x('Add New', 'shortcuts', 'shortcuts'),
				'add_new_item' => __('Add New Shortcut', 'shortcuts'),
				'edit_item' => __('Edit Shortcut', 'shortcuts'),
				'new_item' => __('New Shortcut', 'shortcuts'),
				'view_item' => __('View Shortcut', 'shortcuts'),
				'search_items' => __('Search Shortcuts', 'shortcuts'),
				'not_found' => __('No Shortcuts found', 'shortcuts'),
				'not_found_in_trash' => __('No Shortcuts found in Trash', 'shortcuts'),
				'parent_item_colon' => __('Parent Shortcut:', 'shortcuts')
			),
			'description' 			=> __('Shortcut Plugin', 'shortcuts'),
			'publicly_queryable' 	=> false,
			'exclude_from_search' 	=> true,
			'public' 				=> false,
			'capability_type' 		=> SHORT_CPT,
			//'capabilities' 		=> array(),
			'map_meta_cap'			=> true,
			'hierarchical' 			=> false,
			'rewrite' 				=> false,
			'query_var' 			=> false,
			'supports' 				=> array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'taxonomies' 			=> array(),
			'show_ui' 				=> true,
			'menu_position' 		=> 100,
			'has_archive'			=> false,
			'menu_icon' 			=> SHORT_URL.'/ressources/link.png',
			'can_export' 			=> false,
			'show_in_nav_menus'		=> false
		) );
	}
	
	/**
	 * Flush rewriting rules when a item whith the post_type "shortcut" is save.
	 *
	 * @param integer $object_id 
	 * @param object $object 
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function resetRewritingRules( $object_id = 0, $object = null ) {
		if ( !isset($object) || $object == null ) {
			$object = get_post( $object_id );
		}
		
		if ( $object->post_type == SHORT_CPT ) {
			// Flush only if post type is shortcut
			flush_rewrite_rules( false );
			return true;
		}
		
		return false;
	}
	
	/**
	 * Add rules from each shortcuts
	 *
	 * @param object $wp_rewrite 
	 * @return void
	 * @author Amaury Balmer
	 */
	function addRewriteRules( $wp_rewrite ) {
		global $wpdb;
		
		// Get shortcuts
		$shortcuts = $wpdb->get_results("SELECT DISTINCT post_name, ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'shortcut'");
		if ( $shortcuts == false ) {
			return false;
		}
		
		// Add rules for each shortcuts !
		foreach( $shortcuts as $shortcut ) {
			$new_rules = array();
			$new_rules[$shortcut->post_name.'/([^/]+)?$'] = 'index.php?'.SHORT_QUERY.'='. $wp_rewrite->preg_index( 1 );
			
			if ( get_post_meta( $shortcut->ID, 'pagination', true ) == true ) 
				$new_rules[$shortcut->post_name.'/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?'.SHORT_QUERY.'='. $wp_rewrite->preg_index( 1 ).'&paged='.$wp_rewrite->preg_index(2);
			
			if ( get_post_meta( $shortcut->ID, 'feed', true ) == true ) {
				$new_rules[$shortcut->post_name.'/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.SHORT_QUERY.'='. $wp_rewrite->preg_index( 1 ).'&feed='.$wp_rewrite->preg_index(2);
				$new_rules[$shortcut->post_name.'/([^/]+)/(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?'.SHORT_QUERY.'='. $wp_rewrite->preg_index( 1 ).'&feed='.$wp_rewrite->preg_index(2);
			}
			
			$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		}
	}
	
	/**
	 * Add query var on WP
	 *
	 * @param array $query_vars 
	 * @return array
	 * @author Amaury Balmer
	 */
	function addQueryVar( $query_vars ) {
		$query_vars[] = SHORT_QUERY;
		return $query_vars;
	}
	
	/**
	 * Check query for shortcut !
	 *
	 * @param object $query 
	 * @return object
	 * @author Amaury Balmer
	 */
	function parseQueryStep1( $query ) {
		global $wpdb;
		
		$query->is_shortcut = false;
		
		if ( isset($query->query_vars[SHORT_QUERY]) ) {
			// Get shortcut data
			$this->current_shortcut = (int) $wpdb->get_var($wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = %s", $query->query_vars[SHORT_QUERY], SHORT_CPT ));
			if ( $this->current_shortcut == 0 ) {
				return false;
			}
			
			// Save in class var the current shortcut
			$this->current_shortcut = get_post($this->current_shortcut);
			
			// Get all custom fields
			$fields = get_post_custom( $this->current_shortcut->ID );
			
			// Remove empty value
			if ( isset($fields['simple']) ) {
				$fields['simple'] = array_filter($fields['simple']);
			}
			
			// Sticky ?
			// TODO : add filter
			$sticky = get_post_meta( $this->current_shortcut->ID, 'sticky_shortcut', true );
			
			// Get source of query posts
			$query_mode = get_post_meta( $this->current_shortcut->ID, 'query_mode', true );
			if ( $query_mode == 'advanced' ) {
				$query_posts = get_post_meta( $this->current_shortcut->ID, 'query_posts', true );
			} else {
				$query_posts = get_post_meta( $this->current_shortcut->ID, 'simple_query_posts', true );
				
				// TODO: meta query relation !
			}
			
			// Correct query posts
			if ( !empty($query_posts) ) {
				$query->query($query_posts);
			} else {
				wp_die( __('Shortcut is not configured yet.', 'shortcuts') );
			}
			
			// Second pass for parse_query
			remove_action( 'parse_query', array(&$this, 'parseQueryStep1') );
			add_action( 'parse_query', array(&$this, 'parseQueryStep2') );
			
			// Build SQL Queries
			//add_action('posts_request', array(&$this, 'buildQuery'));
			
			// Redirect to specific template
			add_action( 'template_redirect', array(&$this, 'templateRedirect'), 10 );
			add_filter( 'wp_title', array(&$this, 'wpTitle'), 10, 3 );
			
			return true;
		}
		return false;
	}
	
	/**
	 * Second pass on parse query for fix flag
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function parseQueryStep2( $query ) {
		remove_action( 'parse_query', array(&$this, 'parseQueryStep2') );
		
		// Remove all WP flags
		$query->init_query_flags();
		$query->is_shortcut = true;
		$query->is_archive = true;
	}
	
	function buildQuery( $query = '' ) {
		var_dump($query);
		// exit();
		return $query;
	}
	
	/**
	 * Make a nice title for this page
	 * Todo : Remove "Shortcut "
	 *
	 * @param string $title 
	 * @return void
	 * @author Amaury Balmer
	 */
	function wpTitle( $title, $sep, $seplocation ) {
		$title .= $this->current_shortcut->post_title . ' ' . $sep . ' ';
		return $title;
	}
	
	/**
	 * Load correct template for this shortcut
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function templateRedirect() {
		$templates = array();
		
		// DB Settings
		$template = get_post_meta( $this->current_shortcut->ID, 'template', true );
		if ( !empty($template) ) {
			$templates[] = $template;
		}
		
		// Default templates
		$templates[] = "shortcut-".$this->current_shortcut->post_name.".php";
		$templates[] = "shortcut.php";
		$templates[] = "archive.php";
		
		locate_template( $templates, true );
		exit();
	}
}
?>