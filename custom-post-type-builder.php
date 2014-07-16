<?php
class CustomPostTypeBuilder {

	private $single_template_loc;
	private $archive_template_loc;
	private $post_type_name;
	private $post_type_slug;
		
	private $meta_boxes;
	private $post_type_arguments;
			
	function __construct($handle, $post_info, $single, $archive = 0) {
		$this->single_template_loc = $single;
		$this->archive_template_loc = $archive;
		$this->post_type_slug = $handle;		
		
		$this->register($post_info);
				
		add_filter('template_include', array($this, 'include_template'));
		add_action('save_post_'.$this->post_type_slug, array($this, 'save'), 10, 2);
		$this->increment = 0;
	}
	
	function include_template($template) {
		$post_type = get_post_type();
		if ( $post_type == $this->post_type_slug) {
			remove_filter('the_content', 'wpautop');
			if (is_single()) {
				$template = $this->single_template_loc;
				if (!$template ) {
					echo $template;
					exit;
				}
			} elseif (is_archive()) {
				$template = $template = $this->archive_template_loc;
				if (!$template) {
					echo $template;
					exit;
				}
			}
		} 
		return $template;
	}
	
	function load_meta_boxes() {
		foreach($this->meta_boxes as $box_name => $box) {
			if ($box['params']['callback']) {
				$callback = $box['params']['callback'];
			} else {
				$wp_postmeta = $box['wp_postmeta']; 

				if ($box['input']) {
					$attrs = $box['input'];					
					$callback = function() use($wp_postmeta, $attrs, $box_name) {
						global $post;
						$meta = get_post_meta($post->ID, $wp_postmeta, true);
						echo '<input name="'.$wp_postmeta.'" value="'.$meta.'"';
						foreach($attrs as $attr => $val) {
							echo $attr.'="'.$val.'"';
						}
						echo '>';
						wp_nonce_field( $box_name, $box_name.'_nonce');
					};				
				} elseif ($box['editor']) {
					$settings = $box['editor']['settings'];
					
					$callback = function() use($wp_postmeta, $settings, $box_name) {
						global $post;
						$meta = get_post_meta($post->ID, $wp_postmeta, true);					
						wp_editor($meta, $wp_postmeta, $settings);
					 	wp_nonce_field($box_name, $box_name.'_nonce');
					};
				}
			}
			
			add_meta_box($box['params']['id'], 
				$box['params']['title'], 
				$callback, 
				$this->post_type_slug, 
				$box['params']['context'], 
				$box['params']['priority']);	
		}
	}

	function add_meta_boxes($boxes) {
		add_action('load-post.php', array($this, 'load_meta_boxes'));
		add_action('load-post-new.php', array($this, 'load_meta_boxes'));
		foreach ($boxes as $box) {
			$this->meta_boxes[$box['params']['id']] = $box;
		}
	}	
	
	function save($post_id) {
		global $post;

		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
			return $post_id;

		$post_type = get_post_type_object($post->post_type);

		if ($post_type == $this->post_type_slug) {
			if (!current_user_can('edit_page', $post_id))
				return $post_id;
		} else {
			if (!current_user_can('edit_post', $post_id))
				return $post_id;
		}

		if(is_array($this->meta_boxes)) {		
			foreach($this->meta_boxes as $id => $box) {			
				$nonce_id = $id.'_nonce';
				if ( ! isset( $_POST[$nonce_id] ) )
					return $post_id;
				
				$nonce = $_POST[$nonce_id];
	
				if (!wp_verify_nonce($nonce, $id) )
					return $post_id;

				$name = $box['wp_postmeta']; 
				$new_value = (isset($_POST[$name]) ? wp_kses_post( $_POST[$name] ) : '' );
				
				update_post_meta($post_id, $name, $new_value);
			}
		}
	}
	
	function register($args) {
		$args['labels']['menu_name'] = $args['labels']['name'];
		$args['labels']['add_new'] = 'Add New';
		$args['labels']['add_new_item'] = 'Add New '.$args['labels']['singular_name'];
		$args['labels']['edit'] = 'Edit';
		$args['labels']['edit_item'] = 'Edit '.$args['labels']['singular_name'];
		$args['labels']['new_item'] = 'New '.$args['labels']['singular_name'];
		
		$args['labels']['view'] = 'View';
		$args['labels']['view_item'] = 'View '.$args['labels']['singular_name'];
		$args['labels']['all_items'] = 'All '.$args['labels']['name'];
		$args['labels']['search_items'] = 'Search '.$args['labels']['name'];
		$args['labels']['not_found'] = 'No '.$args['labels']['name'].' Found.';
		$args['labels']['not_found_in_trash'] = 'No '.$args['labels']['name'].' Found in Trash.';
		
		register_post_type($this->post_type_slug, $args);				
	}
	
	private function debug($tag, $var) {
		?>
        <script>
		console.log("<?php echo $tag.': '.$var; ?>");
		</script>
		<?php
	}

}