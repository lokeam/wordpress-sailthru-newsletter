<?php

class Sailthru_Newsletter {
	protected static $horizon_tags = array();

    protected function __construct() {
	    add_action( 'template_include', array( $this, 'template_include' ) );
	    add_action( 'widgets_init', array( $this, 'widgets_init' ) );
		add_filter( 'body_class', array($this, 'add_body_class'));
		add_filter( 'template_include', array($this, 'newsletter_template'));
		add_filter( 'page_generic_title', array($this, 'newsletter_title'));
		add_filter( 'page_generic_content', array($this, 'newsletter_page_template'));
		add_shortcode( 'sailthru', array($this, 'sailthru_shortcode'));

		//Update wp_query
		add_action('wp', array($this, 'wp'));

		//SEO
		add_filter('aioseop_title_page',array($this, 'aioseop_title_page'));
		add_filter('google_ads_gtype',array($this,'google_ads_gtype'));

		//Sailthru Horizon
		add_action('aioseop_description_head_value', array($this, 'store_aioseop_description'));
		add_action('wp_head', array($this, 'add_horizon_meta_tags'), 10 );
		add_action('wp_enqueue_scripts', array($this, 'enqueue_horizon_scripts'));

		//User Registration Event
		add_action('user_registration_confirmed', array($this, 'sailthru_account_registration'));

		// Mobile Intercept JSON Core
		add_action('JSON_API_Core_Controller::get_page::main_content', array($this, 'modify_mobile_data_main'));
		add_action('JSON_API_Core_Controller::get_page::sidebar_content', array($this, 'modify_mobile_data_sidebar'));
    }

    public function template_include($template) {
        if(
	        isset($_REQUEST['email']) && !empty($_REQUEST['email']) &&
	        function_exists('townsquare_get_market') &&
	        preg_match('/^(\/sailthru-postback(\/)?)/', $_SERVER['REQUEST_URI']) &&
            file_exists(dirname(__FILE__) . '/views/sailthru-postback.php' )
        ) {
	        include_once(dirname(__FILE__) . '/views/sailthru-postback.php');
	        echo "------------";
	        exit();
        }
	    return $template;
	}

	public function wp() {
		global $wp_query;
		if (self::is_newsletter_page()) {
			status_header(200);
			$wp_query->is_404 = false;
			$wp_query->is_page = true;
		}
	}

	public function modify_mobile_data_main($main_content){
		// Bail if plugin is not enabled
		if (!self::is_newsletter_page())
			return $main_content;

		$sections = array();
		$section = array(
			'id' => 'tsq_sailthru_newsletter_widget-2',
			'name' => 'Sailthru Newsletter Widget',
			'type' => 'tsq_sailthru_newsletter_widget',
			'data' => array(
				'title' => 'Get Our Newsletter',
				'description' => Sailthru_Admin_Interface::get_sailthru_default_description(),
				'list' => urldecode(get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT))
			),
			// 'handle' => array( "default output handler", "W3_Plugin_TotalCache::ob_callback", "default output handler")
		);

        $main_content = array($section) + $main_content;

