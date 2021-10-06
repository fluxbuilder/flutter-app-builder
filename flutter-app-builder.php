<?php
/**
 * Plugin Name: Flutter App Builder
 * Plugin URI: https://github.com/fluxbuilder/flutter-app-builder
 * Description: The Flutter App Builder Plugin which is used for the FluxStore App
 * Version: 1.0.0
 * Author: InspireUI
 * Author URI: http://inspireui.com
 *
 * Text Domain: Fluxstore-Api
 */

defined('ABSPATH') or wp_die( 'No script kiddies please!' );

include plugin_dir_path(__FILE__)."templates/class-mobile-detect.php";
include plugin_dir_path(__FILE__)."templates/class-rename-generate.php";
include_once plugin_dir_path(__FILE__)."controllers/FlutterUser.php";
include_once plugin_dir_path(__FILE__)."controllers/FlutterHome.php";
include_once plugin_dir_path(__FILE__)."controllers/FlutterBooking.php";
include_once plugin_dir_path(__FILE__)."controllers/FlutterVendorAdmin.php";
include_once plugin_dir_path(__FILE__)."controllers/FlutterWoo.php";
include_once plugin_dir_path(__FILE__)."controllers/FlutterDelivery.php";
include_once plugin_dir_path(__FILE__)."functions/index.php";
include_once plugin_dir_path(__FILE__)."functions/checkout.php";
include_once plugin_dir_path(__FILE__)."controllers/FlutterMembership/index.php";
include_once plugin_dir_path(__FILE__)."controllers/FlutterTeraWallet.php";

class FluxstoreApi
{
    public $version = '1.0.0';

    public function __construct()
    {
        define('MSTORE_CHECKOUT_VERSION', $this->version);
        define('MSTORE_PLUGIN_FILE', __FILE__);
        include_once (ABSPATH . 'wp-admin/includes/plugin.php');
        if (is_plugin_active('woocommerce/woocommerce.php') == false) {
            return 0;
        }
        add_action('woocommerce_init', 'woocommerce_mstore_init');
	    function woocommerce_mstore_init() {
		    include_once plugin_dir_path(__FILE__)."controllers/FlutterOrder.php";
            include_once plugin_dir_path(__FILE__)."controllers/FlutterMultiVendor.php";
            include_once plugin_dir_path(__FILE__)."controllers/FlutterVendor.php";
            include_once plugin_dir_path(__FILE__)."controllers/helpers/DeliveryWCFMHelper.php";
			include_once plugin_dir_path(__FILE__)."controllers/helpers/DeliveryWCFMHelper.php";
            include_once plugin_dir_path(__FILE__)."controllers/helpers/VendorAdminWooHelper.php";
			include_once plugin_dir_path(__FILE__)."controllers/helpers/VendorAdminWCFMHelper.php";
			include_once plugin_dir_path(__FILE__)."controllers/helpers/VendorAdminDokanHelper.php";
	    }
        
        $order = filter_has_var(INPUT_GET, 'code') && strlen(filter_input(INPUT_GET, 'code')) > 0 ? true : false;
        if ($order) {
            add_filter('woocommerce_is_checkout', '__return_true');
        }

        add_action('wp_print_scripts', array($this, 'handle_received_order_page'));

        //add meta box shipping location in order detail
        add_action( 'add_meta_boxes', 'mv_add_meta_boxes' );
        if ( ! function_exists( 'mv_add_meta_boxes' ) )
        {
            function mv_add_meta_boxes()
            {
                add_meta_box( 'mv_other_fields', __('Shipping Location','woocommerce'), 'mv_add_other_fields_for_packaging', 'shop_order', 'side', 'core' );
            }
        }
        // Adding Meta field in the meta container admin shop_order pages
        if ( ! function_exists( 'mv_add_other_fields_for_packaging' ) )
        {
            function mv_add_other_fields_for_packaging()
            {
                global $post;
                $note = $post->post_excerpt;
                $items = explode("\n", $note);
                if (strpos($items[0],"URL:") !== false) {
                    $url = str_replace("URL:","",$items[0]);
                    echo '<iframe width="600" height="500" src="'.$url.'"></iframe>';
                }
            }
        }

        register_activation_hook( __FILE__, array($this,'create_custom_mstore_table') );

        /**
		 * Prepare data before checkout by webview
		 */
        add_action( 'template_redirect', 'fluxstore_prepare_checkout' );
        
        /**
		 * Register js file to theme
		 */
        function mstore_frontend_script() {
            wp_enqueue_script( 'my_script', plugins_url('assets/js/mstore-inspireui.js', MSTORE_PLUGIN_FILE), array( 'jquery' ), '1.0.0', true );
            wp_localize_script( 'my_script', 'MyAjax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
        }
        add_action( 'wp_enqueue_scripts', 'mstore_frontend_script' );
        // Setup Ajax action hook
        add_action( 'wp_ajax_mstore_delete_json_file', array( $this, 'mstore_delete_json_file' ) );
        add_action( 'wp_ajax_mstore_update_limit_product', array( $this, 'mstore_update_limit_product' ) );
        add_action( 'wp_ajax_mstore_update_firebase_server_key', array( $this, 'mstore_update_firebase_server_key' ) );
        add_action( 'wp_ajax_mstore_update_new_order_title', array( $this, 'mstore_update_new_order_title' ) );
        add_action( 'wp_ajax_mstore_update_new_order_message', array( $this, 'mstore_update_new_order_message' ) );
        add_action( 'wp_ajax_mstore_update_status_order_title', array( $this, 'mstore_update_status_order_title' ) );
        add_action( 'wp_ajax_mstore_update_status_order_message', array( $this, 'mstore_update_status_order_message' ) );

        //listen changed order status to notify
        add_action( 'woocommerce_order_status_changed', array( $this, 'track_order_status_changed'), 9, 4 );
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'track_new_order'));
        add_action( 'woocommerce_rest_insert_shop_order_object', array( $this, 'track_api_new_order'), 10, 4);

