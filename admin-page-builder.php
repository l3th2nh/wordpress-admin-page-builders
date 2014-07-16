<?php
class AdminPageBuilder {

	protected $menu_slug;	
	protected $page_title;
	protected $menu_title;

	protected $form_action;
	public $groups;
	public $menu_url;
	
	public $field_class;
	
	function __construct($menu) {
		$f_name = $menu['function']['name'];
		$f_params = $menu['function']['params'];
		$priority = $menu['priority'];

		$this->field_class = $menu['form']['field_class'];
		$this->menu_slug = $f_params['menu_slug'];
		$this->menu_title = $f_params['menu_title'];
		$this->page_title = $f_params['page_title'];
		
		$this->form_action = $menu['form']['form_action'];
		
		$this->options = array();
		$f_params['callback'] = array($this, 'display_menu_page');
		add_action('admin_menu', function() use ($f_name, $f_params) {
			call_user_func_array($f_name, $f_params);								
		}, $priority);
	}

	function display_errors() {
		if (isset($_GET['message'])) {
			echo '<div id="message" class="updated fade"><p><strong>'.$_GET['message'].'</strong></p></div>';
		}
		
	}
	
	function display_menu_page() {		
		echo '<div class="wrap">';
		
		echo '<h2>'.$this->page_title.'</h2>'; 
		
		$this->display_errors();				
		//Add message to top of display
	
		//Add Tabs, if any
		if (is_array($this->groups)) {
			$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : $this->groups[0]['tab_id'];
			echo '<h2 class="nav-tab-wrapper">';		
			foreach($this->groups as $tab) {
				echo '<a href="'.$this->menu_url.'&tab='.$tab['tab_id'].'" class="nav-tab">'.$tab['header'].'</a>';
			}
			echo '</h2>';
		}
		
		echo '<form method="post" action="'.$this->form_action.'">';
		
		if (is_array($this->groups)) {
			foreach($this->groups as $group) {
				if ($active_tab == $group['tab_id']) {
					$this->display_group($group['group'], $group['section'], $tab['note']);				
				}
			}
		} else {
			$this->display_fields();
		}
		submit_button($form_data['submit_text']);
		
		echo '</form></div>';
	}

	function display_group() {}
	function display_fields() {}
	
	function submit_text_file() {
		if ( !current_user_can( 'manage_options' ))
		   wp_die( 'Not allowed' );
		
		$allowed_extensions = array("txt");
		$columns = $this->importer->get_columns();

		foreach ($_FILES as $file) {
			$extension = end(explode(".", $file["name"]));

			if (!in_array($extension, $allowed_extensions)) {
				$message = 'Invalid File Type.';
				break;
			} elseif ($file['error'] == UPLOAD_ERR_NO_FILE) {
				$message = 'No file was uploaded';
				break;
			} elseif ($file['error'] > 0) {
				$message = "Error: " . $file["error"] . "<br>";
				break;
			} else {
				
				$file = fopen($file["tmp_name"], 'r');	
				
				if (!$columns) {
					$columns = fgetcsv($file, 0, "\t");
				}

				do {
					$temp_row = fgetcsv($file, 0, "\t");
					if ($temp_row) {
						foreach ($temp_row as &$cell) {
							$cell = htmlspecialchars($cell);
						}
						$rows[] = array_combine($columns, $temp_row);
					}
				} while(!feof($file));
				
				$this->importer->parse($rows);
				
				fclose($file);
				$message = htmlspecialchars($file["name"].'+Successfully+Uploaded!');
			}
		}
		wp_redirect(add_query_arg(array('page'=>$this->menu_handle, 'message'=>$message), admin_url('admin.php')));	
	}
	
	
}