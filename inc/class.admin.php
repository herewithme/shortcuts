<?php
class Shortcuts_Admin {
	// Error management
	var $message = '';
	var $status = '';
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function Shortcuts_Admin() {
		// Style, Javascript
		add_action( 'admin_enqueue_scripts', array(&$this, 'addRessources') );
		add_action( 'wp_ajax_' . 'shortcut_taxo_meta_query', array(&$this, 'ajaxBuildTaxoMetaQuery' ) );
		add_action( 'wp_ajax_' . 'shortcut_post_meta_query', array(&$this, 'ajaxBuildPostMetaQuery' ) );
		
		
		// Metadatas
		add_action( 'add_meta_boxes', array(&$this, 'registerMetaBox'), 999 );
		add_action( 'save_post', array(&$this, 'saveDatasMetaBoxes'), 10, 2 );
		
		// Listing
		add_filter( 'manage_posts_columns', array( &$this, 'addColumns'), 10 ,2 );
		add_action( 'manage_posts_custom_column', array(&$this, 'addColumnValue' ), 10, 2 );
	}
	
	/**
	 * Register JS/CSS for correct post type
	 *
	 * @param string $hook_suffix
	 * @return void
	 * @author Amaury Balmer
	 */
	function addRessources( $hook_suffix = '' ) {
		global $post;
		
		if (
			( $hook_suffix == 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] == SHORT_CPT ) ||
			( $hook_suffix == 'post.php' && isset($_GET['post']) && $post->post_type == SHORT_CPT ) ||
			( $hook_suffix == 'edit.php' && $_GET['post_type'] == SHORT_CPT )
		) {
			wp_enqueue_style  ( 'admin-shortcuts', SHORT_URL.'/ressources/admin.css', array(), SHORT_VERSION, 'all' );
			
			wp_enqueue_script ( 'jquery-cookie', SHORT_URL.'/ressources/jquery.cookie.js', array('jquery'), '1.0' );
			wp_enqueue_script ( 'jquery-ui-accordion', SHORT_URL.'/ressources/jquery.ui.accordion.js', array('jquery', 'jquery-ui-core', 'jquery-ui-widget'), '1.8' );
			wp_enqueue_script ( 'admin-shortcuts', SHORT_URL.'/ressources/admin.js', array('jquery-cookie', 'jquery-ui-accordion'), SHORT_VERSION );
			/*
			wp_localize_script( 'admin-shortcuts', 'translationL10n', array(
				'successText' => __('This translation is unique, fine...', 'shortcuts'),
				'errorText' => __('Duplicate translation detected !', 'shortcuts')
			) );
			*/
		}
	}
	
	/**
	 * Save datas of translation databox
	 *
	 * @param integer $object_id
	 * @param object $object
	 * @return void
	 * @author Amaury Balmer
	 */
	function saveDatasMetaBoxes( $object_id = 0, $object = null ) {
		if ( !isset($object) || $object == null ) {
			$object = get_post( $object_id );
		}
		
		if ( isset($_POST['_meta_settings']) && $_POST['_meta_settings'] == 'true' ) {
			update_post_meta( $object->ID, 'sticky_shortcut', (isset($_POST['sticky_shortcut']) ? true : false) );
			update_post_meta( $object->ID, 'pagination', (isset($_POST['pagination']) ? true : false) );
			update_post_meta( $object->ID, 'feed', (isset($_POST['feed']) ? true : false) );
			update_post_meta( $object->ID, 'template', stripslashes($_POST['template']) );
		}
		
		if ( isset($_POST['_meta_query']) && $_POST['_meta_query'] == 'true' ) {
			update_post_meta( $object->ID, 'query_mode', stripslashes($_POST['query_mode']) );
			
			// Clean array dynamic
			if ( isset($_POST['simple']['meta_query']) ) {
				foreach( $_POST['simple']['tax_query'] as $k => $tax_query ) {
					if ( empty($tax_query['taxonomy']) ) {
						unset($_POST['simple']['tax_query'][$k]);
					}
				}
			}
			if ( isset($_POST['simple']['meta_query']) ) {
				foreach( $_POST['simple']['meta_query'] as $k => $meta_query ) {
					if ( empty($meta_query['key']) ) {
						unset($_POST['simple']['meta_query'][$k]);
					}
				}
			}
			
			// Clean string values
			foreach( $_POST['simple'] as $key => $value ) {
				if ( is_string($value) )
					$_POST['simple'][$key] = stripslashes($value);
			}
			
			// Update simple in array
			update_post_meta( $object->ID, 'simple_query_posts', $_POST['simple'] );
			update_post_meta( $object->ID, 'query_posts', stripslashes($_POST['query_posts']) );
		}
	}
	
	/**
	 * Register metabox
	 *
	 * @param string $post_type
	 * @return void
	 * @author Amaury Balmer
	 */
	function registerMetaBox( $post_type ) {
		if ( !current_user_can('edit_'.SHORT_CPT) )
			return false;
		
		add_meta_box( SHORT_CPT.'-settings', __('Settings', 'shortcuts'), array(&$this, 'MetaboxSettings'), SHORT_CPT, 'side', 'core' );
		add_meta_box( SHORT_CPT.'-wp_query', __('WP_Query Parameters', 'shortcuts'), array(&$this, 'MetaboxQuery'), SHORT_CPT, 'normal', 'high' );
	}
	
	/**
	 * Some settings for rewriting and templates
	 *
	 * @param object $post
	 * @return void
	 * @author Amaury Balmer
	 */
	function MetaboxSettings( $post ) {
		$meta_value = get_post_meta( $post->ID, 'sticky_shortcut', true );
		echo '<p>' . "\n";
			echo '<label><input type="checkbox" name="sticky_shortcut" value="true" '.checked($meta_value, true, false).' /> '.__('Sticky the shortcut as first post ?', 'shortcuts').'</label><br />' . "\n";
		echo '</p>' . "\n";
		
		$meta_value = get_post_meta( $post->ID, 'pagination', true );
		echo '<p>' . "\n";
			echo '<label><input type="checkbox" name="pagination" value="true" '.checked($meta_value, true, false).' /> '.__('Allow pagination ?', 'shortcuts').'</label><br />' . "\n";
		echo '</p>' . "\n";
		
		$meta_value = get_post_meta( $post->ID, 'feed', true );
		echo '<p>' . "\n";
			echo '<label><input type="checkbox" name="feed" value="true" '.checked($meta_value, true, false).' /> '.__('Allow feed ?', 'shortcuts').'</label><br />' . "\n";
		echo '</p>' . "\n";
		
		$meta_value = get_post_meta( $post->ID, 'template', true );
		echo '<p>' . "\n";
			echo '<label for="template">'.__('Template files', 'shortcuts').'</label><br />' . "\n";
			echo '<input type="text" class="widefat" id="template" name="template" value="'.esc_attr(stripslashes($meta_value)).'" />' . "\n";
			echo '<span class="description">' . __('You can leave this field empty. By default, the file archive-shortcut.php will be used for display content. If this file not exists, WordPress will try to load archive.php, or index.php', 'shortcuts') . '</span>';
		echo '</p>' . "\n";
		
		echo '<input type="hidden" name="_meta_settings" value="true" />';
	}
	
	/**
	 * A form for build the query
	 *
	 * @param object $post
	 * @return void
	 * @author Amaury Balmer
	 */
	function MetaboxQuery( $post ) {
		$query_mode = get_post_meta( $post->ID, 'query_mode', true );
		if ( $query_mode == false )
			$query_mode = 'simple';
			
		$simple_query_posts = get_post_meta($post->ID, 'simple_query_posts', true);
		$simple_query_posts = $this->defaultQueryVars( $simple_query_posts );
		
		echo '<p>' . "\n";
			echo '<label style="font-weight:700">'.__('Query mode', 'shortcuts').'</label><br />' . "\n";
			echo '<label><input type="radio" name="query_mode" value="simple" '.checked($query_mode, 'simple', false).' /> '.__('Simple mode', 'shortcuts').'</label><br />' . "\n";
			echo '<label><input type="radio" name="query_mode" value="advanced" '.checked($query_mode, 'advanced', false).' /> '.__('Advanced mode', 'shortcuts').'</label><br />' . "\n";
			echo '<span class="description hide-if-js">' . __('You must save for switch the mode.', 'shortcuts') . '</span>';
		echo '</p>' . "\n";
		
		echo '<div id="simple-mode-query" class="'.(($query_mode != 'simple')?'hide-if-no-js':'').'">' . "\n";
			echo '<div id="accordion">' . "\n";
				echo '<h3><a href="#">'.__('Author Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="author">'.__('author', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="author" name="simple[author]" value="'.esc_attr(stripslashes($simple_query_posts['author'])).'" />' . "\n";
						echo '<span class="description">' . __('(int) - use author id.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label for="author_name">'.__('author_name', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="author_name" name="simple[author_name]" value="'.esc_attr(stripslashes($simple_query_posts['author_name'])).'" />' . "\n";
						echo '<span class="description">' . __('(string) - use \'user_nicename\' (NOT name).', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Category Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="cat">'.__('cat', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="cat" name="simple[cat]" value="'.esc_attr(stripslashes($simple_query_posts['cat'])).'" />' . "\n";
						echo '<span class="description">' . __('(int) - use category id.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label for="category_name">'.__('category_name', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="category_name" name="simple[category_name]" value="'.esc_attr(stripslashes($simple_query_posts['category_name'])).'" />' . "\n";
						echo '<span class="description">' . __('(string) - use category slug (NOT name).', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="category__and">'.__('category__and', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="category__and" name="simple[category__and]" value="'.esc_attr(stripslashes($simple_query_posts['category__and'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use category id.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label for="category__in">'.__('category__in', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="category__in" name="simple[category__in]" value="'.esc_attr(stripslashes($simple_query_posts['category__in'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use category id.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="category__not_in">'.__('category__not_in', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="category__not_in" name="simple[category__not_in]" value="'.esc_attr(stripslashes($simple_query_posts['category__not_in'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use category id.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Tag Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="tag">'.__('tag', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="tag" name="simple[tag]" value="'.esc_attr(stripslashes($simple_query_posts['tag'])).'" />' . "\n";
						echo '<span class="description">' . __('(string) - use tag slug.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label for="tag_id">'.__('tag_id', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="tag_id" name="simple[tag_id]" value="'.esc_attr(stripslashes($simple_query_posts['tag_id'])).'" />' . "\n";
						echo '<span class="description">' . __('(int) - use tag id.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="tag__and">'.__('tag__and', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="tag__and" name="simple[tag__and]" value="'.esc_attr(stripslashes($simple_query_posts['tag__and'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use tag ids.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label for="tag__in">'.__('tag__in', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="tag__in" name="simple[tag__in]" value="'.esc_attr(stripslashes($simple_query_posts['tag__in'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use tag ids.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="tag__not_in">'.__('tag__not_in', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="tag__not_in" name="simple[tag__not_in]" value="'.esc_attr(stripslashes($simple_query_posts['tag__not_in'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use tag ids.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="tag_slug__and">'.__('tag_slug__and', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="tag_slug__and" name="simple[tag_slug__and]" value="'.esc_attr(stripslashes($simple_query_posts['tag_slug__and'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use tag slugs.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="tag_slug__in">'.__('tag_slug__in', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="tag_slug__in" name="simple[tag_slug__in]" value="'.esc_attr(stripslashes($simple_query_posts['tag_slug__in'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use tag slugs.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Taxonomy Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					if ( empty($simple_query_posts['tax_query']) ) {
						$simple_query_posts['tax_query'] = array();
					}
					
					// Always add a empty condition
					$simple_query_posts['tax_query'][] = array('taxonomy' => '', 'field' => '', 'terms' => '', 'operator' => '');
					
					// Display form for each meta query
					$i = 0;
					foreach( $simple_query_posts['tax_query'] as $tax_query ) {
						$i++;
						$this->addFormTaxoMetaQuery( $i, $tax_query );
					}
					echo '<div class="clear"></div>' . "\n";
					
					// Display relation if >1 tax query
					echo '<div id="relation_tax_query_wrap" style="'.(count($simple_query_posts['tax_query']) > 1 ? 'display:block;':'display:none;').'">' . "\n";
						echo '<hr />' . "\n";
						echo '<p>' . "\n";
							echo '<label for="relation">'.__('relation', 'shortcuts').'</label><br />' . "\n";
							echo '<input type="text" class="widefat" id="relation" name="simple[tax_query_relation]" value="'.esc_attr(stripslashes($simple_query_posts['tax_query_relation'])).'" />' . "\n";
							echo '<span class="description">' . __("(string) - Logical Operators. Possible values are 'OR', 'AND'.", 'shortcuts') . '</span>';
						echo '</p>' . "\n";
					echo '</div>' . "\n";
					
					echo '<a href="#" class="button hide-if-no-js" id="add-another-tax_query">' . __('Add an another tax query', 'shortcuts') . '</a>';
					echo '<p class="description hide-if-js">' . __('You must save for add an another tax query filters.', 'shortcuts') . '</p>';
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Post & Page Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="p">'.__('p', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="p" name="simple[p]" value="'.esc_attr(stripslashes($simple_query_posts['p'])).'" />' . "\n";
						echo '<span class="description">' . __('(int) - use post id.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label for="name">'.__('name', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="name" name="simple[name]" value="'.esc_attr(stripslashes($simple_query_posts['name'])).'" />' . "\n";
						echo '<span class="description">' . __('(string) - use post slug.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="page_id">'.__('page_id', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="page_id" name="simple[page_id]" value="'.esc_attr(stripslashes($simple_query_posts['page_id'])).'" />' . "\n";
						echo '<span class="description">' . __('(int) - use page id.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label for="pagename">'.__('pagename', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="pagename" name="simple[pagename]" value="'.esc_attr(stripslashes($simple_query_posts['pagename'])).'" />' . "\n";
						echo '<span class="description">' . __('(string) - use page slug.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="post_parent">'.__('post_parent', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="post_parent" name="simple[post_parent]" value="'.esc_attr(stripslashes($simple_query_posts['post_parent'])).'" />' . "\n";
						echo '<span class="description">' . __('(int) - use page id. Return just the child Pages.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="post__in">'.__('post__in', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="post__in" name="simple[post__in]" value="'.esc_attr(stripslashes($simple_query_posts['post__in'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use post ids. Specify posts to retrieve.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="post__not_in">'.__('post__not_in', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="post__not_in" name="simple[post__not_in]" value="'.esc_attr(stripslashes($simple_query_posts['post__not_in'])).'" />' . "\n";
						echo '<span class="description">' . __('(array) - use post ids. Specify post NOT to retrieve.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Type & Status Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label>'.__('post_type', 'shortcuts').'</label><br />' . "\n";
						echo '<label class="label-lightbox"><input type="checkbox" name="simple[post_type][]" value="any" '.checked( in_array('any', (array) $simple_query_posts['post_type']), true, false ).' /> '.__("'any' - retrieves any type except revisions", 'shortcuts').'</label>' . "\n";
						foreach( get_post_types( array(), 'objects' ) as $cpt ) {
							echo '<label class="label-lightbox"><input type="checkbox" name="simple[post_type][]" value="'.$cpt->name.'" '.checked( in_array($cpt->name, (array) $simple_query_posts['post_type']), true, false ).' /> '.$cpt->labels->name.'</label>' . "\n";
						}
						echo '<span class="description">' . __("(string / array) - use post types. Retrieves posts by Post Types, default value is 'post'", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label>'.__('post_status', 'shortcuts').'</label><br />' . "\n";
						foreach( get_post_stati( array(), 'objects' ) as $status ) {
							echo '<label class="label-lightbox"><input type="checkbox" name="simple[post_status][]" value="'.$status->name.'" '.checked( in_array($status->name, (array) $simple_query_posts['post_status']), true, false ).' /> '.$status->label.'</label>' . "\n";
						}
						echo '<span class="description">' . __("(string / array) - use post status. Retrieves posts by Post Status, default value is 'publish'", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Pagination Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="posts_per_page">'.__('posts_per_page', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="posts_per_page" name="simple[posts_per_page]" value="'.esc_attr(stripslashes($simple_query_posts['posts_per_page'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - number of post to show per page (available with Version 2.1). Use 'posts_per_page'=>-1 to show all posts. Note if the query is in a feed, wordpress overwrites this parameter with the stored 'posts_per_rss' option. To reimpose the limit, try using the 'post_limits' filter.", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="nopaging">'.__('nopaging', 'shortcuts').'</label><br />' . "\n";
						echo '<select name="simple[nopaging]" id="nopaging" class="widefat">' . "\n";
							foreach( $this->getTrueFalse() as $type_key => $type_name ) {
								echo '<option '.selected($simple_query_posts['nopaging'], $type_key, false).' value="'.esc_attr($type_key).'">'.esc_html($type_name).'</option>' . "\n";
							}
						echo '</select>' . "\n";
						echo '<span class="description">' . __("(bool) - show all posts or use pagination. Default value is 'false', use paging.", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				
					echo '<p>' . "\n";
						echo '<label for="paged">'.__('paged', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="paged" name="simple[paged]" value="'.esc_attr(stripslashes($simple_query_posts['paged'])).'" />' . "\n";
						echo '<span class="description">' . __('(int) - number of page. Show the posts that would normally show up just on page X when using the "Older Entries" link.', 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Offset Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="offset">'.__('offset', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="offset" name="simple[offset]" value="'.esc_attr(stripslashes($simple_query_posts['offset'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - number of post to displace or pass over.", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Order & Orderby Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="order">'.__('order', 'shortcuts').'</label><br />' . "\n";
						echo '<select class="widefat" id="order" name="simple[order]">' . "\n";
							echo '<option value="DESC" '.selected('DESC', $simple_query_posts['order'], false).'>'.__('DESC - descending order from highest to lowest values (3, 2, 1; c, b, a).', 'shortcuts').'</option>' . "\n";
							echo '<option value="ASC" '.selected('ASC', $simple_query_posts['order'], false).'>'.__('ASC - ascending order from lowest to highest values (1, 2, 3; a, b, c).', 'shortcuts').'</option>' . "\n";
						echo '</select>' . "\n";
						echo '<span class="description">' . __("(string) - Designates the ascending or descending order of the 'orderby' parameter.", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="orderby">'.__('orderby', 'shortcuts').'</label><br />' . "\n";
						echo '<select class="widefat" id="orderby" name="simple[orderby]">' . "\n";
							echo '<option value="date" '.selected('date', $simple_query_posts['orderby'], false).'>'.__("'date' - Order by date. (default, if not set to none)", 'shortcuts').'</option>' . "\n";
							echo '<option value="none" '.selected('none', $simple_query_posts['orderby'], false).'>'.__("'none' - No order (available with Version 2.8).", 'shortcuts').'</option>' . "\n";
							echo '<option value="id" '.selected('id', $simple_query_posts['orderby'], false).'>'.__("'id' - Order by post id.", 'shortcuts').'</option>' . "\n";
							echo '<option value="author" '.selected('author', $simple_query_posts['orderby'], false).'>'.__("'author' - Order by author.", 'shortcuts').'</option>' . "\n";
							echo '<option value="title" '.selected('title', $simple_query_posts['orderby'], false).'>'.__("'title' - Order by title.", 'shortcuts').'</option>' . "\n";
							echo '<option value="modified" '.selected('modified', $simple_query_posts['orderby'], false).'>'.__("'modified' - Order by last modified date.", 'shortcuts').'</option>' . "\n";
							echo '<option value="parent" '.selected('parent', $simple_query_posts['orderby'], false).'>'.__("'parent' - Order by post/page parent id.", 'shortcuts').'</option>' . "\n";
							echo '<option value="rand" '.selected('rand', $simple_query_posts['orderby'], false).'>'.__("'rand' - Random order.", 'shortcuts').'</option>' . "\n";
							echo '<option value="comment_count" '.selected('comment_count', $simple_query_posts['orderby'], false).'>'.__("'comment_count' - Order by number of comments (available with Version 2.9).", 'shortcuts').'</option>' . "\n";
							echo '<option value="menu_order" '.selected('menu_order', $simple_query_posts['orderby'], false).'>'.__("'menu_order' - Order by Page Order. Used most often for Pages (Order field in the Edit Page Attributes box) and for attachments (the integer fields in the Insert / Upload Media Gallery dialog), but could be used for any post type with distinct 'menu_order' values (they all default to 0).", 'shortcuts').'</option>' . "\n";
							echo '<option value="meta_value" '.selected('meta_value', $simple_query_posts['orderby'], false).'>'.__("'meta_value' - Note that a 'meta_key=keyname' must also be present in the query. Note also that the sorting will be alphabetical which is fine for strings (i.e. words), but can be unexpected for numbers (e.g. 1, 3, 34, 4, 56, 6, etc, rather than 1, 3, 4, 6, 34, 56 as you might naturally expect).", 'shortcuts').'</option>' . "\n";
							echo '<option value="meta_value_num" '.selected('meta_value_num', $simple_query_posts['orderby'], false).'>'.__("'meta_value_num' - Order by numeric meta value (available with Version 2.8). Also note that a 'meta_key=keyname' must also be present in the query. This value allows for numerical sorting as noted above in 'meta_value'. ", 'shortcuts').'</option>' . "\n";
						echo '</select>' . "\n";
						echo '<span class="description">' . __("(string) - Sort retrieved posts by.", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Sticky Post Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="ignore_sticky_posts">'.__('ignore_sticky_posts', 'shortcuts').'</label><br />' . "\n";
						echo '<select name="simple[ignore_sticky_posts]" id="ignore_sticky_posts" class="widefat">' . "\n";
							foreach( $this->getTrueFalse() as $type_key => $type_name ) {
								echo '<option '.selected($simple_query_posts['ignore_sticky_posts'], $type_key, false).' value="'.esc_attr($type_key).'">'.esc_html($type_name).'</option>' . "\n";
							}
						echo '</select>' . "\n";
						echo '<span class="description">' . __("(bool) - ignore sticky posts or not. Default value is 0, don't ignore. Ignore/exclude sticky posts being included at the beginning of posts returned, but the sticky post will still be returned in the natural order of that list of posts returned.", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Time Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					echo '<p>' . "\n";
						echo '<label for="year">'.__('year', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="year" name="simple[year]" value="'.esc_attr(stripslashes($simple_query_posts['year'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - 4 digit year (e.g. 2011).", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="monthnum">'.__('monthnum', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="monthnum" name="simple[monthnum]" value="'.esc_attr(stripslashes($simple_query_posts['monthnum'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - Month number (from 1 to 12).", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="w">'.__('w', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="w" name="simple[w]" value="'.esc_attr(stripslashes($simple_query_posts['w'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - Week of the year (from 0 to 53). Uses the MySQL WEEK command Mode=1.", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="day">'.__('day', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="day" name="simple[day]" value="'.esc_attr(stripslashes($simple_query_posts['day'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - Day of the month (from 1 to 31).", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="hour">'.__('hour', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="hour" name="simple[hour]" value="'.esc_attr(stripslashes($simple_query_posts['hour'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - Hour (from 0 to 23).", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="minute">'.__('minute', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="minute" name="simple[minute]" value="'.esc_attr(stripslashes($simple_query_posts['minute'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - Minute (from 0 to 60).", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<p>' . "\n";
						echo '<label for="second">'.__('second', 'shortcuts').'</label><br />' . "\n";
						echo '<input type="text" class="widefat" id="second" name="simple[second]" value="'.esc_attr(stripslashes($simple_query_posts['second'])).'" />' . "\n";
						echo '<span class="description">' . __("(int) - Second (0 to 60).", 'shortcuts') . '</span>';
					echo '</p>' . "\n";
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
				
				echo '<h3><a href="#">'.__('Custom Field Parameters', 'shortcuts').'</a></h3>' . "\n";
				echo '<div>' . "\n";
					if ( $simple_query_posts['meta_query'] == false ) {
						$simple_query_posts['meta_query'] = array();
					}
					
					// Always add a empty condition
					$simple_query_posts['meta_query'][] = array('key' => '', 'value' => '', 'compare' => '', 'type' => '');
					
					// Display form for each meta query
					$i = 0;
					foreach( $simple_query_posts['meta_query'] as $meta_query ) {
						$i++;
						$this->addFormPostMetaQuery( $i, $meta_query );
					}
					echo '<div class="clear"></div>' . "\n";
					
					echo '<a href="#" class="button hide-if-no-js" id="add-another-post_query">' . __('Add an another post meta query', 'shortcuts') . '</a>';
					echo '<p class="description hide-if-js">' . __('You must save for add an another post meta query filters.', 'shortcuts') . '</p>';
					
					echo '<div class="clear"></div>' . "\n";
				echo '</div>' . "\n";
			echo '</div>' . "\n";
		echo '</div>' . "\n";
		
		echo '<div id="advanced-mode-query" class="'.(($query_mode != 'advanced')?'hide-if-no-js':'').'">' . "\n";
			echo '<p>' . "\n";
				echo '<label for="query_posts">'.__('Query_posts', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="query_posts" name="query_posts" value="'.esc_attr(stripslashes(get_post_meta($post->ID, 'query_posts', true))).'" />' . "\n";
			echo '</p>' . "\n";
			
			echo '<p>' . "\n";
				echo '<span class="description">' . __('This field works the same way that the function query_posts(), you can spend the same parameters. The documentation is available on <a href="http://codex.wordpress.org/Function_Reference/query_posts">page query_posts codex</a>.', 'shortcuts') . '</span>';
			echo '</p>' . "\n";
			
			echo '<div class="clear"></div>' . "\n";
		echo '</div>' . "\n";
		
		echo '<input type="hidden" name="_meta_query" value="true" />';
	}
	
	/**
	 * Build HTML for taxo meta query condition, used by class form and ajax.
	 *
	 * @param integer $i 
	 * @param array $tax_query 
	 * @return void
	 * @author Amaury Balmer
	 */
	function addFormTaxoMetaQuery( $i, $tax_query = null ) {
		if ( is_null($tax_query) )
			$tax_query = array('taxonomy' => '', 'field' => '', 'terms' => '', 'operator' => '');
			
		echo '<div class="tax_meta_query_col" id="tax_query-'.$i.'">' . "\n";
			echo '<p class="subtitle-short">' . sprintf( __('Condition %d', 'shortcuts'), $i ) . '</p>' . "\n";
			
			echo '<p>' . "\n";
				echo '<label for="taxonomy">'.__('taxonomy', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="taxonomy" name="simple[tax_query]['.$i.'][taxonomy]" value="'.esc_attr(stripslashes($tax_query['taxonomy'])).'" />' . "\n";
				echo '<span class="description">' . __('(string) - Taxonomy.', 'shortcuts') . '</span>';
			echo '</p>' . "\n";
			
			echo '<p>' . "\n";
				echo '<label for="field">'.__('field', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="field" name="simple[tax_query]['.$i.'][field]" value="'.esc_attr(stripslashes($tax_query['field'])).'" />' . "\n";
				echo '<span class="description">' . __('(string) - Select taxonomy term by (\'id\' or \'slug\')', 'shortcuts') . '</span>';
			echo '</p>' . "\n";
			
			echo '<p>' . "\n";
				echo '<label for="terms">'.__('terms', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="terms" name="simple[tax_query]['.$i.'][terms]" value="'.esc_attr(stripslashes($tax_query['terms'])).'" />' . "\n";
				echo '<span class="description">' . __('(int/string/array) - Taxonomy term(s).', 'shortcuts') . '</span>';
			echo '</p>' . "\n";
			
			echo '<p>' . "\n";
				echo '<label for="operator">'.__('operator', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="operator" name="simple[tax_query]['.$i.'][operator]" value="'.esc_attr(stripslashes($tax_query['operator'])).'" />' . "\n";
				echo '<span class="description">' . __("(string) - Operator to test. Possible values are 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'.", 'shortcuts') . '</span>';
			echo '</p>' . "\n";
		echo '</div>' . "\n";
	}
	
	/**
	 * Function called by AJAX hook for build meta query
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxBuildTaxoMetaQuery() {
		if ( !isset($_REQUEST['counter']) || (int) $_REQUEST['counter'] == 0 )
			die();
			
		$this->addFormTaxoMetaQuery( $_REQUEST['counter'] + 1, null );
		die();
	}
	
	/**
	 * Build HTML for post meta query condition, used by class form and ajax.
	 *
	 * @param integer $i 
	 * @param array $meta_query 
	 * @return void
	 * @author Amaury Balmer
	 */
	function addFormPostMetaQuery( $i, $meta_query = null ) {
		if ( is_null($meta_query) )
			$meta_query = array('key' => '', 'value' => '', 'compare' => '', 'type' => '');
			
		echo '<div class="post_meta_query_col" id="post_query-'.$i.'">' . "\n";
			echo '<p class="subtitle-short">' . sprintf( __('Condition %d', 'shortcuts'), $i ) . '</p>' . "\n";
			
			echo '<p>' . "\n";
				echo '<label for="key">'.__('key', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="key" name="simple[meta_query]['.$i.'][key]" value="'.esc_attr(stripslashes($meta_query['key'])).'" />' . "\n";
				echo '<span class="description">' . __("(string) - Custom field key.", 'shortcuts') . '</span>';
			echo '</p>' . "\n";
			echo '<p>' . "\n";
				echo '<label for="value">'.__('value', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="offset" name="simple[meta_query]['.$i.'][value]" value="'.esc_attr(stripslashes($meta_query['value'])).'" />' . "\n";
				echo '<span class="description">' . __("(string) - Custom field value.", 'shortcuts') . '</span>';
			echo '</p>' . "\n";
			echo '<p>' . "\n";
				echo '<label for="compare">'.__('compare', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="compare" name="simple[meta_query]['.$i.'][compare]" value="'.esc_attr(stripslashes($meta_query['compare'])).'" />' . "\n";
				echo '<span class="description">' . __("(string) - Operator to test. Possible values are '=', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'. Default value is '='.", 'shortcuts') . '</span>';
			echo '</p>' . "\n";
			echo '<p>' . "\n";
				echo '<label for="type">'.__('type', 'shortcuts').'</label><br />' . "\n";
				echo '<input type="text" class="widefat" id="type" name="simple[meta_query]['.$i.'][type]" value="'.esc_attr(stripslashes($meta_query['type'])).'" />' . "\n";
				echo '<span class="description">' . __("(string) - Custom field type. Possible values are 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'. Default value is 'CHAR'.", 'shortcuts') . '</span>';
			echo '</p>' . "\n";
		echo '</div>' . "\n";
	}
	
	/**
	 * Function called by AJAX hook for build meta query
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxBuildPostMetaQuery() {
		if ( !isset($_REQUEST['counter']) || (int) $_REQUEST['counter'] == 0 )
			die();
			
		$this->addFormPostMetaQuery( $_REQUEST['counter'] + 1, null );
		die();
	}
	
	/**
	 * Use for build selector
	 * 
	 * @param $key
	 * @return string/array
	 */
	function getTrueFalse( $key = '' ) {
		$types = array( 
			'0' => __('False', 'shortcuts'),
			'1' => __('True', 'shortcuts')
		);
		
		if ( isset($types[$key]) ) {
			return $types[$key];
		}
		
		return $types;
	}
	
	/**
	 * Add columns for post type
	 *
	 * @param array $defaults
	 * @param string $post_type
	 * @return array
	 * @author Amaury Balmer
	 */
	function addColumns( $defaults, $post_type ) {
		if ( $post_type == SHORT_CPT && current_user_can('edit_'.SHORT_CPT) ) {
			$defaults['shortcut-pagination'] 	= __('Pagination', 'shortcuts');
			$defaults['shortcut-feed'] 			= __('Feed', 'shortcuts');
			$defaults['shortcut-template'] 		= __('Template file', 'shortcuts');
		}
		
		return $defaults;
	}
	
	/**
	 * Display value of each custom column for shortcut
	 *
	 * @param string $column_name
	 * @param integer $object_id
	 * @return void
	 * @author Amaury Balmer
	 */
	function addColumnValue( $column_name, $object_id ) {
		switch( $column_name ) {
			case 'shortcut-pagination':
				$pagination = get_post_meta( $object_id, 'pagination', true);
				echo ( $pagination == true ) ? __('True', 'shortcuts') : __('False', 'shortcuts');
				break;
			case 'shortcut-feed':
				$feed = get_post_meta( $object_id, 'feed', true);
				echo ( $feed == true ) ? __('True', 'shortcuts') : __('False', 'shortcuts');
				break;
			case 'shortcut-template':
				$templates = get_post_meta( $object_id, 'template', true);
				echo $templates;
				break;
		}
	}
	
	/**
	 * Manage default values for WP_Query
	 *
	 * @param array $db_values 
	 * @return array
	 * @author Amaury Balmer
	 */
	function defaultQueryVars( $db_values ) {
		$keys = array(
			'error'
			, 'm'
			, 'p'
			, 'post_parent'
			, 'subpost'
			, 'subpost_id'
			, 'attachment'
			, 'attachment_id'
			, 'name'
			, 'static'
			, 'pagename'
			, 'page_id'
			, 'second'
			, 'minute'
			, 'hour'
			, 'day'
			, 'monthnum'
			, 'year'
			, 'w'
			, 'category_name'
			, 'tag'
			, 'cat'
			, 'tag_id'
			, 'author_name'
			, 'feed'
			, 'tb'
			, 'paged'
			, 'comments_popup'
			, 'meta_key'
			, 'meta_value'
			, 'preview'
			, 's'
			, 'sentence'
			, 'fields'
			, 'category__in'
			, 'category__not_in'
			, 'category__and'
			, 'post__in'
			, 'post__not_in'
			, 'tag__in'
			, 'tag__not_in'
			, 'tag__and'
			, 'tag_slug__in'
			, 'tag_slug__and'
			, 'author'
			, 'tax_queries'
			, 'post_type'
			, 'post_status'
			, 'posts_per_page'
			, 'offset'
			, 'meta_query'
		);
		
		foreach ( $keys as $key ) {
			if ( !isset($db_values[$key]) )
				$db_values[$key] = '';
		}
		
		return $db_values;
	}
}
?>