<?php
/*
Plugin Name: Feed.nu
Plugin URI: http://wordpress.org/extend/plugins/feed-nu/
Description: Create a native Android app of your blog. With this plugin you can automatically generate your mobile app ready to deploy on android market or host it by yourself. No programming skills needed and it's instantly available. IPhone version comming soon. <a href="http://feed.nu">http://feed.nu</a>
Author: Feed.nu
Author URI: http://feed.nu/
Version: 1.1.22
License: GPLv2 or later
*/


/* AVOID DIRECT CALLS
 *
 * avoid direct calls to this file where
 * wp core files not present
 ************************************************************/
if(!defined('ABSPATH'))
die('Please do not load this file directly.');


class Feed_app_plugin {

	public $version = '1.1.22';
	private $cmd_cals = array('handle_apk', 'save_live_settings', 'save_general', 'get_live_settings','save_android_gui', 'get_tab', 'get_android_string_xml','get_android_color_xml','get_android_manifest','save_android_apk','get_android_arrays_xml');
	private $ajax_tabs = array('activity','your_apps','settings','ios','android','donate','changelog','live_settings','request_live_password');
	
	
	
	/**
	 * Construct
	 *
	 * @since 1.1.21
	 ************************************************************/
	function __construct() {
		add_action('admin_menu', array(&$this, 'on_admin_menu'));
		add_action("plugin_action_links_feed-nu/feed-nu.php", array(&$this, 'add_plugin_action_links'));
		add_action('admin_post_feed_cmd', array(&$this, 'cmd_call'));
		add_action('wp_ajax_feed_command', array(&$this, 'cmd_call'));
		
		add_filter('upload_mimes',array(&$this, 'add_apk_upload_support'));
		add_filter('feed_full_package_name',array(&$this, 'feed_full_package_name'));
		add_filter('feed_package_name',array(&$this, 'feed_package_name'));
		
		add_shortcode( 'no_app', array(&$this, 'no_app'));
		add_shortcode( 'have_app', array(&$this, 'have_app'));
		add_shortcode( 'app_qr', array(&$this, 'app_qr'));
		
		if(isset($_GET['fau'])) {
			add_action('init', array(&$this, 'downloadLatestAPK'));
		}
		else if( isset($_GET['action'], $_GET['feed_cmd']) && $_REQUEST['action'] == 'feed_cmd' && in_array($_REQUEST['feed_cmd'], $this->cmd_cals) ) {
			add_action('init', array(&$this, 'cmd_call'));
		}


	}


	/**
	 * Checks what client it is and redirekt if there is
	 * an app working for that client
	 *
	 * @since 1.1.12
	 */
	public function downloadLatestAPK() {
		global $blog_id;
		$appUrl = '';
		$user_agent = $_SERVER['HTTP_USER_AGENT'];

		if(preg_match('/ipad/i',$user_agent)){
			//Is ipad
		}
		else if(preg_match('/ipod/i',$user_agent)||preg_match('/iphone/i',$user_agent)){
			//Is ipod or iphone
		}
		else if(preg_match('/android/i',$user_agent)){
			//Is android

			$apk =& get_children( 'post_type=attachment&post_mime_type=application/vnd.android.package-archive' );

			foreach ( $apk as $attachid => $attach ) {
				if($attach->post_excerpt == 'live'){
					$appPath = get_attached_file( $attachid );
					break;
				}
			}
				
			$settings = get_option('feed_settings');
				
			if( isset( $settings['android']['general']['apphost'] ) ) {
				if( $settings['android']['general']['hostedAt'] == 'market' ){
					$domain = feed_get_blog_domain();
					$app_name = apply_filters('feed_full_package_name', $domain);
					$appUrl = "https://market.android.com/details?id=" . $app_name;
				}
			}
				
		}

		if($appUrl != '') {
			wp_redirect($appUrl);
		}
		else if($appPath != '') {
			$filename = basename($appPath);
			$filesize = filesize($appPath);
			header('Content-Type: application/vnd.android.package-archive');
			header('Content-Disposition: attachment; filename="'.$filename.'"');          
			header("Content-Length: " . $filesize);
			readfile($appPath);
			exit;
		}
		else{
			wp_die( __('No app is availible for your client. open this link with you mobile device') );
			//header('Location: ' . $appUrl);
		}
	}

	// 	public function init($param) {
	// 		load_plugin_textdomain( 'feed', false, dirname( plugin_basename( __FILE__ ) ) . '/language/' );
	// 	}

	/**
	 * Adds support for apk upload
	 *
	 * @since 1.1.0
	 ************************************************************/
	public function add_apk_upload_support($mimes) {
		$mimes['apk'] = 'application/vnd.android.package-archive';
		return $mimes;
	}

	/**
	 * @since 1.1.10
	 */
	public function feed_full_package_name($domain) {
		$mySettings = get_option('feed_settings');
		if(isset($mySettings['Android']['General']['packagename']['UserValue'])){
			return 'com.warting.blogg.'.$mySettings['Android']['General']['packagename']['UserValue'];
		}
		return 'com.warting.blogg.wis_' . preg_replace('/\W/', '_', $domain);
	}

	public function feed_package_name($domain) {
		$mySettings = get_option('feed_settings');
		if(isset($mySettings['Android']['General']['packagename']['UserValue'])){
			return $mySettings['Android']['General']['packagename']['UserValue'];
		}
		return 'wis_' . preg_replace('/\W/', '_', $domain);
	}



