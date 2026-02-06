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

// Shortcode: [iml_serie_single]
add_shortcode('iml_serie_single', 'iml_render_serie_single');

function iml_render_serie_single($atts) {
    ob_start();

    // Get the current post ID 
    $post_id = get_the_ID(); 
    // Retrieve custom fields using the post ID 
    $description = rwmb_meta( 'descrizione_progetto', '', $post_id ); 
    $year = rwmb_meta( 'anno', '', $post_id ); 
    //$foto_posts = rwmb_meta( 'foto_in_progetto', '', $post_id ); 
    $items = get_post_meta($post_id, 'prj_items', true); 
    //$alignment = get_post_meta($post_id, 'prj_item_alignment', true) ?: 'square'; 

    ?> 
    <div class="progetto-content"> 
        <div class="left-column-progetto"> 
            <div class="left-column-top"> 
                <a class="back-button" href="javascript:history.back()">Back</a>
            </div> 
            <h1 class="progetto-title"><?php echo get_the_title( $post_id ); ?></h1> 
            <div class="progetto-year"><?php echo esc_html( $year ); ?></div> 
            <div class="progetto-description"><?php echo do_shortcode( wpautop( $description ) ); ?></div> 
        </div> 
        <div class="right-column-progetto"> 
            <div class="right-column-progetto-top"></div> 
            <div class="related-fotos"> 
        <?php 
            if (is_array($items)) { 
                foreach ($items as $foto_id) { 
                    // Get the alignment for this item 
                    $alignment = get_post_meta($foto_id, 'prj_item_alignment', true); 
                    $single_page_true = get_post_meta($foto_id, 'has_single_page', true); 
                    $image_url = wp_get_attachment_url($foto_id); // URL dell'immagine a dimensione piena 
                    $thumbnail = wp_get_attachment_image_url($foto_id, 'large'); // O usa una dimensione specifica 
                    ?> 
                    <a class="related-foto-item" href="<?php echo esc_url($image_url); ?>" style="color:black;" data-lightbox="gallery"> 
                        <div class="fotoContainer <?php echo esc_attr($alignment); ?>"> 
                            <div class="image-wrapper"> 
                                <img src="<?php echo esc_url($thumbnail); ?>" alt=""> 
                            </div> 
                        </div> 
                    </a> 
                    <?php 
                } 
            } 
            ?> 
            </div> 
        </div> 
    </div> 
    <script> 
    jQuery(document).ready(function($) { 
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
            closeText: '<div class="divclose">X</div>', 
            navText: ['<svg id="Layer_1" data-name="Layer 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080"><path d="M230.56,603.18l304.42,304.42-80.41,78.98L7.99,540,454.56,93.43l80.41,80.41L230.56,476.82h841.45v126.36H230.56Z"/></svg>','<svg id="Layer_2" data-name="Layer 2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1080 1080">  <path d="M849.44,476.82l-304.42-304.42,80.41-78.98,446.57,446.57-446.57,446.57-80.41-80.41,304.42-302.98H7.99v-126.36h841.45Z"/></svg>'], 
            spinner: false, 
            overlay: false, 
            docClose: false, 
        }); 
    }); 
    </script>
    <?php

    return ob_get_clean();
}

