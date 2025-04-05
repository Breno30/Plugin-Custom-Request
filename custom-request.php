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

add_action( 'activate_plugin', 'custom_request_check_redis_extension' , 10);

function custom_request_check_redis_extension(){
    if (!extension_loaded('redis')) {
       wp_die( '<p><strong>Plugin Deactivated:</strong> The Redis PHP extension is not installed or enabled. This plugin cannot function without it.</p><a href="/wp-admin/plugins.php">Reload</a>' );
    }
}

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
        'has_archive' => false,
        'publicly_queryable' => false,
        'query_var' => false,
        'rewrite' => false,
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
    $shortcode_value = get_post_meta($post->ID, '_shortcode_key', true);
    $payload_response_value = get_post_meta($post->ID, '_payload_response', true);
?>
    <h3>Shortcode</h3>
    <input type="text" id="shortcode" name="shortcode" required value="<?php echo esc_attr($shortcode_value); ?>" style="width: 100%;" /><br>

    <h3>Url</h3>
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

    if (isset($_POST['shortcode'])) {
        $value = sanitize_text_field(wp_unslash($_POST['shortcode']));
        update_post_meta($post_id, '_shortcode_key', $value);
    }

    if (isset($_POST['payload_response'])) {
        $value = sanitize_text_field(wp_unslash($_POST['payload_response']));
        update_post_meta($post_id, '_payload_response', $value);
    }
}

function fetch_shortcode_value($custom_post_id, $shortcode_key) : string {
    $cache_key = "shortcode:{$shortcode_key}";
    
    // Check WP Cache first
    $cached_value = wp_cache_get($cache_key);
    if ($cached_value !== false) {
        return $cached_value;
    }

    $url = get_post_meta($custom_post_id, '_url_key', true);
    $access_path = get_post_meta($custom_post_id, '_access_path_key', true);
    $access_path_list = explode(',', $access_path);

    $response = wp_remote_get($url);

    $value = json_decode($response['body'], true);

    foreach ($access_path_list as $access_path_item) {
        if($access_path_item == '') continue;

        $value = $value[$access_path_item];
    }

    // Set WP Cache
    wp_cache_set($cache_key, $value, '', 60);

    return $value;
}

$args = [
    'post_type' => 'requests',
    'posts_per_page' => -1,
    'fields' => 'ids'
];

$custom_post_ids = get_posts($args);

foreach($custom_post_ids as $custom_post_id) {
    $shortcode_key = get_post_meta($custom_post_id, '_shortcode_key', true);

    if ($shortcode_key == '') continue;

    add_shortcode($shortcode_key, fn() =>
        fetch_shortcode_value($custom_post_id, $shortcode_key)
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

// Save shortcode as title
function my_save_meta_function( $post_id, $post )
{
	if ( get_post_type( $post_id ) !== 'requests' ) return;

    $shortcode_key = get_post_meta($post_id, '_shortcode_key', true);

    if ($shortcode_key == $post->post_title) return;

    $post_update = array(
        'ID'         => $post_id,
        'post_title' => $shortcode_key
    );
    
    wp_update_post( $post_update );
}
add_action( 'save_post', 'my_save_meta_function', 99, 2 );

// Render shortcode on title
add_filter( 'the_title', 'do_shortcode' );
add_filter( 'pre_get_document_title', 'get_the_title' );

add_filter( 'manage_requests_posts_columns', 'add_request_current_value_column' );
function add_request_current_value_column($columns) {
    $dateColumn = $columns['date'];
    unset( $columns['date'] );
    $columns['value'] = 'Shortcode -> Value';
    $columns['date'] = $dateColumn;

    return $columns;
}

add_action( 'manage_requests_posts_custom_column' , 'render_request_current_value_column', 10, 2 );
function render_request_current_value_column( $column, $post_id ) {
    switch ( $column ) {
        case 'value' :
            $shortcode_key = get_post_meta($post_id, '_shortcode_key', true);
            echo '[' . $shortcode_key .'] -> '. fetch_shortcode_value($post_id, $shortcode_key);
            break;
    }
}

add_action('transition_post_status', 'prevent_draft_status', 10, 3);
function prevent_draft_status($new_status, $old_status, $post) {
    // If post is draft, set as publish
    if ($new_status === 'draft' && $post->post_type === 'requests') {
        wp_update_post(array(
            'ID' => $post->ID,
            'post_status' => 'publish'
        ));
    }
}