		return $main_content;
	}

	public function modify_mobile_data_sidebar($sidebar_content) {
		// Bail if plugin is not enabled
		if (!self::is_newsletter_page())
			return $main_content;

		foreach($sidebar_content as $index => $widget) {
			if ($widget['type'] === 'widget_listen_live') {
				unset($sidebar_content[$index]);
			}
		}

		return array_values($sidebar_content);
	}

	public function widgets_init(){
		if(get_option('sailthru_widget_active')) {
			register_widget( 'Sailthru_Newsletter_Widget' );
		}
	}

	public function add_body_class($classes){
		if( self::is_newsletter_page() ) {
			$classes[] = 'newsletter';
		}
		return $classes;
	}

	public function newsletter_template($template) {
		if( self::is_newsletter_page() && ($page = locate_template('page-generic.php')) ) {
			return locate_template('page-generic.php');
		}
		return $template;
	}

	public function newsletter_title($title){
		if( self::is_newsletter_page()) {
			return 'Get Our Newsletter';
		}
		return $title;
	}

	public function newsletter_page_template($content){
		if( self::is_newsletter_page() ) {
			ob_start();
			include(dirname(__FILE__) . '/views/newsletter-page.php');
			return ob_get_clean();
		}
		return $content;
	}

	public function is_newsletter_page() {
		global $wp_query;
		if ('newsletter' == trim(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH), '/')) {
			return true;
		}
	}

	public function sailthru_shortcode( $atts) {
		$confirmationMessage = Sailthru_Admin_Interface::get_sailthru_default_message();
		$descriptionMessage = Sailthru_Admin_Interface::get_sailthru_default_description();
		ob_start();
		the_widget(
			'Sailthru_Newsletter_Widget',
			array(
				'title' => '',
				'description' => $descriptionMessage,
				'confirmation' => $confirmationMessage,
				'list' => urldecode(get_option(Sailthru_Admin_Interface::SAILTHRU_LIST_DEFAULT)),
			),
			array(
				'before_widget' => '<div class="sailthru-newsletter">',
				'after_widget' => '</div>',
				'before_title' => '',
				'after_title' => ''
			)
		);
		$sailthru_shortcode = ob_get_contents(); /* Captured output */
		ob_end_clean(); /* Stop capture */

		return $sailthru_shortcode;
	}

	public function aioseop_title_page($title){
		if( self::is_newsletter_page() ) {
			$title = townsquare_get_market().' Newsletter Signup';
		}
		return $title;
	}

	public function google_ads_gtype($gtype){
		if( self::is_newsletter_page() ){
			$gtype = 'Newsletter';
		}
		return $gtype;
	}

	public function store_aioseop_description($description){
		if(!get_option('sailthru_widget_active')){
			return;
		}
		self::$horizon_tags['sailthru.description'] = $description;
	}

	public function add_horizon_meta_tags(){
		// only do this on pages and posts
		if( ! is_single() || !get_option('sailthru_widget_active')) {
			return;
		}

		// filter to disable all output
		if( false === apply_filters( 'sailthru_horizon_meta_tags_enable', true ) )
			return;

		global $post;

		$post_object = get_post($post);

		// date
		$post_date = $post->post_date;
		self::$horizon_tags['sailthru.date'] =  esc_attr($post_date);

		// title
		$post_title = get_the_title();
		self::$horizon_tags['sailthru.title'] = esc_attr($post_title);

		// tags and categories
		$catarray = array();
		$tagarray = array();
		$kws = array();
		$cats = get_the_category();
		$tags = get_the_tags();

		if ($tags){
			foreach($tags as $tag){
				$tagarray[] = $tag->slug;
			}
		}

		if ($cats){
			foreach($cats as $cat){
				$catarray[] = $cat->slug;
			}
		}

		$meta = Townsquare::get_station_info();

		$market = get_option(Sailthru_Admin_Interface::SAILTHRU_MARKET);
		$market = str_replace( '/', '', $market );
		$market = str_replace( ' ', '', $market );
		$market = str_replace( '.', '', $market );
		$market = strtolower($market);
		$callletters = $meta['callletters'];

		$kws = array_merge($tagarray,$catarray);
		array_unshift($kws, strtolower('tsmtype-'.esc_attr($meta['sitetype'])));
		array_unshift($kws, strtolower('tsmmarket-'.esc_attr($market)));
		array_unshift($kws, strtolower('tsmsite-'.esc_attr($callletters)));
		$commaList = implode(', ', $kws);
		self::$horizon_tags['sailthru.tags'] = esc_attr($commaList);

		// author
		$post_author = get_the_author_meta('user_nicename',$post->post_author);
		if( ! empty( $post_author ) )
			self::$horizon_tags['sailthru.author'] = $post_author;

		// image & thumbnail
		if( has_post_thumbnail( $post_object->ID ) ) {
			$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );
			$thumb = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'concierge-thumb' );

			$post_image = $image[0];
			self::$horizon_tags['sailthru.image.full'] = esc_attr($post_image);
			$post_thumbnail = $thumb[0];
			self::$horizon_tags['sailthru.image.thumb'] = $post_thumbnail;
		}

		// expiration date
		$post_expiration = get_post_meta( $post_object->ID, 'sailthru_post_expiration', true );

		if( ! empty( $post_expiration ) )
			self::$horizon_tags['sailthru.expire_date'] = esc_attr($post_expiration);

		self::$horizon_tags = apply_filters( 'sailthru_horizon_meta_tags', self::$horizon_tags, $post_object );

		$tag_output = "\n\n<!-- BEGIN Sailthru Horizon Meta Information -->\n";
		foreach ( (array) self::$horizon_tags as $tag_name => $tag_content ) {
			if ( empty( $tag_content ) )
				continue; // Don't ever output empty tags
			$meta_tag = sprintf( '<meta name="%s" content="%s" />', esc_attr( $tag_name ), esc_attr( $tag_content ) );
			$tag_output .= apply_filters( 'sailthru_horizon_meta_tags_output', $meta_tag );
			$tag_output .= "\n";
		}
		$tag_output .= "<!-- END Sailthru Horizon Meta Information -->\n\n";

		echo $tag_output;

	} // sailthru_horizon_meta_tags

	public function enqueue_horizon_scripts(){
		if( ! is_single() || !get_option('sailthru_widget_active')) {
			return;
		}
		if (get_option('sailthru_horizon_status')){
			wp_enqueue_script('horizon',plugins_url('resources/js/horizon.js', __FILE__),array(),version_cache(),true);
		}
		else {
			wp_enqueue_script('horizon',plugins_url('resources/js/horizon-error.js', __FILE__),array(),version_cache(),true);
		}
	}

	public function sailthru_account_registration($user_meta) {
		if(!empty($user_meta['user_email'])) {
			$user_id = $user_meta['id'] ? $user_meta['id'] : get_user_by('email', (string) $user_meta['user_email'])->ID;
			$list = get_option('sailthru_list_default');
			//if(($_POST['receive_emails'] || $user_meta['receive_emails']) && $list) {
			if($list) {
				$templates = null;
				$welcome_template = $this->get_welcome_template();
				if($welcome_template) {
					$templates = array($welcome_template => 1);
				}
				My_Sailthru::subscribe_user($user_meta['user_email'], $list, $templates);
				if($welcome_template) {
					My_Sailthru::send_welcome($user_meta['user_email']);
				}
			} elseif($list) {
				My_Sailthru::unsubscribe_user($user_meta['user_email'], $list);
			}
			if(1 == get_user_meta($user_id, 'create_through_newletter', true)) {
				add_user_to_blog(get_current_blog_id(), $user_id, 'subscriber');
			}
		}

		return $user_meta;
	}

	public function get_welcome_template() {
		$template = get_option('sailthru_welcome_template');
		if($template === false) {
			$template = 'Welcome Email';
		}
		return $template;
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