<?php

/**
 * Storefront automatically loads the core CSS even if using a child theme as it is more efficient
 * than @importing it in the child theme style.css file.
 *
 * Uncomment the line below if you'd like to disable the Storefront Core CSS.
 *
 * If you don't plan to dequeue the Storefront Core CSS you can remove the subsequent line and as well
 * as the sf_child_theme_dequeue_style() function declaration.
 */
//add_action( 'wp_enqueue_scripts', 'sf_child_theme_dequeue_style', 999 );

/**
 * Dequeue the Storefront Parent theme core CSS
 */
function sf_child_theme_dequeue_style()
{
	wp_dequeue_style('storefront-style');
	wp_dequeue_style('storefront-woocommerce-style');
}

/* --------------------------------------------------------------
/* CUSTOM FUNCTIONS
-------------------------------------------------------------- */

add_filter('big_image_size_threshold', '__return_false');
function custom_copy($src, $dst)
{

	// open the source directory 
	$dir = opendir($src);

	// Make the destination directory if not exist 
	@mkdir($dst);

	// Loop through the files in source directory 
	while ($file = readdir($dir)) {

		if (($file != '.') && ($file != '..')) {
			if (is_dir($src . '/' . $file)) {

				// Recursively calling custom copy function 
				// for sub directory  
				custom_copy($src . '/' . $file, $dst . '/' . $file);
			} else {
				copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
	}

	closedir($dir);
}
// retrieves the attachment ID from the file URL
function get_image_id($image_url)
{
	global $wpdb;
	$attachment = $wpdb->get_col($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid='%s';", $image_url));
	return $attachment[0];
}
/**
 * Get an attachment ID given a URL.
 * 
 * @param string $url
 *
 * @return int Attachment ID on success, 0 on failure
 */
function get_attachment_id($url)
{

	$attachment_id = 0;

	$dir = wp_upload_dir();

	if (false !== strpos($url, $dir['baseurl'] . '/')) { // Is URL in uploads directory?
		$file = basename($url);

		$query_args = array(
			'post_type'   => 'attachment',
			'post_status' => 'inherit',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'value'   => $file,
					'compare' => 'LIKE',
					'key'     => '_wp_attachment_metadata',
				),
			)
		);

		$query = new WP_Query($query_args);

		if ($query->have_posts()) {

			foreach ($query->posts as $post_id) {

				$meta = wp_get_attachment_metadata($post_id);

				$original_file       = basename($meta['file']);
				$cropped_image_files = wp_list_pluck($meta['sizes'], 'file');

				if ($original_file === $file || in_array($file, $cropped_image_files)) {
					$attachment_id = $post_id;
					break;
				}
			}
		}
	}

	return $attachment_id;
}
/* create a compressed zip file */
function createZip($files = array(), $destination = '', $overwrite = false)
{

	if (file_exists($destination) && !$overwrite) {
		return false;
	}
	$validFiles = [];
	if (is_array($files)) {
		foreach ($files as $file => $value) {
			$upload_dir = wp_upload_dir();
			if (file_exists($upload_dir['path'] . '/' . $value)) {
				$validFiles[] = $value;
			}
		}
	}

	if (count($validFiles)) {
		unlink($upload_dir['path'] . '/' . $destination);
		$zip = new ZipArchive();
		$destination_p = $upload_dir['path'] . '/' . $destination;

		if ($zip->open($destination_p, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true) {
			return false;
		}

		foreach ($validFiles as $file) {
			$zip->addFile($upload_dir['path'] . '/' . $file, $file);
		}

		$zip->close();
		return file_exists($destination);
	} else {
		return false;
	}
}
add_filter('woocommerce_order_item_get_formatted_meta_data', 'change_formatted_meta_data', 20, 5);

/**
 * Filterting the meta data of an order item.
 * @param  array         $meta_data Meta data array
 * @param  WC_Order_Item $item      Item object
 * @return array                    The formatted meta
 */
function change_formatted_meta_data($meta_data, $item)
{
	$new_meta = array();
	foreach ($meta_data as $id => $meta_array) {
		// We are removing the meta with the key 'something' from the whole array.
		if ('customer_image' === $meta_array->key || 'ImageUrl' === $meta_array->key || 'rgb_color' === $meta_array->key || 'size' === $meta_array->key || 'item' === $meta_array->key || 'print_image' === $meta_array->key) {
			continue;
		}
		$new_meta[$id] = $meta_array;
	}
	return $new_meta;
}
add_action('woocommerce_email_before_order_table', 'ts_email_after_order_table', 10, 4);

function ts_email_after_order_table($order, $sent_to_admin, $plain_text, $email)
{
	if (!is_a($order, 'WC_Order')) {
		return;
	}
	require_once(ABSPATH . 'wp-admin/includes/file.php');
	if (!function_exists('wp_crop_image')) {
		include(ABSPATH . 'wp-admin/includes/image.php');
	}
	$order_data = $order->get_data();
	$order_image = get_post_meta($order->get_id(), 'image', true);
	$upload_dir = wp_upload_dir();
	$your_pdf_path = $upload_dir['path'] . '/' . $order_image;
	$info = pathinfo($order_image);
	$stripped_fn = str_replace(' ', '-', trim($order_data['billing']['first_name']));
	$stripped_ln = str_replace(' ', '-', trim($order_data['billing']['last_name']));
	// $new_zip_name = 'My-Face-Mask-' . $stripped_fn .'-'. $stripped_ln .'-Order'.$order->get_order_number();
	$new_zip_name = 'MyFaceMask-' . $order->get_order_number();

	$file_name =  basename($order_image, '.' . $info['extension']);
	$your_pdf_path_new = $upload_dir['path'] . '/' . $new_zip_name . '.' . $info['extension'];
	$zip_rename = rename($your_pdf_path, $your_pdf_path_new);

	update_post_meta($order->get_id(), 'image', $your_pdf_path_new);
	$order_image = get_post_meta($order->get_id(), 'image', true);
	//unzipping
	// Unzip to uploads folder
	WP_Filesystem();
	$destination = wp_upload_dir();
	$destination_path = $destination['path'];
	$permissions = 0755;
	$oldmask = umask(0);
	$new_upload_dir = $destination_path . '/' . $new_zip_name;
	if (!is_dir($new_upload_dir)) {
		mkdir($new_upload_dir, $permissions);
		$umask = umask($oldmask);
		$chmod = chmod($new_upload_dir, $permissions);
		$unzipfile = unzip_file($destination_path . '/' . $new_zip_name . '.' . $info['extension'], $new_upload_dir);
	}

	$image_name = array();
	// CHECKING WHETHER PATH IS A DIRECTORY OR NOT 
	if (is_dir($new_upload_dir)) {
		// GETING INTO DIRECTORY 
		$files = opendir($new_upload_dir);
		// CHECKING FOR SMOOTH OPENING OF DIRECTORY 
		if ($files) {
			//READING NAMES OF EACH ELEMENT INSIDE THE DIRECTORY  
			while (($gfg_subfolder = readdir($files)) !== FALSE) {
				// CHECKING FOR FILENAME ERRORS 
				if ($gfg_subfolder != '.' && $gfg_subfolder != '..') {
					$dirpath = $new_upload_dir . "/" . $gfg_subfolder . "/";
					custom_copy($dirpath, $new_upload_dir);
					global $wp_filesystem;
					$wp_filesystem->rmdir($dirpath, true);
					$file = opendir($new_upload_dir);
					if ($file) {
						while (($gfg_filename = readdir($file)) !== FALSE) {
							if ($gfg_filename != '.' && $gfg_filename != '..') {
								$image_name[] = $gfg_filename;
							}
						}
					}
				}
			}
			$order = wc_get_order($order->get_id());
			$image_name = array_unique($image_name);
			$count = $new_count = $count_M_img = 0;
			$New_IMGFilePath = array();
			foreach ($image_name as $name => $value) {
				$count = $count + 1;
				$IMGFilePath = $new_upload_dir . "/" . $value;
				if (file_exists($IMGFilePath)) {
					//prepare upload image to WordPress Media Library
					//prepare upload image to WordPress Media Library
					$line_count = 0;
					foreach ($order->get_items() as $item_id => $item_obj) {
						$line_count = $line_count + 1;
						$custom_field = wc_get_order_item_meta($item_id, 'customer_image', true);
						$custom_item = wc_get_order_item_meta($item_id, 'item', true);
						$custom_field_mask = wc_get_order_item_meta($item_id, 'print_image', true);
						$new_string = pathinfo($value, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($value, PATHINFO_EXTENSION));
						$value = $new_string;
						if ($custom_field == $value) {
							$formated_title = $order->get_order_number() . '-' . $line_count . '-CustomerImage';
						} elseif ($custom_field_mask == $value) {
							$formated_title = $order->get_order_number() . '-' . $line_count . '-PrintImage';
						}
					}
					//$formated_title = $order->get_order_number() . '-' . $count . '-' . $stripped_fn . '-' . $stripped_ln;  
					$upload = wp_upload_bits($value, null, file_get_contents($IMGFilePath, FILE_USE_INCLUDE_PATH));

					// check and return file type
					$imageFile = $upload['file'];
					$imageFile_info = pathinfo($imageFile);
					$new_img_path = $upload_dir['path'] . '/' . $formated_title . '.' . $imageFile_info['extension'];
					$zip_rename = rename($imageFile, $new_img_path);
					$wpFileType = wp_check_filetype($new_img_path, null);
					// Attachment attributes for file
					$check_args = array(
						'post_type' => 'attachment',
						'name' => $formated_title,
						'posts_per_page' => 1,
						'post_status' => 'inherit',
					);
					$_header = get_posts($check_args);
					$header = $_header ? array_pop($_header) : null;
					$image_url_check = $header ? wp_get_attachment_url($header->ID) : '';
					if (!$image_url_check) {
						$attachment = array(
							'post_mime_type' => $wpFileType['type'],  // file type
							//'post_title' => sanitize_file_name($value),  // sanitize and use image name as file name
							'post_title' => $formated_title,
							'post_content' => '',  // could use the image description here as the content
							'post_status' => 'inherit'
						);
						// insert and return attachment id
						$attachmentId = wp_insert_attachment($attachment, $new_img_path);
						// insert and return attachment metadata

						$attachmentData = wp_generate_attachment_metadata($attachmentId, $new_img_path);
						// update and return attachment metadata
						wp_update_attachment_metadata($attachmentId, $attachmentData);
						$uploaded_file = pathinfo($attachmentData['file']);
						$name_upload_file = $uploaded_file['basename'];
					} else {
						$uploaded_file = pathinfo($image_url_check);
						$name_upload_file = $uploaded_file['basename'];
					}
					$item_count = 0;
					$image_title = get_the_title($attachmentId);
					$New_IMGFilePath[] = $name_upload_file;
					$item_count = 0;
					foreach ($order->get_items() as $item_id => $item_obj) {
						$item_count = $item_count + 1;
						// full path of image
						// Here you get your data
						$custom_field = wc_get_order_item_meta($item_id, 'customer_image', true);
						$custom_field_mask = wc_get_order_item_meta($item_id, 'print_image', true);

						$new_string = pathinfo($value, PATHINFO_FILENAME) . '.' . strtolower(pathinfo($value, PATHINFO_EXTENSION));
						$value = $new_string;
						if ($custom_field == $value) {
							$pd = wc_update_order_item_meta($item_id, 'ImageUrl', $upload_dir['url'] . '/' . $name_upload_file);
							$pd = wc_update_order_item_meta($item_id, 'customer_image', $name_upload_file);
						} elseif ($custom_field_mask == $value) {
							$pdm = wc_update_order_item_meta($item_id, 'print_image', $name_upload_file);
						}
					}
				}
			}
		}
		# create a temp file & open it
		$formated_name = 'MyFaceMask-' . $order->get_order_number() . '.zip';
		$your_pdf_path = $upload_dir['path'] . '/';
		$fileName = $formated_name;
		if ('customer_processing_order' == $email->id) {
		} else {
			$result = createZip($New_IMGFilePath, $fileName);
		}
	}
}
add_action('woocommerce_order_item_meta_start', 'ts_order_item_meta_start', 10, 5);
function ts_order_item_meta_start($item_id, $item, $order, $plain_text)
{
	$custom_field = wc_get_order_item_meta($item_id, 'customer_image', true);
	$maskimage = wc_get_order_item_meta($item_id, 'print_image', true);
	$item = wc_get_order_item_meta($item_id, 'item', true);
	$size = wc_get_order_item_meta($item_id, 'size', true);
	$rgb_color = wc_get_order_item_meta($item_id, 'rgb_color', true);
	$destination = wp_upload_dir();
	// store the image ID in a var
	$url =  $destination['url'] . '/' . $custom_field;

	if ($url) {
		$image_id = get_attachment_id($url);
		// retrieve the thumbnail size of our image
		$image_thumb = wp_get_attachment_image_src($image_id, 'thumbnail');
		echo '<img width="100px" src="' . $image_thumb[0] . '" />';
	}
	if ($item) {
		echo '<span><b>Item :</b> ' . $item . '</span>';
	}
	if ($size) {
		echo '<br><span><b>Size :</b> ' . $size . '</span>';
	}
	if ($image_thumb[0]) {
		echo '<br><span><b>Customer :</b> ' . $custom_field . '</span>';
	}
	if ($maskimage) {
		echo '<br><span><b>Print :</b> ' . $maskimage . '</span>';
	}
	if ($rgb_color) {
		echo '<br><span><b>Colour :</b> ' . $rgb_color . '</span><br>';
	}
}
add_filter('woocommerce_email_attachments', 'attach_terms_conditions_pdf_to_email', 10, 3);

function attach_terms_conditions_pdf_to_email($attachments, $email_id, $order)
{
	if (!is_a($order, 'WC_Order') || !isset($email_id)) {
		return $attachments;
	}
	$order_image = get_post_meta($order->get_id(), 'image', true);
	$attachments[] = $order_image;
	return $attachments;
}
// disable all new user notification email
//Disable the new user notification sent to the site admin
function smartwp_disable_new_user_notifications()
{
	//Remove original use created emails
	remove_action('register_new_user', 'wp_send_new_user_notifications');
	remove_action('edit_user_created_user', 'wp_send_new_user_notifications', 10, 2);

	//Add new function to take over email creation
	add_action('register_new_user', 'smartwp_send_new_user_notifications');
	add_action('edit_user_created_user', 'smartwp_send_new_user_notifications', 10, 2);
}
function smartwp_send_new_user_notifications($user_id, $notify = 'user')
{
	return;
}
add_action('init', 'smartwp_disable_new_user_notifications');


/* --------------------------------------------------------------
/* CLASS FOR ADDING A JSON CODE INSIDE WOOCOMMERCE
-------------------------------------------------------------- */

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