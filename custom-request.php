<?php
/*
Plugin Name: Custom Request
Description: Create a custom post type for Requests.
Version: 1.0
Author: Your Name
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
            'title',
            'custom-fields',
        ],
    ];

    register_post_type('requests', $args);
}

function add_requests_custom_fields_meta_box()
{
    add_meta_box(
        'requests_custom_fields',
        'Custom Fields',
        'render_requests_custom_fields_meta_box',
    );
}

function render_requests_custom_fields_meta_box($post)
{
    $url_value = get_post_meta($post->ID, '_url_key', true);
    $access_path_value = get_post_meta($post->ID, '_access_path_key', true);
    $shortcut_value = get_post_meta($post->ID, '_shortcut_key', true);
?>
    <label for="url">Url:</label>
    <input type="text" id="url" name="url" value="<?php echo esc_attr($url_value); ?>" style="width: 100%;" /><br>

    <label for="access_path">Object access path:</label>
    <input type="text" id="access_path" name="access_path" value="<?php echo esc_attr($access_path_value); ?>" style="width: 100%;" /><br>

    <label for="shortcut">Shortcut:</label>
    <input type="text" id="shortcut" name="shortcut" value="<?php echo esc_attr($shortcut_value); ?>" style="width: 100%;" /><br>
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
}

function replace_shortcut_content($content)
{
    $args = [
        'post_type' => 'requests',
        'posts_per_page' => -1,
        'fields' => 'ids'
    ];

    $custom_post_ids = get_posts($args);

    foreach ($custom_post_ids as $post_id) {

        $url = get_post_meta($post_id, '_url_key', true);
        $access_path = get_post_meta($post_id, '_access_path_key', true);
        $access_path_list = explode(',', $access_path);
        $shortcut = get_post_meta($post_id, '_shortcut_key', true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $value = json_decode($response, true);
        foreach ($access_path_list as $access_path_item) {
            $value = $value[$access_path_item];
        }

        $content = str_replace($shortcut, $value, $content);
    }

    return $content;
}

add_filter('the_content', 'replace_shortcut_content');
add_action('add_meta_boxes', 'add_requests_custom_fields_meta_box');
add_action('save_post_requests', 'save_requests_custom_fields');
add_action('init', 'create_requests_post_type');