	/**
	 * Adds settings links in plugin mananger
	 *
	 * @since 1.1.4
	 ************************************************************/
	public function add_plugin_action_links($links) {
		array_unshift($links, '<a href="admin.php?page=feed-nu">'.__('Settings', 'feed').'</a>');
		return $links;
	}


	/**
	 * CMD CALLS
	 * Ajax forms are submited to this function as regular posts
	 * and trigger the cmd name function
	 *
	 * @since 1.1
	 ************************************************************/
	public function cmd_call() {
		if(in_array($_REQUEST['feed_cmd'], $this->cmd_cals)) {
			$cmd = $_REQUEST['feed_cmd'];
			$this->$cmd();
			die();
		}
	}



	/**
	 * BUILD MENU
	 * Creates menu on the left bottom, with an smartphone icon
	 *
	 * @since 1.1
	 ************************************************************/
	public function on_admin_menu() {
		$icon = WP_PLUGIN_URL . '/feed-nu/images/Smartphone-icon.png';
		$menu = add_menu_page('Feed', "Feed.nu", 'manage_options', 'feed-nu', array(&$this, 'on_show_connect'), $icon);
		add_action('load-'.$menu, array(&$this, 'on_load_menu'));
	}



	/**
	 * Returns the attachment id from an url
	 *
	 * @since 1.1
	 *
	 * @param string Attachment url
	 * @return int id
	 ************************************************************/
	private function get_attachment_id_from_src ($image_src) {
		global $wpdb;
		return $wpdb->get_var("SELECT ID FROM {$wpdb->posts} WHERE guid='$image_src'");
	}


	/**
	 * Includes some css and javascript when visiting the plugin
	 * 
	 * @since 1.1
	 ************************************************************/
	public function on_load_menu() {
		//TODO: compress js and css
		wp_enqueue_style('feed_nu_sprite', WP_PLUGIN_URL . '/feed-nu/css/sprite.css', false, $this->version);
		wp_enqueue_style('feed_nu_style', WP_PLUGIN_URL . '/feed-nu/css/feed_nu.css', false, $this->version);
		wp_enqueue_style('feed_nu_color_style', WP_PLUGIN_URL . '/feed-nu/css/colorpicker.css', false, $this->version);
		wp_enqueue_script('feed_nu_form', WP_PLUGIN_URL . '/feed-nu/js/jquery.form-2.8.0.js', false, $this->version);
		wp_enqueue_script('feed_nu_color_handler', WP_PLUGIN_URL . '/feed-nu/js/colorpicker.js', false, $this->version);
		wp_enqueue_script('feed_nu_hashchange', WP_PLUGIN_URL . '/feed-nu/js/jquery.hashchange-1.3.min.js', false, $this->version);
		wp_enqueue_script('feed_nu_js', WP_PLUGIN_URL . '/feed-nu/js/main.js', false, $this->version);
	}



	/**
	 * Builds the main interface the header, left/top menu
	 *
	 * @since 1.1
	 ************************************************************/
	public function on_show_connect() {
		include_once 'contents/tpl.php';
	}



	/**
	 * Shows the content on requested ajax call.
	 * Jquery only calls it once unless the pages is refreshed
	 *
	 * @since 1.1
	 ************************************************************/
	private function get_tab() {
		$tab_id = $_REQUEST['tab'];
		$content = dirname(__FILE__) . "/contents/$tab_id.php";
			
		// Include the page requested if whitelisted and file exist
		if ( in_array( $tab_id, $this->ajax_tabs ) && file_exists( $content ) )
		include_once $content;
		else
		_e('Sorry could not found what you where looking for');
	}



	/**
	 * make it a bit easier to create buttons shown on the right
	 * on both android/ios gui
	 *
	 * @since 1.1
	 *
	 * @param string $screen The phone screen it belongs to
	 * @param string $desc Description text (like a label)
	 * @param string $input A <input> string
	 * @return string $html Complite <input>
	 ************************************************************/
	private function add_div($screen, $desc, $input) {
		return "<div class='button feed_page_$screen'><span>$desc</span>$input</div>";
	}



	/**
	 * Used in the settings menu to create field inputs
	 *
	 * @since 1.1
	 *
	 * @param array $field Array from a xml category
	 * @param string $id The Key in that array to use
	 * @param string $h4 <h4> text
	 * @param string $type possible value: textarea, text
	 * @return string Complite feild
	 ************************************************************/
	private function add_field($field, $id, $h4, $type = 'text') {
		$field = $field[$id];
		$desc = (isset($field['Description']) && $field['Description']) ? '<br><small>'.$field['Description'].'</small>' : '';
		$html = "<div class='feed_$id'><h4>$h4 $desc</h4>";
		$standard = (isset($field['Standard'])) ? $field['Standard'] : '';
		$userValue = (isset($field['UserValue'])) ? $field['UserValue'] : $field['Standard'];
			
		// Its a radio
		if( isset( $field['options'] ) ) {
			foreach ($field['options']['option'] as $option) {
				$value = $option['value'];
				$desc = $option['Description'];
				$selected = ($value == $userValue) ? 'checked="checked"' : '';
				$html .= "<input type='radio' name='$id' value='$value' $selected/> $desc<br/>";
			}
		}

		// Its a textarea
		else if ($type == 'textarea') {
			$html .= "<textarea name=$id>$userValue</textarea>";
		}

		// Well, a regular input
		else {
			$html .= "<input type=$type name=$id placeholder='$standard' value='$userValue'>";
		}
		return $html."</div>";
	}



