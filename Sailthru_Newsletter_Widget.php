<?php

class Sailthru_Newsletter_Widget extends TS_Widget {

	const BASE_ID = 'tsq_sailthru_newsletter_widget';
	const NAME = 'Sailthru Newsletter Widget';
	const CLASS_NAME = 'TSQ_sailthru_newsletter_widget_class';
	const DESC = 'A newsletter subscription form.';
	const PREFIX_LOG = 'tsq-st-widget';

	private static $post_valid_email;
	private static $post_user;
	private static $post_user_is_subscribed;

	public function add_hooks(){
		add_action('wp_loaded', array($this, 'wp_loaded'));
		add_action( 'wp_ajax_update_stlists', array($this,'updateSailThruListsAJAX') );
	}

	public function updateSailThruListsAJAX() {
		echo json_encode( $this->updateSailThruLists() );
		exit;
	}

	public function updateSailThruLists() {
		$this->deleteSailthruCachedData( Sailthru_Admin_Interface::get_sailthru_api_key() );
		return $this->sailthruGetLists();
	}

	public function deleteSailthruCachedData( $key = null ){
		if( $key === null ){	//delete all
			delete_blog_option(1, Sailthru_Admin_Interface::SAILTHRU_LIST_CACHE);
		}else if( is_string( $key ) ){	//delete for specific key
			$cache = get_blog_option( 1, Sailthru_Admin_Interface::SAILTHRU_LIST_CACHE );
			unset( $cache[ $key ] );
			update_blog_option( 1, Sailthru_Admin_Interface::SAILTHRU_LIST_CACHE, $cache );
		}
	}

	public function wp_loaded(){
		if(
			!is_admin() &&
			!empty($_POST) &&
			$_POST[self::PREFIX_LOG . '-is_submitted'] &&
			!empty($_POST['email']) &&
			!empty($_POST['list'])
		) {
			if(filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
				self::$post_valid_email = true;
				global $blog_id;
				self::$post_user = get_user_by('email', (string) $_POST['email']);
				self::$post_user_is_subscribed = get_user_meta(self::$post_user->ID, 'ns_' . $blog_id . '_' . urlencode($_POST['list']), true);
				if(!self::$post_user_is_subscribed)
					My_Sailthru::subscribe_user($_POST['email'], $_POST['list']);
			} else
				self::$post_valid_email = false;
		}
	}

	public static function init() {
		add_action('admin_enqueue_scripts', array( __CLASS__, 'enqueue_resources' ), 0, 0 );
	}

	protected static function plugin_url( $path ) {
		return plugins_url($path, __FILE__);
	}

	public static function enqueue_resources() {
		wp_enqueue_style( 'chosen-styles', Sailthru_Newsletter_Widget::plugin_url('resources/css/chosen.css'), array(), version_cache() );
		wp_enqueue_style( 'multiselect-base-styles', Sailthru_Newsletter_Widget::plugin_url('resources/css/sailthru-multiselect.css'), array(), version_cache() );
		wp_enqueue_script( 'multiselect-base-script', Sailthru_Newsletter_Widget::plugin_url('resources/js/sailthru-multiselect_2.js'), array('jquery'), version_cache() );
	}

	public function changeListFilter(){
		$primary_enabled = get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE);

