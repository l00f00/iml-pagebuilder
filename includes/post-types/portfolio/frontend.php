<?php
/**
 * Shortcode: [iml_portfolio_grid]
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('iml_portfolio_grid', 'iml_render_portfolio_grid');

function iml_render_portfolio_grid($atts) {
    ob_start();

    $portfolio_post_id = get_the_ID();
    $portfolio_items = get_post_meta($portfolio_post_id, 'portfolio_items', true);

    if (empty($portfolio_items) || !is_array($portfolio_items)) {
        return '<p>Nessun elemento nel portfolio.</p>';
    }

    echo '<div id="grid-wrapper">';
    echo '<div id="custom-post-grid" class="gallery">';

    foreach ($portfolio_items as $portfolio_single_item_id) {
        $alignment = get_post_meta($portfolio_single_item_id, 'portfolio_item_alignment', true);
        $post_obj = get_post($portfolio_single_item_id);

        if ($post_obj) {
            $post_type = get_post_type($portfolio_single_item_id);
            setup_postdata($post_obj);
            if ($post_type === 'progetto' || $post_type === 'serie' || $post_type === 'portfolio_item') {
                $categories = get_the_terms( $portfolio_single_item_id, 'category' );
                $tags = get_the_terms( $portfolio_single_item_id, 'post_tag' );
                $year = rwmb_meta('anno', '', $portfolio_single_item_id);
                $title = get_the_title($portfolio_single_item_id);
                $the_thumb = get_the_post_thumbnail($portfolio_single_item_id, 'full');
                
                echo '<a href="' . esc_url(get_permalink($portfolio_single_item_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($portfolio_single_item_id) . '"> 
                      <div class="info-overlay"> 
                        <div class="year-title">
                          <span class="title">' . esc_html($title) . '</span> 
                        </div> 
                      </div> 
                      <div class="image-wrapper">' . $the_thumb . '</div> 
                      </a>';
            
            } elseif ($post_type === 'attachment') {
                // $categories = get_the_category_list('<ul><li>', '</li><li>', '</li></ul>', $portfolio_single_item_id);
                // $tags = get_the_tag_list('<ul><li>', '</li><li>', '</li></ul>', $portfolio_single_item_id);
                // $year = rwmb_meta('anno', '', $portfolio_single_item_id);
                $parent_project = wp_get_post_parent_id($portfolio_single_item_id);
                $parent_project_title = get_the_title($parent_project);
                $title = get_the_title($portfolio_single_item_id);
                $single_page_true = get_post_meta($portfolio_single_item_id, 'has_single_page', true);
                $image_url = wp_get_attachment_url($portfolio_single_item_id); 
                $thumbnail = wp_get_attachment_image_url($portfolio_single_item_id, 'full');
                
                if ($single_page_true == '1') {
                    echo '<a href="' . esc_url(get_permalink($portfolio_single_item_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($portfolio_single_item_id) . '">';
                } else {
                    $parent_progetto_url = get_permalink($parent_project);
                    echo '<a href="' . esc_url($parent_progetto_url) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($portfolio_single_item_id) . '" data-lightbox="gallery">';
                }
                
                echo '<div class="info-overlay">';
                
                // Categories and Tags - Only show for non-attachments
                if ($post_type !== 'attachment') {
                    // echo '<div class="categories-tags">';
                    // echo '<ul>';
                    // // Tags and cats logic
                    // echo '</ul>';
                    // echo '</div>';
                }
                
                echo '<div class="year-title">';
                if ($post_type !== 'attachment') {
                    echo '<span class="year">' . esc_html($year) . '</span>';
                }
                echo '<span class="title">' . esc_html($title) . '</span>';
                
                // if ($single_page_true) {
                //     echo '<span class="single-page-icon" style="margin-left:5px; font-size:12px;">❐</span>';
                // }
                
                echo '</div>';
                echo '</div>';
                echo '<div class="image-wrapper">';
                echo wp_get_attachment_image($portfolio_single_item_id, 'full');
                echo '</div>';
                echo '</a>';
            }
            
            wp_reset_postdata(); 
        }
    }

    echo '</div>';
    echo '</div>';
    
    // Add script for Lightbox
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
        });
    });
    </script>
    <?php

    return ob_get_clean();
}
