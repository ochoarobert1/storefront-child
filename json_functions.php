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
class WC_REST_Custom_Controller
{
	/**
	 * You can extend this class with
	 * WP_REST_Controller / WC_REST_Controller / WC_REST_Products_V2_Controller / WC_REST_CRUD_Controller etc.
	 * Found in packages/woocommerce-rest-api/src/Controllers/
	 */
	protected $namespace = 'wc/v3';

	protected $rest_base = 'mobile_config';

	public function get_mobile_config(\WP_REST_Request $data)
	{
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



		$consumer_key = wc_api_hash(sanitize_text_field($consumer_explode_key[0]));

		$keys = $wpdb->get_row($wpdb->prepare("
			SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = '%s'
		", $consumer_key), ARRAY_A);

		if (empty($keys)) {
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

	public function get_gallery_images(\WP_REST_Request $data)
	{
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

		$consumer_key = wc_api_hash(sanitize_text_field($consumer_explode_key[0]));

		$keys = $wpdb->get_row($wpdb->prepare("
			SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = '%s'
		", $consumer_key), ARRAY_A);

		if (empty($keys)) {
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

			$json_galleries = array();

			$arr_galleries = get_galleries_by_folders();

			$gallery_images = array('gallery_images' => $arr_galleries);

			echo json_encode($gallery_images);
		}
	}

	public function get_customer_details(\WP_REST_Request $data)
	{
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

		$consumer_key = wc_api_hash(sanitize_text_field($consumer_explode_key[0]));

		$keys = $wpdb->get_row($wpdb->prepare("
			SELECT key_id, user_id, permissions, consumer_key, consumer_secret, nonces
			FROM {$wpdb->prefix}woocommerce_api_keys
			WHERE consumer_key = '%s'
		", $consumer_key), ARRAY_A);

		if (empty($keys)) {
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
			$method = $data->get_method();
			$json_customer = array();

			if ($method == 'GET') {
				$get_request = $data->get_query_params();

				$id_apple = $get_request['UserId'];

				$args = array(
					'meta_query' => array(
						array(
							'key' => 'UserId',
							'value' => $id_apple,
							'compare' => '='
						)
					)
				);

				$member_arr = get_users($args); //finds all users with this meta_key == 'member_id' and meta_value == $member_id passed in url

				if ($member_arr) {
					foreach ($member_arr as $user) {
						$first_name = get_user_meta($user->ID, 'first_name', true);
						$last_name = get_user_meta($user->ID, 'last_name', true);
						$json_customer = array(
							'id' => $user->ID,
							'email' => $user->user_email,
							'first_name' => $first_name,
							'last_name' => $last_name,
						);
					}
				}
			} else {
				$get_request = $data->get_body();
				$request_json = json_decode($get_request);

				$id_apple = $request_json->userId;

				$user = get_user_by('email', $request_json->email);
				if ($user) {
					update_user_meta($user->ID, 'UserId', $id_apple);
					$first_name = get_user_meta($user->ID, 'first_name', true);
					$last_name = get_user_meta($user->ID, 'last_name', true);
					$json_customer = array(
						'id' => $user->ID,
						'email' => $user->user_email,
						'first_name' => $first_name,
						'last_name' => $last_name,
					);
				} else {
					$username = explode('@', $request_json->email);
					$user_id = wp_insert_user(array(
						'user_login' => $username[0],
						'user_pass' => $username[0],
						'user_email' => $request_json->email,
						'first_name' => $request_json->first_name,
						'last_name' => $request_json->last_name,
						'display_name' => $request_json->first_name . ' ' . $request_json->last_name,
						'role' => 'customer'
					));
					if (is_wp_error($user_id)) {
						$user = get_user_by('email', $request_json->email);
						$first_name = get_user_meta($user->ID, 'first_name', true);
						$last_name = get_user_meta($user->ID, 'last_name', true);
						$json_customer = array(
							'id' => $user->ID,
							'email' => $user->user_email,
							'first_name' => $first_name,
							'last_name' => $last_name,
						);
					} else {
						update_user_meta($user_id, 'UserId', $id_apple);
						$user = get_user_by('id', $user_id);
						$first_name = get_user_meta($user->ID, 'first_name', true);
						$last_name = get_user_meta($user->ID, 'last_name', true);
						$json_customer = array(
							'id' => $user->ID,
							'email' => $user->user_email,
							'first_name' => $first_name,
							'last_name' => $last_name,
						);
					}
				}
			}
			echo json_encode($json_customer);
		}
	}

	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			'/mobile_config',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_mobile_config'),
				'permission_callback' => function () {
					return '';
				}
			)
		);

		register_rest_route(
			$this->namespace,
			'/gallery_images',
			array(
				'methods' => 'GET',
				'callback' => array($this, 'get_gallery_images'),
				'permission_callback' => function () {
					return '';
				}
			)
		);

		register_rest_route(
			$this->namespace,
			'/customersApple',
			array(
				'methods' => array('GET', 'POST'),
				'callback' => array($this, 'get_customer_details'),
				'permission_callback' => function () {
					return '';
				}
			)
		);
	}
}

add_filter('woocommerce_rest_api_get_rest_namespaces', 'woo_custom_api');

function woo_custom_api($controllers)
{
	$controllers['wc/v3']['mobile_config'] = 'WC_REST_Custom_Controller';

	return $controllers;
}
