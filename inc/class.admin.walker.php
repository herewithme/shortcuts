<?php
class Walker_Category_Checklist_Shortcut extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id'); //TODO: decouple this

	function start_lvl(&$output, $depth) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	function end_lvl(&$output, $depth) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	function start_el(&$output, $category, $depth, $name, $selected_cats) {
		$output .= "\n<li id='$name-category-$category->term_id'>" . 
		'<label for="' . $name . '-in-category-' . $category->term_id . '" class="selectit">
		<input value="' . $category->term_id . '" type="checkbox" name="categories[' . $name . '][]" id="' . $name . '-in-category-' . $category->term_id . '"' . (in_array( $category->term_id, (array) $selected_cats ) ? ' checked="checked"' : "" ) . '/> 
		' . wp_specialchars( apply_filters('the_category', $category->name )) . '</label>';
	}

	function end_el(&$output, $category, $depth) {
		$output .= "</li>\n";
	}
}
?>