        $path = get_template_directory()."/templates";
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        if (file_exists($path)) {
            $templatePath = plugin_dir_path(__FILE__)."templates/mstore-api-template.php";
            if (!copy($templatePath,$path."/mstore-api-template.php")) {
                return 0;
            }
        }
    }

    function mstore_delete_json_file(){
        $id = $_REQUEST['id'];
        if(strlen($id) == 2){
            $uploads_dir   = wp_upload_dir();
            $filePath = trailingslashit( $uploads_dir["basedir"] )."/2000/01/config_".$id.".json";
            unlink($filePath);
            echo "success";
            die();
        }
    }

    function mstore_update_limit_product(){
        $limit = $_REQUEST['limit'];
        if (is_numeric($limit)) {
            update_option("mstore_limit_product", intval($limit));
        }
    }

    function mstore_update_firebase_server_key(){
        $serverKey = $_REQUEST['serverKey'];
        update_option("mstore_firebase_server_key", $serverKey);
    }

    function mstore_update_new_order_title(){
        $title = $_REQUEST['title'];
        update_option("mstore_new_order_title", $title);
    }

    function mstore_update_new_order_message(){
        $message = $_REQUEST['message'];
        update_option("mstore_new_order_message", $message);
    }

    function mstore_update_status_order_title(){
        $title = $_REQUEST['title'];
        update_option("mstore_status_order_title", $title);
    }

    function mstore_update_status_order_message(){
        $message = $_REQUEST['message'];
        update_option("mstore_status_order_message", $message);
    }

    function track_order_status_changed($id, $previous_status, $next_status){
        trackOrderStatusChanged( $id, $previous_status, $next_status );
    }

    function track_new_order($order_id){
        trackNewOrder($order_id);
    }

    function track_api_new_order($object){
        trackNewOrder($object->id);
    }

    public function handle_received_order_page()
    {
        // default return true for getting checkout library working
        if (is_order_received_page()) {
            $detect = new MDetect;
            if ($detect->isMobile()) {
                wp_register_style('mstore-order-custom-style', plugins_url('assets/css/mstore-order-style.css', MSTORE_PLUGIN_FILE));
                wp_enqueue_style('mstore-order-custom-style');
            }
        }

    }

    function create_custom_mstore_table(){
        global $wpdb;
        // include upgrade-functions for maybe_create_table;
        if (!function_exists('maybe_create_table')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'mstore_checkout';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            `code` tinytext NOT NULL,
            `order` text NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        $success = maybe_create_table($table_name, $sql);
    }
}

new FluxstoreApi();

// use JO\Module\Templater\Templater;
include plugin_dir_path(__FILE__)."wp-templater/src/Templater.php";