	/**
	 * Saves the form in Settings menu
	 *
	 * @since 1.1.17
	 ************************************************************/
	private function save_general() {

		$allSettings = $this->get_feed_settings();
		$newSettings = get_option('feed_settings', array());
		
		// Remove all old settings
		unset($newSettings['Android']['General']);
		unset($newSettings['Global']);
		
		// Put all new settings to $newSettings unless the value is not the standard value or empty
		foreach ($allSettings['Android']['General'] as $key => $value) {
			if( isset($_POST[$key]) && $_POST[$key] != '' && $_POST[$key] != $value['Standard'] ) {
				$newSettings['Android']['General'][$key]['UserValue'] = $_POST[$key];
			}
		}

		// Put all new settings to $newSettings unless the value is not the standard value or empty
		foreach ($allSettings['Global'] as $key => $value) {
			if( isset($_POST[$key]) && $_POST[$key] != '' && $_POST[$key] != $value['Standard'] ) {
				$newSettings['Global'][$key]['UserValue'] = $_POST[$key];
			}
			if( $key == 'app_date_format' && isset($_POST[$key]) && empty($_POST[$key])) {
				$newSettings['Global'][$key]['UserValue'] = "";
			}
		}

		$A_next_version_name = $_POST['A_next_version_name'];
		$A_next_version_code = $_POST['A_next_version_code'];
		
		
		$A_next_version_name = explode('.', $A_next_version_name);
		if(count($A_next_version_name) == 3){
			foreach ($A_next_version_name as $value) {
				if(is_numeric($value)){
					$newSettings['A_next_version_name'] = $_POST['A_next_version_name'];
				} else {
					unset($newSettings['A_next_version_name']);
					break;
				}
			}
		}
			
		if( is_numeric($A_next_version_code) ) {
			$newSettings['A_next_version_code'] = $A_next_version_code;
		}
			
		update_option('feed_settings', $newSettings);
		die('[Feed-plugin] saved general settings');
	}



	/**
	 * Removes all old Android settings and fill it with everything
	 * new from the form except General
	 *
	 * @since 1.1.13
	 ************************************************************/
	private function save_android_gui() {
		$allSettings = $this->get_feed_settings();
		$newSettings = get_option('feed_settings', array());

		// Remove all old settings
		unset($newSettings['Android']['Color']);
		unset($newSettings['Android']['Language']);

		// Put all new Language settings to $newSettings unless the value is not the standard value or empty
		foreach ($allSettings['Android']['Language'] as $key => $value) {
			if( isset($_POST[$key]) && $_POST[$key] != '' && $_POST[$key] != $value['Standard'] ) {
				$newSettings['Android']['Language'][$key]['UserValue'] = $_POST[$key];
			}
		}

		// Put all new Color settings to $newSettings unless the value is not the standard value or empty
		foreach ($allSettings['Android']['Color'] as $key => $value) {
			if( isset($_POST[$key]) && $_POST[$key] != '' && $_POST[$key] != $value['Standard'] ) {
				$newSettings['Android']['Color'][$key]['UserValue'] = $_POST[$key];
			}
		}

		// Put all new Image settings to $newSettings unless the value is not the standard value or empty
		foreach ($allSettings['Android']['Image'] as $name => $value) {
			if( isset($_FILES[$name]) && $_FILES[$name]['size'] > 0 && $_FILES[$name]['type'] == "image/png") {

				$file = wp_handle_upload($_FILES[$name], array('test_form' => false));

				$wp_filetype = wp_check_filetype(basename($file['file']), null );

				$aFile["name"] = $name.'.png';
				$aFile["type"] = $wp_filetype;
				$aFile["tmp_name"] = $file['file'];

				$newImageMediaId = media_handle_sideload( $aFile, null, $name );
				if(!is_wp_error( $newImageMediaId ) && $newImageMediaId > 0) {
						
					// Save new media url
					//wp_create_thumbnail($newImageMediaId,150);
					$imgUrl = wp_get_attachment_url($newImageMediaId);
						
					// Delete old image
					if(isset($allSettings['Android']['Image'][$name]['UserValue'])) {
						$oldImageUrl = $allSettings['Android']['Image'][$name]['UserValue'];
						$oldImageId = $this->get_attachment_id_from_src($oldImageUrl);

						//TODO: figure out why the old&new id are the same, sould never be that
						if(is_numeric($oldImageId) && $oldImageId != $newImageMediaId){
							wp_delete_attachment($oldImageId, true);
						}
					}
						
					$newSettings['Android']['Image'][$name]['UserValue'] = $imgUrl;
				}
			}
		}

		update_option('feed_settings', $newSettings);
	}



