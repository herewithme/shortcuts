<?php
/**
 * Client class
 *
 * @package Shortcuts
 * @author Amaury Balmer
 */
class Shortcuts_Client {
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
		add_action( 'parse_query', array(&$this, 'parseQuery') );
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
		$shortcuts = $wpdb->get_col("SELECT DISTINCT post_name FROM $wpdb->posts WHERE post_status = 'publish' AND post_type = 'shortcut'");
		if ( $shortcuts == false ) {
			return false;
		}
		
		// Add rules for each shortcuts !
		foreach( $shortcuts as $shortcut ) {
			$new_rules = array(
				$shortcut.'/([^/]+)?$' 									=> 'index.php?'.SHORT_QUERY.'=true&post_type='.SHORT_CPT.'&post_name='. $wp_rewrite->preg_index( 1 ),
				$shortcut.'/([^/]+)/page/?([0-9]{1,})/?$' 				=> 'index.php?'.SHORT_QUERY.'=true&post_type='.SHORT_CPT.'&post_name='. $wp_rewrite->preg_index( 1 ).'&paged='.$wp_rewrite->preg_index(2),
				$shortcut.'/([^/]+)/feed/(feed|rdf|rss|rss2|atom)/?$' 	=> 'index.php?'.SHORT_QUERY.'=true&post_type='.SHORT_CPT.'&post_name='. $wp_rewrite->preg_index( 1 ).'&feed='.$wp_rewrite->preg_index(2),
				$shortcut.'/([^/]+)/(feed|rdf|rss|rss2|atom)/?$' 		=> 'index.php?'.SHORT_QUERY.'=true&post_type='.SHORT_CPT.'&post_name='. $wp_rewrite->preg_index( 1 ).'&feed='.$wp_rewrite->preg_index(2)
			);
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
	
	function parseQuery( $query ) {
		// Execute hook once !

		$current_item = stripslashes(get_query_var(SHORT_QUERY));
		if ( get_magic_quotes_gpc() ) {
			$current_item = stripslashes($current_item); // why so many freakin' slashes?
		}
		
		if ( !empty($current_item) ) {
			
			$flag = false;
			foreach ( (array) $this->options as $id => $shortcut ) {
				if ( $shortcut['slug'] == $current_item ) {
					$flag = $id;
					break;
				}
			}
			
			if ( $flag === false ) {
				return false;
			}
			
			// Clean memory
			unset($current_item);
			
			// Remove all WP flags
			global $wp_query;
			$wp_query->init_query_flags();
			$wp_query->is_archive = true;
			
			// Get current shortcuts
			$shortcut = $this->options[$flag];
			if ( $shortcut == false ) {
				return false;
			}
			
			// Tags
			$wp_query->query_vars['tag__in'] = $shortcut['datas']['tags']['or'];
			if ( !is_array($wp_query->query_vars['tag__in']) || empty($wp_query->query_vars['tag__in']) ) {
				$wp_query->query_vars['tag__in'] = array();
			} else {
				$wp_query->query_vars['tag__in'] = array_map('intval', $wp_query->query_vars['tag__in']);
				$wp_query->is_tag = true;
			}
			
			$wp_query->query_vars['tag__not_in'] = $shortcut['datas']['tags']['not'];
			if ( !is_array($wp_query->query_vars['tag__not_in']) || empty($wp_query->query_vars['tag__not_in']) ) {
				$wp_query->query_vars['tag__not_in'] = array();
			} else {
				$wp_query->query_vars['tag__not_in'] = array_map('intval', $wp_query->query_vars['tag__not_in']);
			}
			
			$wp_query->query_vars['tag__and'] = $shortcut['datas']['tags']['and'];
			if ( !is_array($wp_query->query_vars['tag__and']) || empty($wp_query->query_vars['tag__and']) ) {
				$wp_query->query_vars['tag__and'] = array();
			} else {
				$wp_query->query_vars['tag__and'] = array_map('intval', $wp_query->query_vars['tag__and']);
				$wp_query->is_category = true;
			}
			
			// Categories
			$wp_query->query_vars['category__in'] = $shortcut['datas']['categories']['cat_or'];
			if ( !is_array($wp_query->query_vars['category__in']) || empty($wp_query->query_vars['category__in']) ) {
				$wp_query->query_vars['category__in'] = array();
			} else {
				$wp_query->query_vars['category__in'] = array_map('intval', $wp_query->query_vars['category__in']);
				$wp_query->is_category = true;
			}
			
			$wp_query->query_vars['category__not_in'] = $shortcut['datas']['categories']['cat_not'];
			if ( !is_array($wp_query->query_vars['category__not_in']) || empty($wp_query->query_vars['category__not_in']) ) {
				$wp_query->query_vars['category__not_in'] = array();
			} else {
				$wp_query->query_vars['category__not_in'] = array_map('intval', $wp_query->query_vars['category__not_in']);
			}
			
			$wp_query->query_vars['category__and'] = $shortcut['datas']['categories']['cat_and'];
			if ( !is_array($wp_query->query_vars['category__and']) || empty($wp_query->query_vars['category__and']) ) {
				$wp_query->query_vars['category__and'] = array();
			} else {
				$wp_query->query_vars['category__and'] = array_map('intval', $wp_query->query_vars['category__and']);
				$wp_query->is_category = true;
			}
			
			// Sticky posts
			$wp_query->query_vars['sticky_posts_shortcut'] = $shortcut['post_id'];
			
			// Authors
			$wp_query->query_vars['author'] = implode( ',', (array) $shortcut['datas']['authors'] );
			if ( empty($wp_query->query_vars['author']) || ($wp_query->query_vars['author'] == '0') ) {
				$wp_query->is_author = false;
			} else {
				$wp_query->is_author = true;
			}
			
			// Dates
			// $dates = $shortcut['datas']['dates'];
			
			// Build SQL Queries
			//add_action('posts_request', array(&$this, 'buildQuery'));
			
			// Add stick post filter
			// $this->posts = apply_filters('the_posts', $this->posts);
			
			// Redirect to specific template
			add_action('template_redirect', array(&$this, 'templateRedirect'), 1);
			add_filter('wp_title', array(&$this, 'addTitle'), 10, 2 );
			
			return true;
		}
		return false;
	}
	
	function buildQuery( $query = '' ) {
		// var_dump($query);
		// exit();
		return $query;
	}
	
	function addTitle( $title = '', $sep = '&raquo;') {
		return $sep . ' ' . get_shortcut_title();
	}
	
	function templateRedirect() {

			$template = '';
			
			if ( is_file(TEMPLATEPATH . '/' . $this->tpl_file ) ) {
				$template = TEMPLATEPATH . '/' . $this->tpl_file;
			} elseif ( is_file(TEMPLATEPATH . '/index.php') ) {
				$template = TEMPLATEPATH . '/index.php';
			} else {
				wp_die('Template files <code>'.$this->tpl_file.'</code> or <code>index.php</code> are required for this plugin');
			}
			
			if ($template) {
				load_template($template);
				exit();
			}
		
		return;
	}
}
?>