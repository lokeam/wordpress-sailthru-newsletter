<?php
/**
 * Sailthru Site Admin
 */

class Sailthru_Admin_Interface{

	const SETTINGS_MENU_SLUG = 'options-general.php';
	const SETTINGS_SUBMENU_LABEL = 'Sailthru Settings';
	const IS_ACTIVE_OPTION_NAME = 'sailthru_widget_active';
	const IS_ACTIVE_PROMOTE_VIP = 'sailthru_vip_active';
	const ADMIN_CAPABILITIES = 'manage_options';
	const SUBMENU_SLUG = 'sailthru';

	const FTP_SERVER = "ftp.sailthru.com";
	const FTP_USER_NAME= "tsquare";
	const FTP_USER_PASS = "v9f0vus";
	const FTP_ROOT_FOLDER = "/town";

	const SAILTHRU_LIST_TYPE= 'sailthru_list_type';
	const SAILTHRU_LIST_CACHE= 'sailthru_list_cache';
	const SAILTHRU_MARKET= 'sailthru_market';
	const SAILTHRU_API_KEY= 'sailthru_api_key';
	const SAILTHRU_SECRET_KEY= 'sailthru_secret_key';
	const SAILTHRU_LIST_DEFAULT= 'sailthru_list_default';
	const SAILTHRU_WELCOME_TEMPLATE='sailthru_welcome_template';
	const SAILTHRU_MESSAGE_DEFAULT= 'sailthru_message_default';
	const SAILTHRU_DESCRIPTION_DEFAULT= 'sailthru_description_default';

	private static $dirs = array();

	protected function __construct() {
		$this->add_hooks();
	}

	protected function add_hooks() {
		add_action('admin_menu', array($this, 'register_site_admin_page') );
		add_action('admin_menu', array($this, 'register_site_stats_page') );
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
		add_action('wp_ajax_sailthru_get_list', array($this, 'get_sailthru_lists'));
		add_action('wp_ajax_sailthru_get_keys', array($this, 'get_sailthru_keys'));
	}

	public function enqueue_scripts(){
		global $pagenow;
		if($pagenow == 'options-general.php' && $_GET['page'] == 'sailthru') {
			wp_enqueue_style('sailthru', plugins_url('admin/css/style.css', __FILE__), array(), version_cache(), 'all');
			wp_enqueue_style('sailthru-uploader', plugins_url('admin/css/fileuploader.css', __FILE__), array(), version_cache(), 'all');

			wp_enqueue_script('jquery-tabify', plugins_url('admin/js/jquery.tabify.js', __FILE__), array('jquery'), version_cache(), false);
			wp_enqueue_script('jquery-timer', plugins_url('admin/js/jquery.timer.js', __FILE__), array('jquery'), version_cache(), false);
			wp_enqueue_script('sailthru-uploader', plugins_url('admin/js/fileuploader.js', __FILE__), array('jquery'), version_cache(), false);
			wp_enqueue_script('sailthru-script', plugins_url('admin/js/script.js', __FILE__), array('jquery'), version_cache(), false);
		}

		if ($pagenow == 'admin.php' && $_GET['page'] == 'sailthru-stats'){
			wp_enqueue_style('sailthru', plugins_url('admin/css/style.css', __FILE__), array(), version_cache(), 'all');
			wp_enqueue_style('sailthru-uploader', plugins_url('admin/css/fileuploader.css', __FILE__), array(), version_cache(), 'all');
			wp_enqueue_style('sailthru-datetimepicker', plugins_url('admin/css/jquery.datetimepicker.css', __FILE__), array(), version_cache(), 'all');
			wp_enqueue_script('sailthru-script', plugins_url('admin/js/stats-script.js', __FILE__), array('jquery'), version_cache(), false);
			wp_enqueue_script('sailthru-datetimepicker', plugins_url('admin/js/jquery.datetimepicker.full.js', __FILE__), array('jquery'), version_cache(), false);
		}
	}

	public function register_site_admin_page(){
		add_submenu_page(
			self::SETTINGS_MENU_SLUG,
			self::SETTINGS_SUBMENU_LABEL,
			self::SETTINGS_SUBMENU_LABEL,
			self::ADMIN_CAPABILITIES,
			self::SUBMENU_SLUG,
			array($this, 'display_settings_page')
		);
	}

	public function register_site_stats_page(){
		add_menu_page(
			'Sailthru Statistics',
			'Sailthru Statistics',
			self::ADMIN_CAPABILITIES,
			'sailthru-stats',
			array($this, 'display_stats_page'),
			'',
			86
		);
	}

	public static function ftpRecursivePrintContent($ftpConnection, $path) {
		static $allFiles = array();
		$contents = ftp_nlist($ftpConnection, $path);
		foreach($contents as $currentFile) {
			// assuming its a folder if there's no dot in the name
			if (strpos($currentFile, '.') === false) {

				$pos = strrpos($currentFile, "/");
				if ($pos>0) { // note: three equal signs

					$folder = substr($currentFile,$pos+1,strlen($currentFile));
					array_push(self::$dirs, $folder);

					$filesList = ftp_nlist($ftpConnection, $currentFile);
					//echo "[".$folder."]<br/>";
					$filesArray = array();
					foreach($filesList as $file) {
						$pos = strrpos($file, "/");

						if ($pos>0) { // note: three equal signs
							$fileTrimmed = substr($file,$pos+1,strlen($file));
							array_push($filesArray,$fileTrimmed);
						}

					}

					$allFiles[str_replace(" ","_",strtolower($folder))] = $filesArray;

				}
			}

		}
		return $allFiles;
	}


	public static function get_markets(){
		global $wpdb;
		return $wpdb->get_results("SELECT DISTINCT(market) FROM {$wpdb->base_prefix}site_newsletter");
	}

