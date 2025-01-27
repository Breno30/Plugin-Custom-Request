<?php
/*
Plugin Name: Custom Request
Description: Create a custom post type for Requests.
Version: 1.0.0
Requires PHP: 7.4
Stable tag: 1.0.0
Tested up to: 6.7.1
License: GPLv2 or later
Author: Breno do Nascimento Silva
*/

function create_requests_post_type()
{
    $labels = [
        'name' => 'Requests',
        'singular_name' => 'Request',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Request',
        'edit_item' => 'Edit Request',
        'new_item' => 'New Request',
        'view_item' => 'View Request',
        'view_items' => 'View Requests',
        'search_items' => 'Search Requests',
        'not_found' => 'No Requests found',
        'not_found_in_trash' => 'No Requests found in Trash',
        'parent_item_colon' => 'Parent Request:',
        'menu_name' => 'Requests',
    ];

    $args = [
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'publicly_queryable' => true,
        'query_var' => true,
        'rewrite' => ['slug' => 'requests'],
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_icon' => 'dashicons-admin-site-alt3',
        'supports' => [
            'revisions',
        ],
    ];

    register_post_type('requests', $args);
}

function add_requests_custom_fields_meta_box()
{
    add_meta_box(
        'requests_custom_fields',
        'Request',
        'render_requests_custom_fields_meta_box',
    );
}

function render_requests_custom_fields_meta_box($post)
{
    $url_value = get_post_meta($post->ID, '_url_key', true);
    $access_path_value = get_post_meta($post->ID, '_access_path_key', true);
    $shortcut_value = get_post_meta($post->ID, '_shortcut_key', true);
    $payload_response_value = get_post_meta($post->ID, '_payload_response', true);
?>
    <h3>Shortcut</h3>
    <input type="text" id="shortcut" name="shortcut" required value="<?php echo esc_attr($shortcut_value); ?>" style="width: 100%;" /><br>

    <h3>Data</h3>
    <div class="pcr__data--wrapper">
        <input type="text" id="url" name="url" required value="<?php echo esc_attr($url_value); ?>" style="width: 100%;" />
        <button id="btn-send-request">Test</button>
    </div>

    <h3>Answer</h3>
    <div class="custom-request__answer" id="custom-request__answer"></div>
    <input type="hidden" id="payload_response" name="payload_response" value='<?php echo esc_html($payload_response_value); ?>'>
    <input type="hidden" id="access_path" name="access_path" value="<?php echo esc_attr($access_path_value); ?>" style="width: 100%;" />
    <?php wp_nonce_field('requests_custom_fields', 'my_nonce'); ?>
<?php
}

function save_requests_custom_fields($post_id)
{
    $nonce = isset($_POST['my_nonce'])? sanitize_text_field(wp_unslash($_POST['my_nonce'])): null;

    if (!isset($nonce) || !wp_verify_nonce($nonce , 'requests_custom_fields' )) {
        return;
    }

    if (isset($_POST['url'])) {
        $url_value = sanitize_text_field(wp_unslash($_POST['url']));
        update_post_meta($post_id, '_url_key', $url_value);
    }

    if (isset($_POST['access_path'])) {
        $value = sanitize_text_field(wp_unslash($_POST['access_path']));
        update_post_meta($post_id, '_access_path_key', $value);
    }

    if (isset($_POST['shortcut'])) {
        $value = sanitize_text_field(wp_unslash($_POST['shortcut']));
        update_post_meta($post_id, '_shortcut_key', $value);
    }

    if (isset($_POST['payload_response'])) {
        $value = sanitize_text_field(wp_unslash($_POST['payload_response']));
        update_post_meta($post_id, '_payload_response', $value);
    }
}

function fetch_shortcut_value($custom_post_id, $shortcut_key) : string {
    // Initialize Redis
    $redis = new Redis();

    if (!defined('WP_REDIS_HOST')) {
        define('WP_REDIS_HOST', '127.0.0.1'); // Default to localhost
    }

    if (!defined('WP_REDIS_PORT')) {
        define('WP_REDIS_PORT', 6379); // Default Redis port
    }

    $redis->connect(WP_REDIS_HOST, WP_REDIS_PORT);

    $cache_key = "shortcut:{$shortcut_key}";

    // Check if value exists in Redis
    if ($redis->exists($cache_key)) {
        // Fetch cached value
        return $redis->get($cache_key);
    }

    $url = get_post_meta($custom_post_id, '_url_key', true);
    $access_path = get_post_meta($custom_post_id, '_access_path_key', true);
    $access_path_list = explode(',', $access_path);

    $response = wp_remote_get($url);

    $value = json_decode($response['body'], true);

    // $value = json_decode($response, true);
    foreach ($access_path_list as $access_path_item) {
        $value = $value[$access_path_item];
    }

    // Update redis cache
    $redis->set($cache_key, $value, 60);

    return $value; 
}

$args = [
    'post_type' => 'requests',
    'posts_per_page' => 1,
    'fields' => 'ids'
];

$custom_post_ids = get_posts($args);

foreach($custom_post_ids as $custom_post_id) {
    $shortcut_key = get_post_meta($custom_post_id, '_shortcut_key', true);

    add_shortcode($shortcut_key, fn() =>
        fetch_shortcut_value($custom_post_id, $shortcut_key)
    );
}

add_action('add_meta_boxes', 'add_requests_custom_fields_meta_box');
add_action('save_post_requests', 'save_requests_custom_fields');
add_action('init', 'create_requests_post_type');

function enqueue_admin_styles()
{
    $version = '1.0.0';
    wp_enqueue_style('cusyom-request-style', plugin_dir_url(__FILE__) . 'style.css', [], $version);
    wp_enqueue_script('cusyom-request-script', plugin_dir_url(__FILE__) . 'script.js', [], $version, true);
}
add_action('admin_enqueue_scripts', 'enqueue_admin_styles');

// Save shortcut as title
function my_save_meta_function( $post_id, $post )
{
	if ( get_post_type( $post_id ) !== 'requests' ) return;

    $shortcut_key = get_post_meta($post_id, '_shortcut_key', true);

    if ($shortcut_key == $post->post_title) return;

    $post_update = array(
        'ID'         => $post_id,
        'post_title' => $shortcut_key
    );
    
    wp_update_post( $post_update );
}
add_action( 'save_post', 'my_save_meta_function', 99, 2 );

// Render shortcut on title
add_filter( 'the_title', 'do_shortcode' );
add_filter( 'pre_get_document_title', 'get_the_title' );

add_filter( 'manage_requests_posts_columns', 'add_request_current_value_column' );
function add_request_current_value_column($columns) {
    $dateColumn = $columns['date'];
    unset( $columns['date'] );
    $columns['value'] = 'Value';
    $columns['date'] = $dateColumn;

    return $columns;
}

add_action( 'manage_requests_posts_custom_column' , 'render_request_current_value_column', 10, 2 );
function render_request_current_value_column( $column, $post_id ) {
    switch ( $column ) {
        case 'value' :
            $shortcut_key = get_post_meta($post_id, '_shortcut_key', true);
            echo do_shortcode("[$shortcut_key]");
            break;
    }
}