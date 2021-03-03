<?php 
class WC_Settings_Mobile_Config
{

	/*
     * Bootstraps the class and hooks required actions & filters.
     *
     */
	public static function init()
	{
		add_action('admin_enqueue_scripts', __CLASS__ . '::add_style_script', 99);
		add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::add_settings_tab', 50);
		add_action('woocommerce_settings_tabs_settings_mobile_config', __CLASS__ . '::settings_tab');
		add_action('woocommerce_update_options_settings_mobile_config', __CLASS__ . '::update_settings');
	}

	/*
     * enqueue a style neccesary for woocommerce custom data tab.
     *
     * @return null.
     */
	public static function add_style_script()
	{
		wp_enqueue_style('myfacemask_admin', get_stylesheet_directory_uri() . '/admin-style.css', null, null, 'all');
	}

	/*
     * Add a new settings tab to the WooCommerce settings tabs array.
     *
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels, excluding the Subscription tab.
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels, including the Subscription tab.
     */
	public static function add_settings_tab($settings_tabs)
	{
		$settings_tabs['settings_mobile_config'] = __('Mobile Configuration JSON', 'storefront');
		return $settings_tabs;
	}


	/*
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
	public static function settings_tab()
	{
		woocommerce_admin_fields(self::get_settings());
	}


	/*
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
	public static function update_settings()
	{
		woocommerce_update_options(self::get_settings());
	}


	/*
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
	public static function get_settings()
	{

		$settings = array(
			'section_title' => array(
				'name'     => __('Mobile Configuration JSON', 'storefront'),
				'type'     => 'title',
				'desc'     => '',
				'id'       => 'wc_settings_mobile_config_section_title'
			),
			'json' => array(
				'name' => __('JSON', 'storefront'),
				'type' => 'textarea',
				'desc' => __('Add here the JSON file that will config all settings on the app', 'storefront'),
				'id'   => 'wc_settings_mobile_config_json',
				'class' => 'custom_textarea_code',
				'style' => 'height: 400px'
			),
			'section_end' => array(
				'type' => 'sectionend',
				'id' => 'wc_settings_mobile_config_section_end'
			)
		);

		return apply_filters('wc_settings_mobile_config_settings', $settings);
	}
}

WC_Settings_Mobile_Config::init();

/* --------------------------------------------------------------
/* CODE TO ADDING A NEW ENDPOINT INSIDE REST API
-------------------------------------------------------------- */
/*
add_action('rest_api_init', function () {
	register_rest_route('/v1', '/mobile_config/', array(
		'methods' => 'GET',
		'callback' => 'myfacemask_mobile_config',
		'permission_callback' => 'myfacemask_auth_token'
		
	));
	
	register_rest_route('/v1', '/gallery_images/', array(
		'methods' => 'GET',
		'callback' => 'myfacemask_gallery_callback',
	));
});



add_action( 'woocommerce_api_loaded', 'my_plugin_load_api' );
add_filter( 'woocommerce_api_classes', 'my_plugin_add_api' );

function my_plugin_load_api() {
	class MyFaceMaskWCEndpoint extends WC_API_Resource {
	
		protected $base = '/mobile_config';
	
		protected $post_type = 'shop_cart';

		public function register_routes( $routes ) {
			# GET|POST /carts
			$routes[ $this->base ] = array(
				array( array( $this, 'get_mobile_config' ), WC_API_Server::READABLE ),
			);
			return $routes;
		}
		
		public function get_mobile_config() {
			$json_config = get_option('wc_settings_mobile_config_json');
			echo $json_config;
			die();
		}
	}
}

function my_plugin_add_api( $apis ) {
    $apis[] = 'MyFaceMaskWCEndpoint';
    return $apis;
}

// adding callback for endpoint
function myfacemask_mobile_config()
{
	$json_config = get_option('wc_settings_mobile_config_json');
	echo $json_config;
}


// adding callback for endpoint
function myfacemask_gallery_callback()
{
	$json_config = get_option('wc_settings_mobile_config_json');
	echo $json_config;
}
*/

class WC_REST_Custom_Controller {
	/**
	 * You can extend this class with
	 * WP_REST_Controller / WC_REST_Controller / WC_REST_Products_V2_Controller / WC_REST_CRUD_Controller etc.
	 * Found in packages/woocommerce-rest-api/src/Controllers/
	 */
	protected $namespace = 'wc/v3';

	protected $rest_base = 'mobile_config';

	public function get_mobile_config( \WP_REST_Request $data ) {
		global $wpdb;
		
		$logger = wc_get_logger();
		
		foreach (getallheaders() as $name => $value) {
			$logger->info($name . ' ' . $value);
			if ($name == 'Authorization') {
				$auth = $value;
			}
		}
				
		$auth_explode = explode(' ', $auth);
		$consumer_key = base64_decode($auth_explode[1]);
		
		$consumer_explode_key = explode(':', $consumer_key);
			
		$consumer_key = wc_api_hash( sanitize_text_field( $consumer_explode_key[0] ) );

		$keys = $wpdb->get_row( $wpdb->prepare( "
			SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = '%s'
		", $consumer_key ), ARRAY_A );

		if ( empty( $keys ) ) {
			$error_handler = (object) array(
			   'code' => 'woocommerce_rest_cannot_view',
			   'message' => 'Sorry, you cannot list resources.',
			   'data' => 
			  (object) array(
				 'status' => 401,
			  ),
			);
			echo json_encode($error_handler);
		} else {
			$json_config = get_option('wc_settings_mobile_config_json');
			echo $json_config;	
		}
	}
	
	public function get_gallery_images( \WP_REST_Request $data ) {
		global $wpdb;
		
		$logger = wc_get_logger();
				
		foreach (getallheaders() as $name => $value) {
			$logger->info($name . ' ' . $value);
			if ($name == 'Authorization') {
				$auth = $value;
			}
		}
		
		$auth_explode = explode(' ', $auth);
		$consumer_key = base64_decode($auth_explode[1]);
		
		$consumer_explode_key = explode(':', $consumer_key);
			
		$consumer_key = wc_api_hash( sanitize_text_field( $consumer_explode_key[0] ) );

		$keys = $wpdb->get_row( $wpdb->prepare( "
			SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = '%s'
		", $consumer_key ), ARRAY_A );

		if ( empty( $keys ) ) {
			$error_handler = (object) array(
			   'code' => 'woocommerce_rest_cannot_view',
			   'message' => 'Sorry, you cannot list resources.',
			   'data' => 
			  (object) array(
				 'status' => 401,
			  ),
			);
			echo json_encode($error_handler);
		} else {
			echo 'the Galleryyy';	
		}
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/mobile_config',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_mobile_config' ),
				'permission_callback' => function() { return ''; }
			)
		);
		
		register_rest_route(
			$this->namespace,
			'/gallery_images',
			array(
				'methods' => 'GET',
				'callback' => array( $this, 'get_gallery_images' ),
				'permission_callback' => function() { return ''; }
			)
		);
	}
}

add_filter( 'woocommerce_rest_api_get_rest_namespaces', 'woo_custom_api' );

function woo_custom_api( $controllers ) {
	$controllers['wc/v3']['mobile_config'] = 'WC_REST_Custom_Controller';

	return $controllers;
}