	/**
	 * Removes all old iOS settings and fill it with everything
	 * new from the form except General
	 *
	 * @since 1.1
	 ************************************************************/
	private function save_ios_gui() {
		$allSettings = $this->get_feed_settings();
		$newSettings = get_option('feed_settings', array());

		// Remove all old settings
		unset($newSettings['ios']['Language']);

		// Put all new Language settings to $newSettings unless the value is not the standard value or empty
		foreach ($allSettings['ios']['Language'] as $key => $value) {
			if( isset($_POST[$key]) && $_POST[$key] != '' && $_POST[$key] != $value['Standard'] ) {
				$newSettings['ios']['Language'][$key]['UserValue'] = $_POST[$key];
			}
		}

		// Put all new Image settings to $newSettings unless the value is not the standard value or empty
		foreach ($allSettings['ios']['Image'] as $name => $value) {
			if( isset($_FILES[$name]) && $_FILES[$name]['size'] > 0 && $_FILES[$name]['type'] == "image/png") {

				$file = wp_handle_upload($_FILES[$name], array('test_form' => false));

				$wp_filetype = wp_check_filetype(basename($file['file']), null );

				$aFile["name"] = $name.'.png';
				$aFile["type"] = $wp_filetype;
				$aFile["tmp_name"] = $file['file'];

				$newImageMediaId = media_handle_sideload( $aFile, null, $name );
				if(!is_wp_error( $newImageMediaId ) && $newImageMediaId > 0) {

					// Save new media url
					//wp_create_thumbnail($newImageMediaId,150);
					$imgUrl = wp_get_attachment_url($newImageMediaId);
					$newSettings['Android']['Image'][$name]['UserValue'] = $imgUrl;

					// Delete old image
					if(isset($allSettings['Android']['Image'][$name]['UserValue'])) {
						$oldImageUrl = $allSettings['Android']['Image'][$name]['UserValue'];
						$oldImageId = $this->get_attachment_id_from_src($oldImageUrl);
						wp_delete_attachment($oldImageId, true);
					}

				}
			}
		}

		update_option('feed_settings', $newSettings);
	}



	/**
	 * Handles the apk request
	 *
	 * @since 1.1
	 ************************************************************/
	private function handle_apk() {

		if(isset($_REQUEST['publish_apk'])) {
			$this->publish_apk();
		}

		if(isset($_REQUEST['generate_apk'])){
			$this->generate_apk();
		}

		if(isset($_REQUEST['cansel_apk'])) {
			$this->delete_pending_build('Android');
		}


	}



	/**
	 * Handles the ios request
	 *
	 * @since 1.1
	 ************************************************************/
	private function handle_ios() {

		if(isset($_REQUEST['publish_ios'])){
		}

		if(isset($_REQUEST['generate_ios'])){
		}

		if(isset($_REQUEST['cancel_ios'])){
		}

	}



	/**
	 * Gets the next apk version name
	 *
	 * @since 1.1.17
	 * 
	 * @return string
	 ************************************************************/
	private function get_next_apk_version() {
		$mySettings = get_option('feed_settings', array());

		if( isset( $mySettings['A_next_version_name'] ) ){
			return $mySettings['A_next_version_name'];
		}
			
		if( isset( $mySettings['Android']['CurrentVersion'] ) ) {
			$nextVersion = explode('.', $mySettings['Android']['CurrentVersion']);
			$nextVersion[2]++;
			$nextVersion = implode('.', $nextVersion);
			return $nextVersion;
		}
		return '1.0.0';
	}



	/**
	 * Gets the next apk version code
	 *
	 * @since 1.1.17
	 *
	 * @return string interger > 0
	 ************************************************************/
	private function get_next_apk_version_code() {
		$mySettings = get_option('feed_settings', array());
		if( isset( $mySettings['A_next_version_code'] ) ){
			return $mySettings['A_next_version_code'];
		}
		return isset($mySettings['Android']['CurrentVersionCode']) ? $mySettings['Android']['CurrentVersionCode']+1 : '1';
	}



	/**
	 * should only be pressed once or else you may skip a version name & code
	 * but not the end of the world if you did
	 *
	 * @since 1.1.17
	 ************************************************************/
	private function publish_apk() {
		// fetch the beta versions name & code
		$curentVersionName = $this->get_next_apk_version();
		$curentVersionCode = $this->get_next_apk_version_code();

		// Update the versions name & code
		$allSettings = get_option('feed_settings');
		$allSettings['Android']['CurrentVersionCode'] = $curentVersionCode;
		$allSettings['Android']['CurrentVersion'] = $curentVersionName;

		unset($allSettings['A_next_version_code']);
		unset($allSettings['A_next_version_name']);

		update_option('feed_settings', $allSettings);

		// Get all apk
		$mediaapk =& get_children( 'post_type=attachment&post_mime_type=application/vnd.android.package-archive' );

		foreach ( $mediaapk as $attachid => $attach ) {
			// If we found a beta among them
			if ( $attach->post_excerpt == 'beta' ) {
				// Then we update it to a live version
				$my_post = array();
				$my_post['ID'] = $attachid;
				$my_post['post_excerpt'] = 'live';
				wp_update_post( $my_post );
			}
		}
	}


