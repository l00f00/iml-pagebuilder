<?php
/**
 * Shortcodes: [iml_archive_grid], [iml_generic_archive_grid]
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode: [iml_archive_grid]
add_shortcode('iml_archive_grid', 'iml_render_archive_grid');

function iml_render_archive_grid($atts) {
    ob_start();

    echo '<div id="grid-wrapper">';
    $is_tag_archive_class = (is_tag()) ? 'is-tag-archive' : '';
    echo '<div id="custom-post-grid" class="' . $is_tag_archive_class . '">';
    
    if (is_tag() || is_category()) {
        // Get the current term object
        $current_term = get_queried_object();
        $taxonomy = $current_term->taxonomy; // 'category' or 'post_tag'
        $term_slug = $current_term->slug; // Slug of the current term

        // Query posts with the current term
        $query_args = array(
            'post_type'      => array('progetto', 'serie', 'portfolio', 'attachment'),
            'posts_per_page' => -1,
            'tax_query'      => array(
                array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $term_slug,
                ),
            ),
            'post_status'    => 'any',
        );
        
        $post_query = new WP_Query($query_args);

        if ($post_query->have_posts()) {
            while ($post_query->have_posts()) {
                $post_query->the_post();
                $post_id = get_the_ID();
                $post_obj = $post_query->post;
                setup_postdata($post_obj);

                $post_type = get_post_type($post_id);
                $alignment = get_post_meta($post_id, 'prj_item_alignment', true);

                if (!$alignment) {
                    // Default alignment logic based on image aspect ratio
                    $image_meta = wp_get_attachment_metadata(get_post_thumbnail_id($post_id));
                    if (!empty($image_meta['width']) && !empty($image_meta['height'])) {
                        $aspect_ratio = $image_meta['width'] / $image_meta['height'];
                        if ($aspect_ratio > 1) {
                            $alignment = 'basso'; // Landscape
                        } elseif ($aspect_ratio < 1) {
                            $alignment = 'destra'; // Portrait
                        } else {
                            $alignment = 'square alto'; // Square
                        }
                    }
                }

                if ($post_type === 'progetto' || $post_type === 'serie' || $post_type === 'portfolio') {
                    $title = get_the_title($post_id);
                    $the_thumb = get_the_post_thumbnail($post_id, 'full');
                    $theimageurl = wp_get_attachment_url( get_post_thumbnail_id($post_id), 'large' );
                    $permalink = get_permalink($post_id);

                    // Modified to always link to parent single page, disabling lightbox for these post types
                    echo '<a href="' . esc_url($permalink) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '">';
                    // echo '<a href="' . esc_url($theimageurl) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '" data-lightbox="gallery">';
                    
                    echo '<div class="info-overlay">';
                    echo '<div class="categories-tags"></div>';
                    echo '<div class="year-title">';
                    echo '<span class="title">' . esc_html($title) . '</span>';
                    echo '</div>';
                    // Back button removed as per user request
                    echo '</div>';
                    echo '<div class="image-wrapper">' . $the_thumb . '</div>';
                    echo '</a>';
                    echo '<div class="hidden-caption" style="display: none;">';
                    echo '<span class="title">' . esc_html($title) . '</span>';
                    echo '</div>';

                } elseif ($post_type === 'attachment') {
                    $title = get_the_title($post_id);
                    $thumbnail = wp_get_attachment_image_url($post_id, 'full');
                    
                    // Logic for linking to parent project or single attachment page
                    $parent_id = wp_get_post_parent_id($post_id);
                    $parent_post = get_post($parent_id);
                    
                    if ($parent_post && ($parent_post->post_type === 'progetto' || $parent_post->post_type === 'serie' || $parent_post->post_type === 'portfolio')) {
                        $permalink = get_permalink($parent_id);
                        $lightbox_attr = ''; // No lightbox, go to parent
                    } else {
                         // Fallback: check if single page is enabled (though 'attachment' post type logic here is usually simple)
                         // User request: "se non c'è progetto alla pagina singola dell attachment"
                         $single_page_true = get_post_meta($post_id, 'has_single_page', true);
                         if ($single_page_true == '1') {
                            $permalink = get_permalink($post_id);
                            $lightbox_attr = ''; // No lightbox, go to single page
                         } else {
                             // Default fallback if no parent and no single page: just open image file or do nothing?
                             // User said: "se non c- progetto alla pagina singola dell attachment"
                             // Assuming if no single page, we still go to attachment page or maybe file url?
                             // Let's default to attachment single page (even if not explicitly checked) or file URL if we want lightbox?
                             // But user said "NON DEVE aprire lightbox". So let's link to attachment page.
                             $permalink = get_permalink($post_id);
                             $lightbox_attr = '';
                         }
                    }
                    
                    echo '<a href="' . esc_url($permalink) .'" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '" ' . $lightbox_attr . '>';
                    echo '<div class="info-overlay">';
                    echo '<div class="categories-tags">';
                    echo '<div class="hidden-html" href="' . esc_url($permalink) . '" data-caption="' . esc_html($title) . '"></div>';
                    echo '</div>';
                    echo '<div class="year-title">';
                    echo '<span class="title">' . esc_html($title) . '</span>';
                    echo '</div>';
                    // Back button removed as per user request
                    echo '</div>';
                    echo '<div class="image-wrapper">';
                    echo wp_get_attachment_image($post_id, 'full');
                    echo '</div>';
                    echo '</a>';
                    // Hidden caption not needed if we are linking away
                }
                wp_reset_postdata();
            }
        }
    }

    echo '</div>'; // Close custom-post-grid
    echo '</div>'; // Close grid-wrapper
    
    // JS for Archive
    ?>
    <script>
    jQuery(document).ready(function($) {
        var gallery = jQuery('a[data-lightbox="gallery"]').simpleLightbox({
            className: 'simple-lightbox',
            widthRatio: 1,
            heightRatio: 1,
            scaleImageToRatio: true,
            animationSpeed: 005,
            fadeSpeed: 5,
            animationSlide: false,
            enableKeyboard: true,
            preloading: true,
            closeText: '<div class="divclose"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 44 39" width="44" height="39"><rect x="4" y="14" width="24" height="4" fill="white" transform="rotate(45 16 16)" /><rect x="4" y="14" width="24" height="4" fill="white" transform="rotate(-45 16 16)" /></svg></div>',
            navText: ['<','>'],
            spinner: false,
            overlay: false,
            docClose: false,
            loop: true,
            alertError: false,
            captions: true,
            captionSelector: function(element) {
                return element.nextElementSibling; 
            },
            captionType: 'text',
            captionPosition: 'bottom',
            captionDelay: 0,
            captionHTML: true,
        });
    });
    </script>
    <?php

    return ob_get_clean();
}

// Shortcode: [iml_generic_archive_grid]
add_shortcode('iml_generic_archive_grid', 'iml_render_generic_archive_grid');

function iml_render_generic_archive_grid($atts) {
    ob_start();

    echo '<div id="grid-wrapper">';
    echo '<div id="custom-post-grid">';

    // The standard WordPress loop
    if (have_posts()) : 
        while (have_posts()) : the_post();
            $post_id = get_the_ID();
            $post_type = get_post_type($post_id);
            $alignment = get_post_meta($post_id, 'portfolio_item_alignment', true);

            if ($post_type === 'progetto' || $post_type === 'serie') {
                $categories = get_the_terms($post_id, 'category');
                $tags = get_the_terms($post_id, 'post_tag');
                $year = rwmb_meta('anno', '', $post_id);
                $title = get_the_title($post_id);
                $the_thumb = get_the_post_thumbnail($post_id, 'full');
                
                echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '">
                      <div class="info-overlay">
                        <div class="categories-tags">';
                echo '<ul>';
                if (!empty($categories) && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        $category_link = get_category_link($category->term_id);
                        echo '<li href="' . esc_url($category_link) . '">' . esc_html($category->name) . '</li>';
                    }
                }
                if (!empty($tags) && !is_wp_error($tags)) {
                    foreach ($tags as $tag) {
                        $tag_link = get_tag_link($tag->term_id);
                        echo '<li href="' . esc_url($tag_link) . '">' . esc_html($tag->name) . '</li>';
                    }
                }
                echo '</ul>';
                echo '</div>
                        <div class="year-title">
                          <span class="title">' . esc_html($year) . '</span>
                          <span class="title">' . esc_html($title) . '</span>
                        </div>
                      </div>
                      <div class="image-wrapper">' . $the_thumb . '</div>
                      </a>';
            
            } elseif ($post_type === 'attachment') {
                $categories = get_the_category_list('<ul><li>', '</li><li>', '</li></ul>', $post_id);
                $tags = get_the_tag_list('<ul><li>', '</li><li>', '</li></ul>', $post_id);
                $year = rwmb_meta('anno', '', $post_id);
                $title = get_the_title($post_id);
                $single_page_true = get_post_meta($post_id, 'has_single_page', true);
                $image_url = wp_get_attachment_url($post_id);
                $thumbnail = wp_get_attachment_image_url($post_id, 'full');

                if ($single_page_true == '1') {
                    echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '">';
                } else {
                    echo '<a href="' . esc_url($image_url) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '" data-lightbox="gallery">';
                }

                echo '<div class="info-overlay">';
                echo '<div class="categories-tags">';
                echo '<ul>';
                if (!empty($categories) && !is_wp_error($categories)) {
                    foreach ($categories as $category) {
                        $category_link = get_category_link($category->term_id);
                        echo '<li href="' . esc_url($category_link) . '">' . esc_html($category->name) . '</li>';
                    }
                }
                if (!empty($tags) && !is_wp_error($tags)) {
                    foreach ($tags as $tag) {
                        $tag_link = get_tag_link($tag->term_id);
                        echo '<li href="' . esc_url($tag_link) . '">' . esc_html($tag->name) . '</li>';
                    }
                }
                echo '</ul>';
                echo '</div>';
                echo '<div class="year-title">';
                echo '<span class="year">' . esc_html($year) . '</span>';
                echo '<span class="title">' . esc_html($title) . '</span>';
                echo '</div>';
                echo '</div>';
                echo '<div class="image-wrapper">';
                echo wp_get_attachment_image($post_id, 'full');
                echo '</div>';
                echo '</a>';
            } else {
                // Standard post fallback
                $title = get_the_title($post_id);
                echo '<div class="standard-post"></div>';
            }

        endwhile;
    endif;

    echo '</div>'; // custom-post-grid
    echo '</div>'; // grid-wrapper

    return ob_get_clean();
}
