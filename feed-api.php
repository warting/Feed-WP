<?php

/* AVOID DIRECT CALLS
 *
* avoid direct calls to this file where
* wp core files not present
************************************************************/
if(!defined('ABSPATH'))
die('Please do not load this file directly.');


require_once( ABSPATH . WPINC . '/class-IXR.php' );
require_once( ABSPATH . "wp-admin/includes/file.php" );
require_once( ABSPATH . "wp-admin/includes/media.php" );
require_once( ABSPATH . "wp-admin/includes/image.php" );

class Feed_API {

	var $client;
	var $Api_key;

	function __construct() {
		add_action('wp_loaded', array($this,'init'));
	}

	public function init() {

		$this->Api_key = get_option('feed_api_key');
		delete_option('feed_api_key');
		
		if($this->Api_key) {
			$this->client = new FeedNuClient();
			$this->OnePointZeroToOnePointOne();
		}

	}

	public function OnePointZeroToOnePointOne() {

		$status = $this->client->get_connection_status($this->Api_key);

		if(is_array($status) && !isset($status['error'])){
			$this->download_all_my_apk();
				
			$alloldconvertedsetting = $this->client->get_all_settings($this->Api_key);
			update_option('feed_settings', $alloldconvertedsetting);
				
			$this->client->feed_delete_android_apps($this->Api_key);
		}

	}

	function download_all_my_apk() {

		$apps = $this->client->get_android_apps($this->Api_key);

		foreach ($apps['released'] as $app){
			$this->save_file($app['link'],$app['version'],'live',$app['created']);
		}

		foreach ($apps['beta'] as $app){
			$this->save_file($app['link'],$app['version'],'beta',$app['created']);
		}

	}


	private function save_file($url,$version,$status,$created) {
			
		$tmp = download_url( $url );

		$file_array['name'] = basename($url);
		$file_array['tmp_name'] = $tmp;
		$file_array['size'] = filesize($tmp);

		// If error storing temporarily, unlink
		if ( is_wp_error( $tmp ) ) {
				
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
				
		} else {
				
			$newImageMediaId = media_handle_sideload( $file_array, null, $file_array['name'] );

			if(!is_wp_error( $newImageMediaId ) && $newImageMediaId > 0) {
				$my_post = array();
				$my_post['ID'] = $newImageMediaId;
				$my_post['post_excerpt'] = $status;
				$my_post['post_date'] = $my_post['post_date_gmt'] = $my_post['post_modified'] = $my_post['post_modified_gmt'] = $created;
				$my_post['post_title'] = $version;
				wp_update_post( $my_post );
			}
				
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
				
		}

	}

}
new Feed_API();






class FeedNuClient {

	protected $url = 'feed.nu';
	protected $client;

	public function FeedNuClient() {
		$this->client = new IXR_Client('http://'.$this->url.'/xmlrpc.php');
	}

	public function custom_query($method, $data='') {
		if (!$this->client->query($method,$data)) {
			return array('error' => 'An error occurred - '.$this->client->getErrorCode().": ".$this->client->getErrorMessage());
		}
		return $this->client->getResponse();
	}
	public function get_connection_status($apikey) {
		return $this->custom_query('feed.getconnectionstatus',$apikey);
	}
	public function get_all_settings($apikey) {
		return $this->custom_query('feed.TryFetchAllSettings', $apikey);
	}
	public function get_android_apps($apikey) {
		return $this->custom_query('feed.getandroidapps', $apikey);
	}
	public function feed_delete_android_apps($apikey) {
		return $this->custom_query('feed.deleteandroidapps', $apikey);
	}
	public function feed_get_app_settings($adminemail, $password, $packagename) {
		return $this->custom_query('feed.getappsettings', array('adminemail'=>$adminemail,'password'=>$password,'packagename'=>$packagename));
	}
	public function request_new_live_password($adminemail, $packagename) {
		return $this->custom_query('feed.requestnewlivepassword', array('adminemail'=>$adminemail,'packagename'=>$packagename));
	}
	public function feed_set_app_settings(
		$adminemail, 
		$password, 
		$packagename, 
		$clientAdmobCode, 
		$updateAppURL, 
		$availibleVersionName, 
		$clientAnalytics, 
		$credsRatio, 
		$availibleVersionCode, 
		$disableAds, 
		$feedUrls
	) {
		
		$request = array(
			'adminemail'=>$adminemail,
			'password'=>$password,
			'packagename'=>$packagename,
			'clientAdmobCode'=>$clientAdmobCode,
			'updateAppURL'=>$updateAppURL,
			'availibleVersionName'=>$availibleVersionName,
			'clientAnalytics'=>$clientAnalytics,
			'credsRatio'=>$credsRatio,
			'availibleVersionCode'=>$availibleVersionCode,
			'disableAds'=>$disableAds,
			'feedUrls'=>$feedUrls
		);

		return $this->custom_query('feed.setappsettings', $request);
	}

}