	/**
	 * Tells feed.nu to generate a apk file
	 *
	 * @since 1.1.15
	 ************************************************************/
	private function generate_apk() {
		global $blog_id;

		$allSettings = $this->get_feed_settings();
		$domain = feed_get_blog_domain();
		$time = time();
		
		
		// Adds http://domain.com to all standard image
		foreach ($allSettings['Android']['Image'] as $key => $v) {
			$allSettings['Android']['Image'][$key]['Standard'] = 'http://'. $domain . $allSettings['Android']['Image'][$key]['Standard'];
		}

		// Fetch all UserValue
		$myApkImage = $this->get_user_value($allSettings['Android']['Image']);
		
		// Validate image
		$errors = $this->is_valid_images($myApkImage);
		
		
		$blog_name = apply_filters('feed_package_name', $domain);
		$versionCode = $this->get_next_apk_version_code();
		$versionName = $this->get_next_apk_version();
		$packagename = apply_filters('feed_package_name', $domain);
		
		// Validate packagename
		if(preg_match("/^[A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/", $packagename) === 0){
			array_push($errors, "Packagename can only contain the characters a-z A-Z 0-9 and underscore\n\n");
		};
		
		// Validate versionCode if string is int and > 0
		if (! preg_match('/^\d+$/', $versionCode) && $versionCode > 0){
			array_push($errors, "Versioncode can only contain the characters 0-9 and must be grater then 0\n\n");
		}
		
		if(count($errors) > 0){
			echo json_encode(array('errors' => $errors));
			die();
		}
		 
		$mySettings = array(
			'NEWNAME'		=> $packagename,
			'STARTHEADER'	=> $myApkImage['top_image_start']['UserValue'],
			'LISTHEADER'	=> $myApkImage['top_image_listview']['UserValue'],
			'ARTICLEHEADER'	=> $myApkImage['top_image_description']['UserValue'],
			'APPICON'		=> $myApkImage['icon']['UserValue'],
			'ITEMICONNORMAL'=> $myApkImage['item_icon']['UserValue'],
			'ITEMICONREAD'	=> $myApkImage['is_read_icon']['UserValue'],
			'BTNNORMAL'		=> $myApkImage['mybtn_normal']['UserValue'],
			'BTNFOCUS'		=> $myApkImage['mybtn_focus']['UserValue'],
			'BTNPRESSED'	=> $myApkImage['mybtn_pressed']['UserValue'],
			'STRINGS'		=> 'http://' . $domain . '/?action=feed_cmd&feed_cmd=get_android_string_xml',
			'COLORS'		=> 'http://' . $domain . '/?action=feed_cmd&feed_cmd=get_android_color_xml',
			'ARRAYS'		=> 'http://' . $domain . '/?action=feed_cmd&feed_cmd=get_android_arrays_xml',
			'RETURNURL'		=> 'http://' . $domain . '/?action=feed_cmd&feed_cmd=save_android_apk&timestamp=' . $time,
			'PLUGINVERSION' => $this->version,
			'VERSIONCODE'	=> $versionCode,
			'VERSIONNAME'	=> $versionName,
		);

		// Pro settings
		$mySettings = apply_filters('feed_pro_generation', $mySettings);
		
		
		// Save the time we did generate it
		$this->set_pending_build('Android', $time);

		$url = 'http://feed.nu/?buildApp=android';

		$request = new WP_Http;
		$result = $request->post($url, array( 'body' => $mySettings ) );
		echo $result['body'];
		echo print_r($mySettings);
	}



	public function is_valid_images($images){
		$errors = array();
		
		if( function_exists('imagecreatefromstring') &&
			function_exists('imagecolorat') &&
			function_exists('wp_remote_get')) {
			
			$patchImages = array('mybtn_normal', 'mybtn_focus', 'mybtn_pressed');
			
			
			foreach ($images as $key => $oneimage) {
				
				$imageUrl = $oneimage['UserValue'];
				$image = wp_remote_get($imageUrl);
				
				if( is_wp_error( $image ) ) {
					$errors[] = "$key image dose not exist:\n$imageUrl\n\n";
				} else {
					if($image['headers']['content-type'] != "image/png"){
						$errors[] = "$key is not a png image:\n$imageUrl\n\n";
					}
					else if(in_array($key, $patchImages) && $image['headers']['content-type'] == "image/png"){
						if( ! $this->is_valid_9_patch_image($image['body'] ) ){
							$errors[] = "$key is not a valid patch image:\n$imageUrl\n\n";
						}
					}
				}
			}
			
		} else {
			// Assume its a valid image
		}

		return $errors;
	}
	
	/**
	 * One of the callback from feed, asking for an string xml
	 *
	 * @since 1.1
	 ************************************************************/
	private function get_android_string_xml() {

		global $blog_id;
		$domain = feed_get_blog_domain();

		// Get all the settings
		$allSettings = $this->get_feed_settings();

		// Modifie some standards
		$allSettings['Android']['General']['ArticleCSS']['Standard'] = '';

		// Get all the users value (The standard or customized value)
		$myLangSettings = $this->get_user_value($allSettings['Android']['Language'], true);
		$General = $this->get_user_value($allSettings['Android']['General'], true);
		$global = $this->get_user_value($allSettings['Global'], true);

		// Make one huge array of it
		$mergedSettings = array_merge_recursive($myLangSettings, $global);
		$mergedSettings = array_merge_recursive($mergedSettings ,$General);

		// Filter out what we realy need
		foreach ($mergedSettings as $key => $value) {
			$strings[$key] = $value['UserValue'];
		}

		// Fix the dynamic variabels into something the app wants
		$strings['new_items'] = str_replace('NUMBERS_OF', '%1$s', $strings['new_items']);
		$strings['share_title'] = str_replace('POST_TITLE', '%1$s', $strings['share_title']);
		$strings['updatedialog'] = str_replace('THIS_VERSION', '%1$s', $strings['updatedialog']);
		$strings['updatedialog'] = str_replace('NEXT_VERSION', '%2$s', $strings['updatedialog']);
		$strings['share_message'] = str_replace('LINK_URL', '%1$s', $strings['share_message']);
		$strings['version_number'] = str_replace('VERSION_NUMBER', '%1$s', $strings['version_number']);
		$strings['about_app_title'] = str_replace('APPLICATION_TITLE', '%1$s', $strings['about_app_title']);
		$strings['app_date_format'] = str_replace('custum', $strings['custom_date'], $strings['app_date_format']);
		unset($strings['custom_date']);
		unset($strings['feedhost']);


		// This is the adress to check for new updates if the app isn't on the market
		// 		$strings['updateappurl'] = $domain . '/?update=now';
		$strings['admin_email'] = get_option('admin_email');

		// Build the xml
		$xml = '<?xml version="1.0" encoding="utf-8"?'.'>'."\n";
		$xml .= '<resources>'."\n";
		foreach($strings as $id=>$val) {
			$val = str_replace("\n", '\n', $val);
			$xml .= "\t".'<string name="'.$id.'">'.htmlspecialchars($val).'</string>'."\n";
		}
		$xml .= '</resources>';

		// Print it
		header('content-type: text/xml');
		die($xml);
	}



