<?php

class My_Sailthru {

	private static $credentials;
	private static $client;

	private function __construct() {
		include_once dirname(__FILE__).'/sailthru/Sailthru_Client_Exception.php';
		include_once dirname(__FILE__).'/sailthru/Sailthru_Client.php';
		include_once dirname(__FILE__).'/sailthru/Sailthru_Util.php';
		self::set_credentials();
		self::$client = new Sailthru_Client(self::$credentials['key'], self::$credentials['secret']);
	}

	private static function maybe_intialize(){
		if(!is_a(self::$instance, __CLASS__))
			self::init();
	}

	public static function set_credentials($creds = array()){
		if(!empty($creds)) {
			self::$credentials = $creds;
		} elseif(self::$credentials == null) {
			self::$credentials = array(
				'key' => Sailthru_Admin_Interface::get_sailthru_api_key(),
				'secret' => Sailthru_Admin_Interface::get_sailthru_secret_key()
			);
		}
	}

	public static function subscribe_user($_email, $_list, $_templates = null) {
		self::maybe_intialize();
		global $blog_id;
		$response = self::$client->setEmail($_email, null, array($_list=>'1'), $_templates);
		$user = get_user_by('email', (string) $_email);
		if(empty($user->ID)) {
			$pwd = wp_generate_password();
			$user_id = wpmu_create_user($_email,$pwd,$_email);
			//wp_clear_auth_cookie();
			update_user_meta($user_id,"registration_method","newsletter");
			update_user_meta($user_id,"create_through_newletter","1");
		} else {
			$user_id = $user->ID;
		}
		update_user_meta($user_id,'ns_' . $blog_id . '_' . urlencode($_list), '1');
	}

	public static function unsubscribe_user($_email, $_list) {
		self::maybe_intialize();
		global $blog_id;
		self::$client->setEmail($_email, null, array($_list=>'0'));
		$user = get_user_by('email', (string) $_email);
		update_user_meta($user->ID,'ns_' . $blog_id . '_' . urlencode($_list), '0');
	}

	public static function get_lists() {
		self::maybe_intialize();
		$result = self::$client->getLists();
		return $result['lists'];
	}

	public static function get_list($name) {
		self::maybe_intialize();
		$result = self::$client->getList($name);
		return $result;
	}

	public static function get_stats($list=null, $date=null) {
		self::maybe_intialize();
		$result = self::$client->stats_list($list,$date);
		return $result;
	}

	/*
	Author: Rishi/Ahn
	Edit: Get primary keys from sailthru list
	Date: 10/12/15
	*/
	public static function get_primarynormal_lists(){
		self::maybe_intialize();
		$result = self::$client->apiGet( 'list', array(
			'primary' => '1',
			'type' => 'normal'
		) );
		return $result;
	}

	public static function get_stats_blast($start_date, $end_date){
		self::maybe_intialize();
		$result = self::$client->stats_blast(null, $start_date, $end_date);
		return $result;
	}

	public static function send_welcome($_email) {

		self::maybe_intialize();
		$welcome_template = get_option('sailthru_welcome_template');
		if($welcome_template === false) {
			$welcome_template = 'Welcome Email';
		}
		if($welcome_template && !empty($_email)) {
			$user = get_user_by('email', (string) $_email);
			$vars = array(
				'name' => $user->display_name,
				'first_name' => $user->first_name,
				'last_name' => $user->last_name,
			);
			do_action('log', 'SAILTHRU WELCOME EMAIL: ' . print_r($vars, true), 'sailthru');
			self::$client->send($welcome_template, $_email, $vars);
		}

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