add_action('plugins_loaded', 'load_mstore_templater');
function load_mstore_templater()
{

    // add our new custom templates
    $my_templater = new Templater(
        array(
            // YOUR_PLUGIN_DIR or plugin_dir_path(__FILE__)
            'plugin_directory' => plugin_dir_path(__FILE__),
            // should end with _ > prefix_
            'plugin_prefix' => 'plugin_prefix_',
            // templates directory inside your plugin
            'plugin_template_directory' => 'templates',
        )
    );
    $my_templater->add(
        array(
            'page' => array(
                'mstore-api-template.php' => 'Page Custom Template',
            ),
        )
    )->register();
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// Define for the API User wrapper which is based on json api user plugin
///////////////////////////////////////////////////////////////////////////////////////////////////

// if (!is_plugin_active('json-api/json-api.php') && !is_plugin_active('json-api-master/json-api.php')) {
//     // add_action('admin_notices', 'pim_draw_notice_json_api');
//     return;
// }

//custom rest api
function mstore_users_routes() {
    $controller = new FlutterUserController();
    $controller->register_routes();
} 
add_action( 'rest_api_init', 'mstore_users_routes' );
add_action( 'rest_api_init', 'mstore_check_payment_routes' );
function mstore_check_payment_routes() {
    register_rest_route( 'order', '/verify', array(
                    'methods' => 'GET',
                    'callback' => 'mstore_check_payment',
                    'permission_callback' => function (){
                        return true;
                    },
                )
            );
}
function mstore_check_payment() {
    return true;
}



// Add menu Setting
add_action('admin_menu', 'mstore_plugin_setup_menu');

function mstore_plugin_setup_menu(){
        add_menu_page( 'Fluxstore Api', 'Fluxstore Api', 'manage_options', 'mstore-plugin', 'mstore_init' );
}

function mstore_init(){
    load_template( dirname( __FILE__ ) . '/templates/mstore-api-admin-page.php' );
}

add_filter( 'woocommerce_rest_prepare_product_variation_object','custom_woocommerce_rest_prepare_product_variation_object',20,3 );
add_filter( 'woocommerce_rest_prepare_product_object','custom_change_product_response', 20, 3 );
function custom_change_product_response( $response, $object, $request ) {
    return customProductResponse( $response, $object, $request);
}

function custom_woocommerce_rest_prepare_product_variation_object( $response, $object, $request) {

    global $woocommerce_wpml;

    /* Added By Toan */
    $is_purchased = false;
	if(isset($request['user_id'])){
		$user_id = $request['user_id'];
    	$user_data = get_userdata($user_id);
    	$user_email = $user_data->user_email;
    	$is_purchased = wc_customer_bought_product( $user_email, $user_id, $response->data['id']);
	}
    $response->data['is_purchased'] = $is_purchased;
    /* Added By Toan */

    if ( ! empty( $woocommerce_wpml->multi_currency ) && ! empty( $woocommerce_wpml->settings['currencies_order'] ) ) {

        $type  = $response->data['type'];
        $price = $response->data['price'];

            foreach ( $woocommerce_wpml->settings['currency_options'] as $key => $currency ) {
                $rate = (float)$currency["rate"];
                $response->data['multi-currency-prices'][ $key ]['price'] = $rate == 0 ? $price : sprintf("%.2f", $price*$rate);
            }
    }

    return $response;
}

// Add product image to order
add_filter('woocommerce_rest_prepare_shop_order_object', 'custom_woocommerce_rest_prepare_shop_order_object', 10, 1);
function custom_woocommerce_rest_prepare_shop_order_object( $response ) {
    if( empty( $response->data ) || empty($response->data['line_items']) ){
        return $response;
    }
    $api = new WC_REST_Products_Controller();
    $req = new WP_REST_Request('GET');
    $line_items = [];
    foreach($response->data['line_items'] as $item){
        $product_id= $item['product_id'];
        $req->set_query_params(["id"=>$product_id]);
        $res = $api->get_item($req);
        if(is_wp_error( $res )){
            $item["product_data"] = null;
        }else{
			$item["product_data"] = $res->get_data();
		}
        $line_items[] = $item;
        
    }
    $response->data['line_items'] = $line_items;
    return $response;
}


function mstore_register_order_refund_requested_order_status() {
    register_post_status( 'wc-refund-req', array(
        'label'                     => esc_attr__( 'Refund Requested'),
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop( 'Refund requested <span class="count">(%s)</span>', 'Refund requested <span class="count">(%s)</span>' )
    ) );
}
add_action( 'init', 'mstore_register_order_refund_requested_order_status' );


function add_custom_order_statuses( $order_statuses ) {
    // Create new status array.
    $new_order_statuses = array();
    // Loop though statuses.
    foreach ( $order_statuses as $key => $status ) {
        // Add status to our new statuses.
        $new_order_statuses[ $key ] = $status;
        // Add our custom statuses.
        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-refund-req']  = esc_attr__( 'Refund Requested' );
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'add_custom_order_statuses' );


function custom_status_bulk_edit( $actions ) {
	// Add order status changes.
	$actions['mark_refund-req']  = __( 'Change status to refund requested' );

	return $actions;
}
add_filter( 'bulk_actions-edit-shop_order', 'custom_status_bulk_edit', 20, 1 );