	/**
	 * One of the callback from feed, asking for an color xml
	 *
	 * @since 1.1
	 ************************************************************/
	private function get_android_color_xml() {

		$allSettings = $this->get_feed_settings();
		$myColorSettings = $this->get_user_value($allSettings['Android']['Color'], true);

		foreach ($myColorSettings as $key => $value) {
			//add alpha chanel to the color xml
			$strings[$key] = 'FF'.$value['UserValue'];
		}

		$xml = '<?xml version="1.0" encoding="utf-8"?'.'>'."\n";
		$xml .= '<resources>'."\n";
		foreach($strings as $id=>$val) {
			$xml .= "\t".'<color name="'.$id.'">#'.$val.'</color>'."\n";
		}
		$xml .= '</resources>';

		header('content-type: text/xml');
		die($xml);
	}



	/**
	 * One of the callback from feed, asking for an arrays xml
	 *
	 * @since 1.1
	 ************************************************************/
	private function get_android_arrays_xml() {

		global $blog_id;
		$domain = feed_get_blog_domain();

		$allSettings = $this->get_feed_settings();

		$allSettings['Global']['feedhost']['Standard'] = get_bloginfo('rss2_url');

		$myGlobalSettings = $this->get_user_value($allSettings['Global'], true);

		$rss = explode("\n", $myGlobalSettings['feedhost']['UserValue']);

		$xml = '<?xml version="1.0" encoding="utf-8"?'.'>'."\n";
		$xml .= '<resources>'."\n";
		$xml .= '<string-array name="rssfeeds">'."\n";
		foreach($rss as $val) {
			$xml .= "\t".'<item>'.htmlspecialchars($val).'</item>'."\n";
		}
		$xml .= '</string-array>';
		$xml .= '</resources>';

		header('content-type: text/xml');
		die($xml);
	}



	/**
	 * One of the callback from feed.nu telling you to save the
	 * Android apk file from $_FILES['file']
	 *
	 * @since 1.1
	 ************************************************************/
	private function save_android_apk() {

		// Get the time we starded generate
		$timestamp = $this->get_pending_build('Android');
		$this->delete_pending_build('Android');

		$apk = $_FILES['file'];

		// If the time was not the same we don't want it
		if( ! isset($_GET['timestamp'], $apk) )
		die('not valid param');

		if($timestamp != $_GET['timestamp'])
		die('timestamp is not the same');

		// Create the file
		$file = wp_handle_upload($apk, array('test_form' => false));

		$wp_filetype = wp_check_filetype(basename($file['file']) );

		$save_as = $this->get_next_apk_version();

		$aFile["name"] = $save_as . ".apk";
		$aFile["type"] = $wp_filetype;
		$aFile["tmp_name"] = $file['file'];

		// Add post excerpt telling it to be beta version
		$post_data = array('post_excerpt'=>'beta');

		// Delete previus beta apk
		$mediaapk =& get_children( 'post_type=attachment&post_mime_type=application/vnd.android.package-archive' );
		foreach ( $mediaapk as $attachid => $attach ) {
			if($attach->post_excerpt == 'beta'){
				wp_delete_attachment($attachid, true);
			}
		}

		// This handles a sideloaded file in the same way as an uploaded file is handled by media_handle_upload()
		$newApkMediaId = media_handle_sideload( $aFile, null, $save_as, $post_data );
		if(!is_wp_error( $newApkMediaId ) && $newApkMediaId > 0) {
			//TODO: Handle upload errors
		}
		die();
	}



	/**
	 * Gets the time when you started a build
	 *
	 * @since 1.1
	 * @param string $os possible value "Android, iOS"
	 * @return timestamp from when you starde the generation
	 ************************************************************/
	private function get_pending_build($os){
		$mySettings = get_option('feed_settings');
		return isset($mySettings[$os]['HasPendingBuild']) ? $mySettings[$os]['HasPendingBuild'] : false;
	}



	/**
	 * Set the time when you starded generating your app
	 *
	 * @since 1.1
	 *
	 * @param string $os possible value "Android, iOS"
	 * @param string $time timestamp
	 ************************************************************/
	private function set_pending_build($os, $time) {
		$mySettings = get_option('feed_settings');
		$mySettings[$os]['HasPendingBuild'] = $time;
		update_option('feed_settings', $mySettings);
	}


