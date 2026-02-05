<?php
/**
 * Admin cleanup and general settings.
 */

if (!defined('ABSPATH')) {
    exit;
}

function disable_comments_support() {
    // Disable support for comments and trackbacks in post types
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }

    // Close comments on the front-end
    add_filter('comments_open', '__return_false', 20, 2);
    add_filter('pings_open', '__return_false', 20, 2);

    // Hide existing comments
    add_filter('comments_array', '__return_empty_array', 10, 2);

    // Remove comments page in menu
    add_action('admin_menu', function() {
        remove_menu_page('edit-comments.php');
    });

    // Redirect any user trying to access comments page
    add_action('admin_init', function() {
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_redirect(admin_url());
            exit;
        }
    });
}
add_action('init', 'disable_comments_support');

// Imposta 'wp_attachment_pages_enabled' sempre attivo (1)
add_action('init', function() {
    if (get_option('wp_attachment_pages_enabled') !== '1') {
        update_option('wp_attachment_pages_enabled', '1');
    }
});

function hide_default_posts_menu() {
    // Remove the "Posts" menu
    remove_menu_page('edit.php'); // Hides the "Posts" menu from the admin sidebar
}
add_action('admin_menu', 'hide_default_posts_menu');

function remove_posts_from_admin_bar($wp_admin_bar) {
    $wp_admin_bar->remove_node('new-post'); // Removes the "New Post" option
}
add_action('admin_bar_menu', 'remove_posts_from_admin_bar', 999);

function add_tags_to_attachments() {
    register_taxonomy_for_object_type('post_tag', 'attachment');
}
add_action('init', 'add_tags_to_attachments');

function enable_tags_meta_box_for_attachments() {
    add_meta_box(
        'tagsdiv-post_tag', // ID of the meta box
        __('Tags'), // Meta box title
        'post_tags_meta_box', // Callback function to display tags
        'attachment', // Post type
        'side', // Context (e.g., side, normal, advanced)
        'low' // Priority
    );
}
add_action('admin_menu', 'enable_tags_meta_box_for_attachments');

function get_the_tagged() {
    // Ensure this is a tag archive
    if (!is_tag()) {
        return [];
    }

    // Get the current tag
    $current_tag = get_queried_object();

    if (!$current_tag || !isset($current_tag->slug)) {
        return [];
    }

    // Prepare the query arguments
    $args = array(
        'post_type'      => array('portfolio', 'progetto', 'serie', 'attachment'), // Specify your post types
        'tax_query'      => array(
            array(
                'taxonomy' => 'post_tag', // Ensure it queries by tag taxonomy
                'field'    => 'slug',
                'terms'    => $current_tag->slug,
            ),
        ),
        'post_status'    => 'any', // Include all statuses to get attachments
        'posts_per_page' => -1,    // Get all posts
    );

    // Perform the query
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $results = [];
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = get_post(); // Collect the post object
        }
        wp_reset_postdata();
        return $results; // Return array of post objects
    }

    wp_reset_postdata();
    return []; // Return an empty array if no posts found
}
