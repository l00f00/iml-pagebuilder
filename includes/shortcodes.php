<?php
/**
 * Frontend Shortcodes.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode: [iml_homepage_grid]
add_shortcode('iml_homepage_grid', 'iml_render_homepage_grid');

function iml_render_homepage_grid($atts) {
    ob_start();

    // Get the Homepage ID
    $homepage_id = get_option('page_on_front');
    
    // Retrieve the items and their alignment from the meta field
    $post_ids = get_post_meta($homepage_id, 'homepage_items_alignment', true);

    if (empty($post_ids) || !is_array($post_ids)) {
        return '<p>Nessun elemento configurato per la griglia.</p>';
    }

    echo '<div id="grid-wrapper">';
    echo '<div id="custom-post-grid">';

    foreach ($post_ids as $post_id => $alignment) {
        $post_type = get_post_type($post_id);
        $post_obj = get_post($post_id);
        
        if (!$post_obj) continue;

        setup_postdata($post_obj);

        if ($post_type === 'progetto' || $post_type === 'serie' || $post_type === 'portfolio') {
            $categories = get_the_terms($post_id, 'category');
            $tags = get_the_terms($post_id, 'post_tag');
            $title = get_the_title($post_id);
            $the_thumb = get_the_post_thumbnail($post_id, 'large');
            
            echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '">';
            echo '<div class="info-overlay">';
            echo '<div class="categories-tags">';
            // Categories and tags loop commented out in original code, kept as logic placeholder
            echo '</div>';
            echo '<div class="year-title">';
            echo '<span class="title">' . esc_html($title) . '</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="image-wrapper">' . $the_thumb . '</div>';
            echo '</a>';
            
        } elseif ($post_type === 'attachment') {
            $title = get_the_title($post_id);
            $single_page_true = get_post_meta($post_id, 'has_single_page', true);
            
            // Logic to determine the link
            $parent_id = wp_get_post_parent_id($post_id);
            $parent_type = $parent_id ? get_post_type($parent_id) : null;
            $href = '';

            if ($parent_id) {
                $href = get_permalink($parent_id);
                $title = get_the_title($parent_id);
            } elseif ($single_page_true == '1' && $parent_id == 0) {
                $href = get_permalink($post_id);
            } else {
                // Fallback if no parent and not single page? Original code implies it might just be a link to image or nothing?
                // Original code logic:
                // if ($parent_id) { ... } elseif ($single_page_true == '1' ...) { ... }
                // If neither, $href remains empty or undefined in original?
                // Assuming it links to the attachment page or file if no other logic applies.
                $href = wp_get_attachment_url($post_id); 
            }

            $the_thumb = wp_get_attachment_image($post_id, 'large');
            
            echo '<a href="' . esc_url($href) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . ' ' . esc_attr($post_type) . ' ' . esc_attr($parent_type) . '" data-id="' . esc_attr($post_id) . '">';
            echo '<div class="info-overlay">';
            echo '<div class="categories-tags">';
            echo '</div>';
            echo '<div class="year-title">';
            echo '<span class="title">' . esc_html($title) . '</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="image-wrapper">' . $the_thumb . '</div>';
            echo '</a>';
        }

        wp_reset_postdata();
    }

    echo '</div>'; // End #custom-post-grid
    echo '</div>'; // End #grid-wrapper

    return ob_get_clean();
}