	/**
	 * Unsets the pending build, when called you can not
	 * accept any incoming apps
	 *
	 * @since 1.1
	 *
	 * @param string $os possible value "Android, iOS"
	 ************************************************************/
	private function delete_pending_build($os) {
		$mySettings = get_option('feed_settings');
		unset($mySettings[$os]['HasPendingBuild']);
		update_option('feed_settings', $mySettings);
	}



	/*
	 private function publish_ios() {
	;
	}

	private function generate_ios() {
	;
	}

	private function cancel_ios() {
	;
	}
	*/


	/**
	 * Get all settings from feed.xml and the custom settings
	 * done by the user
	 *
	 * @since 1.1
	 *
	 * @return array One pick fat such
	 ************************************************************/
	private function get_feed_settings() {
		$path = dirname(__FILE__).'/feed.xml';
		$xmlStr = file_get_contents($path);
		$xmlObj = simplexml_load_string($xmlStr);
		$standardFeedSettings = $this->object2array($xmlObj);

		$mySettings = get_option('feed_settings', array());

		$allSettings = array_merge_recursive($mySettings, $standardFeedSettings);
		return $allSettings;
	}


	/**
	 * xxx
	 *
	 * @since 1.1
	 *
	 * @return array
	 ************************************************************/
	private function get_live_settings($password = false){
		global $blog_id;
		$client = New FeedNuClient();

		$domain = feed_get_blog_domain();
		$packetname = apply_filters('feed_full_package_name', $domain);
		$password = ($password) ? $password : $_POST['appowner_password'];
		
		$appSettings = $client->feed_get_app_settings(get_option('admin_email'), $password, $packetname);
		
		if( isset($appSettings['credsRatio']) ){
			$mySettings = get_option('feed_settings');
			$mySettings['live_password'] = $password;
			update_option('feed_settings', $mySettings);
		}
		
		if(isset($_POST['appowner_password']) && empty($_POST['appowner_password']) === false){
			echo json_encode($appSettings);
		} else {
			return $appSettings;
		}
	}


	/**
	 * xxx
	 *
	 * @since 1.1
	 *
	 * @return array
	 ************************************************************/
	private function save_live_settings(){

		global $blog_id;
		$domain = feed_get_blog_domain();

		$allSettings = $this->get_feed_settings();
		$globalSettings = $allSettings['Global'];
		$globalSettings['feedhost']['Standard'] = $domain . $globalSettings['feedhost']['Standard'];
		$globalSettings = $this->get_user_value($globalSettings);

		$allSettings = get_option('feed_settings');
		$client = New FeedNuClient();

		$domain = feed_get_blog_domain();
		
		if(isset($allSettings['live_password'])){
			$password = $allSettings['live_password']; 
		} else {
			$password = $_POST['appowner_password'];
		}

		$adminemail = get_option('admin_email');
		$packagename = apply_filters('feed_full_package_name', $domain);
		$clientAdmobCode = $_POST['clientAdmobCode'];
		$updateAppURL = "http://".$domain."/?fau";
		$availibleVersionName = $allSettings['Android']['CurrentVersion'];
		$clientAnalytics = $_POST['clientAnalytics'];
		$credsRatio = $_POST['credsRatio'];
		$availibleVersionCode = $allSettings['Android']['CurrentVersionCode'];
		$disableAds = $_POST['disableAds'];
		$feedUrls = explode("\n", $globalSettings['feedhost']['UserValue']);

		print $client->feed_set_app_settings($adminemail, $password, $packagename, $clientAdmobCode, $updateAppURL, $availibleVersionName, $clientAnalytics, $credsRatio, $availibleVersionCode, $disableAds, $feedUrls);
	}



	/**
	 * Convert object to array
	 *
	 * @since 1.1
	 *
	 * @param object $arrObjData
	 * @return array
	 ************************************************************/
	private function object2array($arrObjData, $arrSkipIndices = array()) {
		$arrData = array();

		// if input is object, convert into array
		if (is_object($arrObjData)) {
			$arrObjData = get_object_vars($arrObjData);
		}

		if (is_array($arrObjData)) {
			foreach ($arrObjData as $index => $value) {
				if (is_object($value) || is_array($value)) {
					$value = $this->object2array($value, $arrSkipIndices); // Recursive call
					$value = (count($value) > 0) ? $value : '';
				}
				if (in_array($index, $arrSkipIndices)) {
					continue;
				}
				//if( count($value) > 0 )
				$arrData[$index] = $value;
			}
		}
		return $arrData;
	}



	/**
	 * Gets all UserValue in a array.
	 * All UserValue may not be in that array so the
	 * UserValue becomes the standard if empty
	 *
	 * @since 1.1.9
	 *
	 * @param array with all UserValue
	 */
	private function get_user_value($array, $stripquotes = false) {
		foreach ($array as $key => $value) {
			$val = isset($value['UserValue']) ? $value['UserValue'] : $value['Standard'];
			$array[$key]['UserValue'] = stripslashes($val);
			if($stripquotes) {
				$array[$key]['UserValue'] = str_replace("'", "\\'", $array[$key]['UserValue']);
			}
		}
		return $array;
	}
	
	
	

