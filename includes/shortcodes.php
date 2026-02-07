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

// Shortcode: [iml_portfolio_grid]
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

            if ($post_type === 'progetto' || $post_type === 'serie') {
                $categories = get_the_terms( $portfolio_single_item_id, 'category' );
                $tags = get_the_terms( $portfolio_single_item_id, 'post_tag' );
                $year = rwmb_meta('anno', '', $portfolio_single_item_id);
                $title = get_the_title($portfolio_single_item_id);
                $the_thumb = get_the_post_thumbnail($portfolio_single_item_id, 'large');
                
                echo '<a href="' . esc_url(get_permalink($portfolio_single_item_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($portfolio_single_item_id) . '"> 
                      <div class="info-overlay"> 
                        <div class="categories-tags">';
                echo '<ul>';
                // Categories loop (commented out in original, can be uncommented if needed)
                /*
                if ( !empty($categories) && !is_wp_error( $categories ) ) {
                    foreach ( $categories as $category ) {
                      // $category_link = get_category_link( $category->term_id );
                      // echo '<li href="' . esc_url( $category_link ) . '">' . esc_html( $category->name ) . '</li>';
                    }
                }
                */
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
                $categories = get_the_category_list('<ul><li>', '</li><li>', '</li></ul>', $portfolio_single_item_id);
                $tags = get_the_tag_list('<ul><li>', '</li><li>', '</li></ul>', $portfolio_single_item_id);
                $year = rwmb_meta('anno', '', $portfolio_single_item_id);
                $parent_project = wp_get_post_parent_id($portfolio_single_item_id);
                $parent_project_title = get_the_title($parent_project);
                $title = get_the_title($portfolio_single_item_id);
                $single_page_true = get_post_meta($portfolio_single_item_id, 'has_single_page', true);
                $image_url = wp_get_attachment_url($portfolio_single_item_id); 
                $thumbnail = wp_get_attachment_image_url($portfolio_single_item_id, 'large');
                
                if ($single_page_true == '1') {
                    echo '<a href="' . esc_url(get_permalink($portfolio_single_item_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($portfolio_single_item_id) . '">';
                } else {
                    $parent_progetto_url = get_permalink($parent_project);
                    echo '<a href="' . esc_url($parent_progetto_url) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($portfolio_single_item_id) . '" data-lightbox="gallery">';
                }
                
                echo '<div class="info-overlay">';
                echo '<div class="categories-tags">';
                echo '<ul>';
                // Tags and cats logic
                echo '</ul>';
                echo '</div>';
                echo '<div class="year-title">';
                echo '<span class="year">' . esc_html($year) . '</span>';
                echo '<span class="title">' . esc_html($title) . '</span>';
                echo '</div>';
                echo '</div>';
                echo '<div class="image-wrapper">';
                echo wp_get_attachment_image($portfolio_single_item_id, 'large');
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

// Shortcode: [iml_archive_grid]
add_shortcode('iml_archive_grid', 'iml_render_archive_grid');

function iml_render_archive_grid($atts) {
    ob_start();

    echo '<div id="grid-wrapper">';
    echo '<div id="custom-post-grid">';
    
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
                    $the_thumb = get_the_post_thumbnail($post_id, 'large');
                    $theimageurl = wp_get_attachment_url( get_post_thumbnail_id($post_id), 'large' );
                    $permalink = get_permalink($post_id);

                    echo '<a href="' . esc_url($theimageurl) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '" data-lightbox="gallery">';
                    echo '<div class="info-overlay">';
                    echo '<div class="categories-tags"></div>';
                    echo '<div class="year-title">';
                    echo '<span class="title">' . esc_html($title) . '</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="image-wrapper">' . $the_thumb . '</div>';
                    echo '</a>';
                    echo '<div class="hidden-caption" style="display: none;">';
                    echo '<span class="title">' . esc_html($title) . '</span> - <a href="' . esc_url($permalink) . '">Show '. esc_html($post_type) .'</a>';
                    echo '</div>';

                } elseif ($post_type === 'attachment') {
                    $title = get_the_title($post_id);
                    $thumbnail = wp_get_attachment_image_url($post_id, 'large');
                    $permalink = get_permalink($post_id);
                    
                    echo '<a href="' . esc_url($thumbnail) .'" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '" data-lightbox="gallery">';
                    echo '<div class="info-overlay">';
                    echo '<div class="categories-tags">';
                    echo '<div class="hidden-html" href="' . esc_url($permalink) . '" data-caption="' . esc_html($title) . '"></div>';
                    echo '</div>';
                    echo '<div class="year-title">';
                    echo '<span class="title">' . esc_html($title) . '</span>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="image-wrapper">';
                    echo wp_get_attachment_image($post_id, 'large');
                    echo '</div>';
                    echo '</a>';
                    echo '<div class="hidden-caption" style="display: none;">';
                    echo '<span class="title">' . esc_html($title) . '</span> - <a href="' . esc_url($permalink) . '" >Show Photo</a>';
                    echo '</div>';
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
                $the_thumb = get_the_post_thumbnail($post_id, 'large');
                
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
                $thumbnail = wp_get_attachment_image_url($post_id, 'large');

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
                echo wp_get_attachment_image($post_id, 'large');
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

// Shortcode: [iml_attachment_single]
add_shortcode('iml_attachment_single', 'iml_render_attachment_single');

function iml_render_attachment_single($atts) {
    ob_start();
    ?>
    <div class="foto-content"> 
     <?php 
     global $post;  // Explicitly define the global $post variable 
     $parent_post_id = $post->post_parent; 
     //echo $parent_post_id; 
     $parent_post_type = get_post_type( $parent_post_id ); 
     // Fetch the associative array from parent post meta 
     $items = get_post_meta($parent_post_id, 'prj_items', true); 
     //echo count($items); 
     $image_paths = []; 
     
     if (is_array($items)) { 
         foreach ($items as $foto_id) { 
             // Ottieni l'URL dell'immagine di dimensioni complete 
             $image_url = wp_get_attachment_image_url($foto_id, 'large'); 
             //echo $image_url; 
             if ($image_url) { 
                 $image_paths[] = stripslashes($image_url); 
             } 
         } 
     } 
     // cerco l'URL dell'immagine principale del post in diverse dimensioni 
     // nell' array e la rimuovo per evitare immagini raddoppiate 
       $main_image_urls = [ 
           wp_get_attachment_url($post->ID), 
           wp_get_attachment_image_url($post->ID, 'large') 
       ]; 
       
       // Rimuovi gli URL dell'immagine principale dall'array $image_paths 
       foreach ($main_image_urls as $main_image_url) { 
           if (($key = array_search($main_image_url, $image_paths)) !== false) { 
               unset($image_paths[$key]); 
               // Stampa un messaggio di debug 
               // echo '<script>console.log("Removed image URL: ' . $main_image_url . '");</script>'; 
           } 
       } 
       
       $image_paths = array_values($image_paths); // Reindex array 
 
     $prj_items_alignment = get_post_meta($parent_post_id, 'prj_items_alignment', true); 
     //echo count($prj_items_alignment); 
     //must be valid also for portfolio portfolio_items_alignment 
     if (!empty($prj_items_alignment) && is_array($prj_items_alignment)) { 
         // Assuming $prj_items_alignment is an associative array where keys are post IDs 
         // Convert array keys to a simple array of post IDs 
         $post_ids = array_keys($prj_items_alignment); 
         // Find current post's index 
         $current_index = array_search($post->ID, $post_ids); 
 
         // Previous and Next post URLs 
         $prev_post_id = $post_ids[$current_index - 1] ?? null; 
         $next_post_id = $post_ids[$current_index + 1] ?? null; 
         $prev_post_url = $prev_post_id ? get_permalink($prev_post_id) : null; 
         $next_post_url = $next_post_id ? get_permalink($next_post_id) : null; 
     } 
 
     if ( have_posts() ) : while ( have_posts() ) : the_post(); 
     $attachment_id = get_the_ID(); 
     $attachment_meta = get_post_meta($attachment_id); 
     //deve andare a capo 
     $description = nl2br(get_the_content(null, false, $attachment_id)); 
     $categories = get_the_terms( $attachment_id, 'category' ); 
     $tags = get_the_terms( $attachment_id, 'post_tag' ); 
     //var_dump($attachment_meta);  
     ?> 
         <div class="left-column"> 
             <div class="left-column-top"> 
             <h1><?php the_title(); ?></h1> 
             <div class="foto-description"> 
             <?php 
               // Check if description exists 
                 if (!empty($description)) { 
                     echo $description; 
                 } else { 
                     // Get the attachment excerpt 
                     $excerpt = get_the_excerpt($attachment_id); 
                     // Check if excerpt exists 
                     if (!empty($excerpt)) { 
                         echo $excerpt; 
                     } else { 
                         // Output an empty string if neither description nor excerpt exists 
                         //echo 'no description'; 
                         echo '  '; 
                     } 
                 } 
             ?></div> 
             <div class="categories-tags"> 
             <?php echo '<ul>'; 
             if ( !empty($categories) && !is_wp_error( $categories ) ) { 
                 foreach ( $categories as $category ) { 
                   $category_link = get_category_link( $category->term_id ); 
                   //echo '<li href="' . esc_url( $category_link ) . '">' . esc_html( $category->name ) . '</li>'; 
                 } 
             }  else { 
                   //echo '<li href="#">Cat</li>';//remove in prd 
                 }; 
             if ( !empty($tags) && !is_wp_error( $tags ) ) { 
                 foreach ( $tags as $tag ) { 
                   $tag_link = get_tag_link( $tag->term_id ); 
                   //echo '<li href="' . esc_url( $tag_link ) . '">' . esc_html( $tag->name ) . '</li>'; 
                 } 
             } else { 
                   //echo '<li href="#">Tag</li>';//remove in prd 
                 }; 
             echo '</ul>'; 
             echo '</div>'; 
             ?> 
             </div> 
             <div class="left-column-bottom thisTMP"> 
             <nav class="foto-navigation"> 
             <div class="nav-prev-next"> 
             <?php 
             if (isset($prev_post_url)) { 
               echo '<div class="nav-prev"><a href="' . esc_url($prev_post_url) . '">Previous</a></div>'; 
             } 
             if (isset($next_post_url)) { 
               echo '<div class="nav-ne"><a href="' . esc_url($next_post_url) . '">Next</a></div>'; 
             } 
             ?> 
             </div> 
             <?php 
             if ($parent_post_id != 0) { // Check if there is a parent post 
             $parent_post_url = get_permalink($parent_post_id); // Get the permalink of the parent post 
             echo '<a class="back" href="' . esc_url($parent_post_url) . '" >Back</a>'; // Create the link to the parent post 
             } else { 
                 echo '<a class="back" href="javascript:history.back()" >Back</a>'; // Fallback to javascript back if no parent post 
             } 
             ?> 
             </nav> 
             </div> 
             
             <script> 
               function emToPixels(em, element) { 
                   return em * parseFloat(getComputedStyle(element).fontSize); 
               } 
               // Adjust the height of 'fotoContent' 
               document.addEventListener("DOMContentLoaded", function() { 
                   var fotoContent = document.querySelector('.foto-content'); 
                   if (fotoContent) { 
                       var emInPixels = emToPixels(2, document.body); // Convert 1em to pixels 
                       fotoContent.style.height = (window.innerHeight - emInPixels) + 'px'; 
                   } 
               }); 
               
               // Adjust max-height of images 
               function adjustImageMaxHeight() { 
                   var images = document.querySelectorAll('.image-wrapper img'); 
                   var emInPixels = emToPixels(2, document.body); // Convert 1em to pixels 
                   var maxHeight = window.innerHeight - emInPixels; // Subtract 1em (in pixels) from the window height 
               
                   images.forEach(function(img) { 
                       img.style.maxHeight = maxHeight + 'px'; 
                   }); 
               } 
               
               // Adjust max-height on window resize 
               window.addEventListener('resize', adjustImageMaxHeight); 
               
               // Set initial max-height when the DOM content is fully loaded 
               document.addEventListener("DOMContentLoaded", adjustImageMaxHeight); 
             </script> 
         </div> 
         <div class="right-column"> 
           <?php 
           // Fetch attachment details 
           $attachment_id = get_the_ID(); 
           $alignment = get_post_meta($attachment_id, 'image_allineamento', true); 
           $image_url = wp_get_attachment_url($attachment_id); // URL dell'immagine a dimensione piena 
       
           // Set default alignment class 'destra' if alignment is empty or 'square' 
           if (empty($alignment) || $alignment === 'square') { 
               $alignment = 'destra'; 
           } 
           ?> 
           <a class="related-foto-item" href="<?php echo esc_url($image_url); ?>" style="color:black;" data-lightbox="gallery"> 
           <div class="fotoContainer <?php echo esc_attr($alignment); ?>"> 
               <div class="image-wrapper"> 
                   <?php echo wp_get_attachment_image($attachment_id, 'large'); ?> 
               </div> 
           </div> 
           </a> 
       </div> 
     <div id="hidden-images" style="display: none;"> 
     <?php 
     foreach ($image_paths as $path) { 
         echo '<a href="' . esc_url($path) . '" data-lightbox="gallery"><img src="' . esc_url($path) . '" alt="Gallery Image"></a>'; 
     } 
     ?> 
     </div> 
     <?php endwhile; endif; ?> 
 </div> 
 
 <script> 
 jQuery(document).ready(function($) { 
     //console.log(imagePaths) 
     var gallery = jQuery('a[data-lightbox="gallery"]').simpleLightbox({ 
         className: 'simple-lightbox', // Adds a custom class to the lightbox wrapper 
         widthRatio: 1, // Sets the maximum width of the image to 80% of the screen width 
         heightRatio: 1, // Sets the maximum height of the image to 90% of the screen height 
         scaleImageToRatio: true, // Prevents scaling the image larger than its original size, 
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
     // Aggiungi evento per tasti freccia 
     document.addEventListener('keydown', function (event) { 
         // Verifica se la lightbox è attiva controllando la presenza dell'elemento con le classi "sl-wrapper simple-lightbox" 
         if (document.querySelector('.sl-wrapper.simple-lightbox')) { 
             // La lightbox è visibile, impedisci l'azione 
             return; 
         } 
 
         // Freccia sinistra 
         if (event.key === 'ArrowLeft') { 
             const prevLink = document.querySelector('.nav-prev a'); 
             if (prevLink) { 
                 window.location.href = prevLink.href; 
             } 
         } 
 
         // Freccia destra 
         if (event.key === 'ArrowRight') { 
             const nextLink = document.querySelector('.nav-ne a'); 
             if (nextLink) { 
                 window.location.href = nextLink.href; 
             } 
         } 
     }); 
   
     let touchStartX = 0; 
     let touchEndX = 0;
     let touchStartY = 0;
     let touchEndY = 0;

     // Funzione per controllare se sei su un dispositivo mobile (viewport < 992px) 
     function isMobileViewport() { 
         return window.innerWidth < 992; 
     } 

     // Aggiungi eventi touch per rilevare swipe 
     document.addEventListener('touchstart', function (event) { 
         if (!isMobileViewport()) return; // Gestisce solo i dispositivi mobili 
         touchStartX = event.changedTouches[0].screenX; // Posizione iniziale del tocco 
         touchStartY = event.changedTouches[0].screenY;
     }); 

     document.addEventListener('touchend', function (event) { 
         if (!isMobileViewport()) return; // Gestisce solo i dispositivi mobili 
         touchEndX = event.changedTouches[0].screenX; // Posizione finale del tocco 
         touchEndY = event.changedTouches[0].screenY;
         handleSwipe(); 
     }); 

     // Funzione per gestire lo swipe 
     function handleSwipe() { 
         // Verifica se la lightbox è attiva 
         const plusIcon = document.querySelector('.absolute-plus-icon'); 
         if (document.querySelector('.sl-wrapper.simple-lightbox')) { 
             plusIcon 
             return; // La lightbox è visibile, ignora lo swipe 
         } 

         // Calcola la direzione dello swipe 
         const swipeDistanceX = touchStartX - touchEndX; 
         const swipeDistanceY = touchStartY - touchEndY;

         // Se lo scorrimento verticale è maggiore di quello orizzontale, è uno scroll, non uno swipe
         if (Math.abs(swipeDistanceY) > Math.abs(swipeDistanceX)) {
             return;
         }

         if (swipeDistanceX < -40) { 
             // Swipe destra: naviga al prossimo link 
             const nextLink = document.querySelector('.nav-ne a'); 
             if (nextLink) { 
                 window.location.href = nextLink.href; 
             } else { 
                 console.log("Nessun link 'next' disponibile."); 
             } 
         } else if (swipeDistanceX > 40) { 
             // Swipe sinistra: naviga al link precedente 
             const prevLink = document.querySelector('.nav-prev a'); 
             if (prevLink) { 
                 window.location.href = prevLink.href; 
             } else { 
                 console.log("Nessun link 'previous' disponibile."); 
             } 
         } 
     } 
     
     const image = $("#code_block-6-243 > div > div.right-column > a > div > div > img"); 
       if (image.length) { 
       // Ottieni il bounding rect dell'immagine 
       const rect = image[0].getBoundingClientRect(); 
   
       // Calcola la posizione dall'alto della pagina 
       const scrollTop = $(window).scrollTop(); 
       const imageBottom = rect.bottom + scrollTop; 
       const imageRight = rect.right; 
   
       // Crea l'elemento per l'icona 
       const plusIcon = $('<div class="absolute-plus-icon">+</div>'); 
   
       // Posiziona l'icona in basso a destra con un margine di 10px 
       plusIcon.css({ 
           position: "absolute", 
           top: `${imageBottom - 28}px`, // 10px sopra il bordo inferiore 
           left: `${imageRight - 20}px`, // 10px a sinistra del bordo destro 
           zIndex: 1, // Assicurati che sia sopra tutto 
       }); 
   
       // Aggiungi l'icona al body 
       $("body").append(plusIcon); 
   
       //console.log(`Icon positioned at bottom: ${imageBottom - 20}px, right: ${imageRight - 20}px`); 
   } else { 
       console.warn("Image not found"); 
   } 
 }); 
 
 document.addEventListener("DOMContentLoaded", () => { 
     const observer = new MutationObserver(() => { 
         const navMenuOpen = document.querySelector("body .oxy-nav-menu.oxy-nav-menu-open"); 
         const plusIcon = document.querySelector("body > div.absolute-plus-icon"); 
 
         if (plusIcon) { 
             if (navMenuOpen) { 
                 plusIcon.style.opacity = 0; // Nascondi icona se lightbox aperto 
             } else { 
                 plusIcon.style.opacity = 1; // Mostra icona se lightbox chiuso 
             } 
         } 
     }); 
 
     // Osserva modifiche nel body per rilevare l'apertura/chiusura del menu 
     observer.observe(document.body, { childList: true, subtree: true }); 
 }); 
 document.addEventListener("DOMContentLoaded", () => { 
     const checkAndToggleSection = () => { 
         const lightbox = document.querySelector(".sl-wrapper.simple-lightbox"); 
         const $sezioneIncriminata = jQuery("#section-3-106"); 
 
         if (lightbox && $sezioneIncriminata.length) { 
             $sezioneIncriminata.css({ 
                 position: "absolute", 
                 bottom: "-100", 
                 zIndex: "-1" // Opzionale, se necessario 
             }).hide(); // Nasconde la sezione 
             console.log("#section-3-106 nascosta e posizionata assolutamente perché la lightbox è presente."); 
         } else if (!lightbox && $sezioneIncriminata.is(":hidden")) { 
             setTimeout(() => { 
                 $sezioneIncriminata.css({ 
                     position: "", 
                     bottom: "", 
                     zIndex: "" // Ripristina lo stile originale, opzionale 
                 }).fadeIn(200); // Mostra nuovamente la sezione 
                 console.log("#section-3-106 mostrata e posizione ripristinata dopo il timeout."); 
             }, 200); // Timeout di 500ms 
         } 
     }; 
 
     const observer = new MutationObserver(() => { 
         checkAndToggleSection(); 
     }); 
 
     observer.observe(document.body, { childList: true, subtree: true }); 
 }); 
 </script>
    <?php

    return ob_get_clean();
}