	public static function handleCreateMarketTable(){
		global $wpdb;
		$query = "SELECT blog_id, domain FROM {$wpdb->base_prefix}blogs";
		$result = $wpdb->get_results($query);
		$num=count($result);
		$i=0;
		$market_array = array();

		while ($i < $num) {
			$var_blogid=$result[$i]->blog_id;
			$var_domain = $result[$i]->domain;
			$tsm_info=$wpdb->get_results("SELECT option_value FROM {$wpdb->base_prefix}{$var_blogid}_options WHERE option_name = '_station_info'");
			if(count($tsm_info)>0){
				$option_value_str = $tsm_info[0]->option_value;
				$option_value_obj = unserialize($option_value_str);
				$var_market = $option_value_obj["market"];
				if(trim($var_market)!=""){
					array_push($market_array,array("market"=>$var_market,"market_url"=>$var_domain));
				}
			}
			$i++;
		}
		$row = 1;
		if (($handle = fopen($_FILES["file"]["tmp_name"], "r")) !== FALSE) {
			while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
				$row++;
				$market_title = $data[0];
				$sailthru_api = $data[1];
				$sailthru_secret = $data[2];
				for ($k=0; $k < count($market_array); $k++) {
					if(strtolower(trim($market_array[$k]["market"])) == strtolower(trim($market_title))){
						$production_site = $market_array[$k]["market_url"];
						$blogId = $wpdb->get_row("SELECT blog_id FROM {}$wpdb->base_prefix}blogs WHERE domain = '$production_site'");
						$check_blogid_exist = $wpdb->get_row("SELECT blog_id FROM {$wpdb->base_prefix}site_newsletter WHERE blog_id = $blogId->blog_id");
						if(count($check_blogid_exist)>0)
							$wpdb->query("UPDATE {$wpdb->base_prefix}site_newsletter SET blog_id = '$blogId->blog_id', domain = '$production_site', sailthru_api_key = '$sailthru_api', sailthru_secret_key = '$sailthru_secret', market = '$market_title' WHERE blog_id = $blogId->blog_id");
						else
							$wpdb->query("INSERT INTO {$wpdb->base_prefix}site_newsletter VALUES('$blogId->blog_id','$production_site','$sailthru_api','$sailthru_secret','$market_title')");
					}
				}
			}
			fclose($handle);
		}
	}

	public static function get_sailthru_lists(){
		$lists = My_Sailthru::get_lists();
		echo json_encode($lists);
		die();
	}


	public static function get_sailthru_keys(){
		$market = $_POST["sailthru_dir"];
		global $wpdb;
		$key_array = $wpdb->get_row("SELECT DISTINCT sailthru_api_key,sailthru_secret_key FROM {$wpdb->base_prefix}site_newsletter WHERE market = '$market'");
		update_option(self::SAILTHRU_MARKET, $market);
		update_option(self::SAILTHRU_API_KEY, $key_array->sailthru_api_key);
		update_option(self::SAILTHRU_SECRET_KEY, $key_array->sailthru_secret_key);
		echo json_encode($key_array);
		die();
	}

	public static function get_sailthru_market(){
		$stationInfo = get_option('_station_info');
		$sailthru_market = get_option(self::SAILTHRU_MARKET);
		return (empty($sailthru_market)) ? $stationInfo['market']  : $sailthru_market;
	}

	public static function get_sailthru_default_message(){
		$sailthru_message = get_option(self::SAILTHRU_MESSAGE_DEFAULT);
		return (empty($sailthru_message)) ? 'Thanks for subscribing to the newsletter!'  : $sailthru_message;
	}

	public static function get_sailthru_default_description(){
		$sailthru_description = get_option(self::SAILTHRU_DESCRIPTION_DEFAULT);
		return (empty($sailthru_description)) ? 'Enter your email to sign up for our newsletter'  : $sailthru_description;
	}

	public static function get_sailthru_api_key(){
		global $wpdb;
		$sailthru_api_key = get_option(self::SAILTHRU_API_KEY);
		$market = self::get_sailthru_market();
		if(empty($sailthru_api_key))
			$sailthru_api_key = $wpdb->get_row("SELECT DISTINCT sailthru_api_key FROM {$wpdb->base_prefix}site_newsletter WHERE market='$market'")->sailthru_api_key;
		return (empty($sailthru_api_key)) ? '' : $sailthru_api_key;
	}

	public static function get_sailthru_secret_key(){
		global $wpdb;
		$sailthru_secret_key = get_option(self::SAILTHRU_SECRET_KEY);
		$market = self::get_sailthru_market();
		if(empty($sailthru_secret_key))
			$sailthru_secret_key = $wpdb->get_row("SELECT DISTINCT sailthru_secret_key FROM {$wpdb->base_prefix}site_newsletter WHERE market='$market'")->sailthru_secret_key;
		return (empty($sailthru_secret_key)) ? '' : $sailthru_secret_key;
	}


	public function display_settings_page(){
		include(dirname(__FILE__) . '/admin/views/settings-site.php');
	}

	public function display_stats_page(){
		include(dirname(__FILE__) . '/admin/views/stats-site.php');
	}

	public static function is_active(){
		return get_option(self::IS_ACTIVE_OPTION_NAME);
	}

	/* Start Singleton */
	private static $instance;
	public static function init() {
		self::$instance = self::get_instance();
	}
	public static function get_instance() {
		if ( !is_a(self::$instance, __CLASS__) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	final public function __clone() {
		trigger_error("No cloning allowed!", E_USER_ERROR);
	}
	final public function __sleep() {
		trigger_error("No serialization allowed!", E_USER_ERROR);
	}
	/* End Singleton */

}