	/**
	 * Determine if a image is a valid 9 patch png image
	 * 
	 * @param string $pngRel full Filepath
	 * @return true if a image has only solid black lines on top & left edge
	 * @since 1.1.18
	 */
	function is_valid_9_patch_image($pngRel) {
		$im = imagecreatefromstring($pngRel);
		$height = imagesy($im);
		$width = imagesx($im);

		//Not a valid png image?
		if($height <= 0 || $width <= 0) {
			return false;
		}
		
		$haveAnyBlack = false;
		
		for($x = 0; $x < $width; $x++){
			$y = 0;
			$rgba = imagecolorat($im,$x,$y);
			$alpha = ($rgba & 0x7F000000) >> 24;
			$red = ($rgba & 0xFF0000) >> 16;
			$green = ($rgba & 0x00FF00) >> 8;
			$blue = ($rgba & 0x0000FF);

			$isAlpha = false;
			$isBlack = false;
			if($alpha == 0 && $red == 0 && $green == 0 && $blue == 0) {
				$isBlack = true;
				$haveAnyBlack = true;
			}
			else if($alpha == 127) {
				$isAlpha = true;
			}

			//If the top left pixel is not transparent then false
			if($x == 0 && $isAlpha == false) {
				//die('If the top left pixel is not transparent then false. x:' . $x . ' y:' . $y);
				return false;
			}
				
			//If the top right pixel is not transparent then false
			if($x == $width-1 && $isAlpha == false) {
				//die('If the top right pixel is not transparent then false. x:' . $x . ' y:' . $y);
				return false;
			}

			//If it is not completly transparent or black, then false
			if(!$isAlpha && !$isBlack) {
				//die('If it is not completly transparent or black, then false. x:' . $x . ' y:' . $y);
				return false;
			}
		}
		
		if(!$haveAnyBlack){
			//die('Top edge dose not contain any black lines');
			return false;
		}
		
		$haveAnyBlack = false;
		
		for($y = 0; $y < $height; $y++){
			$x = 0;
			$rgba = imagecolorat($im,$x,$y);
			$alpha = ($rgba & 0x7F000000) >> 24;
			$red = ($rgba & 0xFF0000) >> 16;
			$green = ($rgba & 0x00FF00) >> 8;
			$blue = ($rgba & 0x0000FF);

			$isAlpha = false;
			$isBlack = false;
			if($alpha == 0 && $red == 0 && $green == 0 && $blue == 0) {
				$isBlack = true;
				$haveAnyBlack = true;
			}
			else if($alpha == 127) {
				$isAlpha = true;
			}

			//If the top left pixel is not transparent then false
			if($y == 0 && $isAlpha == false) {
				//die('If the top left pixel is not transparent then false. x:' . $x . ' y:' . $y);
				return false;
			}
				
			//If the top right pixel is not transparent then false
			if($y == $width-1 && $isAlpha == false) {
				//die('If the top right pixel is not transparent then false. x:' . $x . ' y:' . $y);
				return false;
			}

			//If it is not completly transparent or black, then false
			if(!$isAlpha && !$isBlack) {
				//die('If it is not completly transparent or black, then false. x:' . $x . ' y:' . $y);
				return false;
			}
		}
			
		if(!$haveAnyBlack){
			//die('Left edge dose not contain any black lines');
			return false;
		}
		
		return true;

	}
	
	/**
	* @since 1.1.21
	*/
	function have_app( $atts, $content = null ) {
		$apk =& get_children( 'post_type=attachment&post_mime_type=application/vnd.android.package-archive' );
	
		foreach ( $apk as $attachid => $attach ) {
			if($attach->post_excerpt != 'beta'){
				$apk_link = wp_get_attachment_link( $attachid );
				return do_shortcode($content);
			}
		}
	
		return '';
	}
	
	/**
	* @since 1.1.21
	*/
	function no_app( $atts, $content = null ) {
		$apk =& get_children( 'post_type=attachment&post_mime_type=application/vnd.android.package-archive' );
	
		foreach ( $apk as $attachid => $attach ) {
			if($attach->post_excerpt != 'beta'){
				$apk_link = wp_get_attachment_link( $attachid );
				break;
			}
		}
	
		if( !isset($apk_link ) )
			return $content;
		return '';
	}
	
	/**
	* @since 1.1.21
	*/
	function app_qr( $atts, $content = null ) {
		$apk =& get_children( 'post_type=attachment&post_mime_type=application/vnd.android.package-archive' );
	
		foreach ( $apk as $attachid => $attach ) {
			if($attach->post_excerpt != 'beta'){
				$applink = get_option('siteurl').'?fau';
				return "<a href='$applink'>".$this->getQRwithGoogle($applink).'</a>';
			}
		}
	
		return '';
	}
	
	/**
	 * @since 1.1.21
	 */
	function getQRwithGoogle($chl, $widhtHeight='150',$EC_level='L', $margin='0') {
		return '<img src="http://chart.apis.google.com/chart?chs='.$widhtHeight.'x'.$widhtHeight.'&cht=qr&chld='.$EC_level.'|'.$margin.'&chl='.urlencode($chl).'" alt="QR code" widhtHeight="'.$widhtHeight.'" widhtHeight="'.$widhtHeight.'"/>';
	}
}

new Feed_app_plugin();

function feed_get_blog_domain($dummy = null){
	$domain = get_option('siteurl');
	$domain = str_replace('http://', '', $domain);
	$domain = str_replace('https://', '', $domain);
	while(substr($domain, -1, 1) == '/') {
		$domain = substr($domain, 0, strlen($domain)-1);
	}
	return $domain;
}

require_once( 'widgets/qr.php' );
require_once 'custom-feed.php';
require_once 'feed-api.php';
$feedAPI = new Feed_API();


