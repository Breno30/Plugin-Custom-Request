<?php
/*
Plugin Name: Custom Request
Description: Create a custom post type for Requests.
Version: 1.0
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
    <input type="hidden" id="payload_response" name="payload_response" value='<?php echo $payload_response_value; ?>'>
    <input type="hidden" id="access_path" name="access_path" value="<?php echo esc_attr($access_path_value); ?>" style="width: 100%;" />

<?php
}

function save_requests_custom_fields($post_id)
{
    if (isset($_POST['url'])) {
        $url_value = sanitize_text_field($_POST['url']);
        update_post_meta($post_id, '_url_key', $url_value);
    }

    if (isset($_POST['access_path'])) {
        $value = sanitize_text_field($_POST['access_path']);
        update_post_meta($post_id, '_access_path_key', $value);
    }

    if (isset($_POST['shortcut'])) {
        $value = sanitize_text_field($_POST['shortcut']);
        update_post_meta($post_id, '_shortcut_key', $value);
    }

    if (isset($_POST['payload_response'])) {
        $value = sanitize_text_field($_POST['payload_response']);
        update_post_meta($post_id, '_payload_response', $value);
    }
}

function fetch_shortcut_value($custom_post_id, $shortcut_key) : string {
    // Initialize Redis
    $redis = new Redis();
    $redis->connect('redis', 6379);

    $cache_key = "shortcut:{$shortcut_key}";

    // Check if value exists in Redis
    if ($redis->exists($cache_key)) {
        // Fetch cached value
        return $redis->get($cache_key);
    }

    $url = get_post_meta($custom_post_id, '_url_key', true);
    $access_path = get_post_meta($custom_post_id, '_access_path_key', true);
    $access_path_list = explode(',', $access_path);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $value = json_decode($response, true);
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
    wp_enqueue_style('cusyom-request-style', plugin_dir_url(__FILE__) . 'style.css');
    wp_enqueue_script('cusyom-request-script', plugin_dir_url(__FILE__) . 'script.js');
}
add_action('admin_enqueue_scripts', 'enqueue_admin_styles');
