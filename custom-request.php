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
?>
    <label for="url">Url:</label>
    <input type="text" id="url" name="url" value="<?php echo esc_attr($url_value); ?>" style="width: 100%;" />
<?php
}

function save_requests_custom_fields($post_id)
{
    if (isset($_POST['url'])) {
        $url_value = sanitize_text_field($_POST['url']);
        update_post_meta($post_id, '_url_key', $url_value);
    }
}

add_action('add_meta_boxes', 'add_requests_custom_fields_meta_box');
add_action('save_post_requests', 'save_requests_custom_fields');
add_action('init', 'create_requests_post_type');