		if( $primary_enabled == "yes") {
			$changedList = My_Sailthru::get_primarynormal_lists();
		} else {
			$changedList = array( "lists" => My_Sailthru::get_lists() );
		}
		return $changedList;
	}

	public function sailthruGetLists() {
		// get the old data from the original sailthru widget
		$primary_enabled = get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE);
		$oldValue = get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT);

		// check data in cache
		$cachedpair =	get_blog_option(1, Sailthru_Admin_Interface::SAILTHRU_LIST_CACHE);
		$valueMatch = TRUE;
		// if data's missing fetch from http request, then save data on cache
		$cachedlist = array(
			"lists" => array()
		);

		$cachedkey = Sailthru_Admin_Interface::get_sailthru_api_key();

		if ( $cachedpair === FALSE || !isset( $cachedpair[$cachedkey] ) ) {
			$cachedlist = $this->changeListFilter();

			if ( !is_array($cachedpair) ){
				$cachedpair = array(
					$cachedkey => $cachedlist
				);
			} else {
				$cachedpair[ $cachedkey ] = $cachedlist;
			}
			update_blog_option(1, Sailthru_Admin_Interface::SAILTHRU_LIST_CACHE, $cachedpair);
		}
		$toinject = array(
			"name" => $oldValue
		);
		foreach( $cachedpair[$cachedkey]["lists"] as $list ) {
			if( $list["name"] == $oldValue ) {
				$valueMatch = TRUE;
				break;
			} else {
				$valueMatch = FALSE;
			}
		}
		if ( $valueMatch === FALSE ) {
			$cachedpair[$cachedkey]["lists"][] = $toinject;
		}

		return $cachedpair;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['description'] = strip_tags( $new_instance['description'] );
		$instance['confirmation'] = strip_tags( $new_instance['confirmation'] );
		$instance['enable_primary'] = strip_tags( $new_instance['enable_primary'] );
		$instance['list'] = array_filter( $new_instance['list'] );
		return $instance;
	}

	public function form($instance) {

		$primary_enabled = get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_TYPE);
		$confirmationMessage = Sailthru_Admin_Interface::get_sailthru_default_message();
		$defaults = array(
			'title' => 'Sign up for our newsletter',
			'description' => 'Sign up for our newsletter!',
			'confirmation' => $confirmationMessage,
			'list' => get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT)
		);

		// check options table
		$instance = wp_parse_args( (array) $instance, $defaults );
		if( !is_array( $instance[ "list" ] ) ){
			$instance[ "list" ] = array( $instance[ "list" ] );
		}
		$responseObj = $this->sailthruGetLists($instance);
		$responseKey = Sailthru_Admin_Interface::get_sailthru_api_key();
		$response = $responseObj[$responseKey];
		$response = $response["lists"];

		usort($response, function($a, $b) {
    	return strnatcasecmp($a['name'], $b['name']);
  	});
		?>
	<p>Title: <input class="widefat" name="<?php echo $this->get_field_name( 'title' ) ?>" type="text" value="<?php echo esc_attr( $instance['title'] ) ?>" /></p>
	<p>Description: <input class="widefat" name="<?php echo $this->get_field_name( 'description' ) ?>" type="text" value="<?php echo esc_attr( $instance['description'] ) ?>" /></p>
	<p>Confirmation: <input class="widefat" name="<?php echo $this->get_field_name( 'confirmation' ) ?>" type="text" value="<?php echo esc_attr( $instance['confirmation'] ) ?>" /></p>
	<span>Lists:</span>
		<div class="st-multi-select-wrapper">
			<select class="st-multi-select" multiple="multiple" name="<?php echo $this->get_field_name( 'list' ) ?>[]" style="width:226px;">
				<?php
				if(count($response)>0) {
					foreach($response as $list) {
				?>
					<option value="<?php echo $list['name']; ?>" <?php if( in_array( $list[ "name" ], $instance[ "list" ] ) ){echo "selected";}?> ><?php echo $list['name'] ?></option>

						<?php
					}
				}
				?>
			</select>
			<p><a href="javascript:" class="clear-st-multi-select">Clear all</a></p>
			<p><a href="javascript:" class="refresh-st-multi-select">Refresh list</a></p>
		</div>
	<?php
	}

	public function widget($args, $instance) {
		if ( count($instance["list"][0]) > 0 ) {
				include(dirname(__FILE__) . '/views/widget.php');
				echo $args['before_widget'];
				$title = apply_filters( 'widget_title', $instance['title'] );
				if ( !empty( $title ) ) {
					echo $args['before_title'] . $instance['title'] . $args['after_title'];
				}
				echo $content;
				echo $args['after_widget'];
		} else {
				global $json_tsm_api;
				if ($json_tsm_api->webservice) {
					return;
				}
		}
	}
	// public function widget($args, $instance) {
	// 	global $json_tsm_api;
	// 	if ($json_tsm_api->webservice) {
	// 		return;
	// 	}
	// 	else if ( count($instance["list"][0]) > 0 ){
	// 		include(dirname(__FILE__) . '/views/widget.php');
	// 			echo $args['before_widget'];
	// 			$title = apply_filters( 'widget_title', $instance['title'] );
	// 			if ( !empty( $title ) ) {
	// 				echo $args['before_title'] . $instance['title'] . $args['after_title'];
	// 			}
	// 			echo $content;
	// 			echo $args['after_widget'];
	// 	}
	// }
}
