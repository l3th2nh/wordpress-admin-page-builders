<?php
require_once(dirname(__FILE__).'/admin-page-builder.php');

class SettingsPageBuilder extends AdminPageBuilder {

	private $sections;
	public $groups;
	protected $options;
	
	function __construct($menu) {
		parent::__construct($menu);
		$this->options = array();
		$tabs = isset($menu['tabs']) ? $menu['tabs'] : '';
		$builder = $this;
		$this->menu_url = 'options-general.php?page='.$this->menu_slug;
		
		add_action('whitelist_options', array($this, 'whitelist_custom_options_page'), 11);
				
		add_action('admin_init', function() use ($builder, $tabs) {
			foreach($tabs as $tab) {
				$grouping = $this->menu_slug.'_'.$tab['settings_group'];
				$group_url = $this->menu_slug; //.'&tab='.$tab['settings_group'];
				$builder->groups[] = array(
					'group'		=>	$grouping,
					'section'	=> 	$group_url,
					'header' 	=> 	isset($tab['title']) ? $tab['title'] : '',
					'tab_id'	=> 	isset($tab['settings_group']) ? $tab['settings_group'] : '',
					'note'		=> 	isset($tab['note']) ? $tab['note'] : '' 
				);
				
				foreach($tab['sections'] as $section) {
					
					//add_action('update_option', array($this, 'process_options'));
					add_settings_section($section['id'], $section['title'], array($builder, 'add_section_header'), $grouping);
					if( $section['id'] != $grouping ){
						if( !isset($this->sections[$grouping])) {
							$this->sections[$grouping] = array();
						}
						//$this->sections[$grouping][] = $id;
					}
					
					
					foreach($section['fields'] as $field) {
					
						//$field['attributes']['name'] = $grouping.'['.$field['name'].']';
						$field_label = $field['label'];
						$field_id = $field['attributes']['name'];
						$this->sections[$grouping][] = $field_id;
						$builder->options[] = $field_id; 
						add_settings_field($field_id, $field_label, array($builder, 'add_field'), $grouping, $section['id'], $field);  
						
						if (isset($field['validation_callback'])) {
							if (is_array($field['validation_callback'])) {
								add_action('update_option_'.$field_id, array($builder, 'validate_checkbox'));
								$callback = array($builder, $field['validation_callback'][0]);
								//print_r($callback);
							} else {
								$callback = $field['validation_callback'];
							}
						} else {
							$callback = 'sanitize_text_field';
						}
						register_setting($grouping, $field_id, $callback);
					}
				}
			}
		});
	}
		
	function display_errors() {
		settings_errors();
	}
	
	function validate_checkbox($input) {
		//echo '<br>$_POST: ';
		//print_r($_POST);
		//echo '<br>Input: '.$input;
		//exit;		
		$valid_input = ( isset( $input ) && $input == 1) ? true : false;		
		//update_option();
		//echo $valid_input;
		//exit;
		return $valid_input;
	}
	
	function add_section_header() {
	
	}

	function add_field($field) {
		$echo = '<'.$field['tag'].' '; 
		//$echo .= 'id="'.$field['attributes']['name'].'" ';
		
		if (!$field['attributes']['class']) {
			$field['attributes']['class'] = $this->field_class;
		}
		
		if ($field['tag'] == 'select') {
			foreach ($field['attributes'] as $attr => $value) {
				$echo .= $attr.'="'.$value.'" ';
			}
			$echo .= '>';

			$value = $field['options']['return']['key'];
			$text = $field['options']['return']['value'];			
			$options = call_user_func_array($field['options']['function'], $field['options']['params']);			
				
			if ($field['options']['return']['type'] == 'object') {
				foreach ($options as $option) {
					$echo .= '<option value="'.$option->$value.'">'.$option->$text.'</option>';
				}
			} else if ($field['options']['return']['type'] == 'array') {
				foreach ($options as $option) {
					$echo .= '<option value="'.$option[$value].'">'.$option[$text].'</option>';
				}
			}
			
			$echo .= '</select>';	
		} elseif ($field['tag'] == 'fieldset') {
			foreach($field['fields'] as $f) {
				$this->add_field($f);			
			}
		} else {
			if ($field['attributes']['type'] == 'checkbox') {
				$echo .= (get_option($field['attributes']['name']) == 1) ? 'checked ' : '';				
				$field['value'] = 1;//(get_option($field['attributes']['name']) == 1) ? 0 : 1;
			} elseif (!isset($field['value'])) {
				$field['value'] = get_option($field['attributes']['name']);						
			}
			
			//Set attributes
			if(@ is_array($field['attributes'])) {
				foreach (@ $field['attributes'] as $attr => $v) {
					$echo .= $attr.'="'.$v.'" ';
				}
			}
				
			//set value
			if ($field['tag'] == 'textarea') {
				$echo .= '>'.$field['value'].'</textarea>';
			} else {
				$echo .= 'value="'.$field['value'].'"/>';
			}
		}
		echo $echo;
	}
		
	function display_group($group, $section, $note) {
		settings_fields($group);
 		do_settings_sections($group);	
		echo '<p>'.$note.'</p>';
	}
	
	public function whitelist_custom_options_page( $whitelist_options ){
	    // Custom options are mapped by section id; Re-map by page slug.
	    //echo 'Whitelist Options: ';
	    //print_r($whitelist_options);	    
	    foreach($this->sections as $page => $sections ) {
	        //echo '<br><br>Page: '.$page;
	        $whitelist_options[$page] = array();
	        foreach( $sections as $section ) {
	        	//echo '<br><br>Section: ';
	            //print_r($section);
				$whitelist_options[$page][] = $section;
	        }
	    }
		//exit;
	    return $whitelist_options;
	}
	
	
	function process_options($option, $oldvalue, $newvalue) {
		if ( !current_user_can( 'manage_options' ))
			wp_die( 'Not allowed' );
		
		check_admin_referer($this->menu_slug, '_wpnonce'); 
		
		//foreach($this->options as $option) {
			if (in_array($option, $this->options)) {
				update_option($option, $newvalue);//$_POST[$option]);
				//wp_redirect( add_query_arg( array('page'=>$this->menu_slug, 'message'=>1), admin_url($this->menu_url)));
			}
		//}
		
	}
	
	
	
	
}

?>
