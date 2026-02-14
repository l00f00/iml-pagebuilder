<?php
/**
 * Shortcode: [iml_project_single]
 * Handles the single project display with switchable layouts (1 Column / 3 Columns).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('iml_project_single', 'iml_render_project_single');

function iml_render_project_single($atts) {
    ob_start();

    // --- SETUP Navigation ---
    /*
    $prev_post = get_adjacent_post(false, '', true);
    $next_post = get_adjacent_post(false, '', false);
    $prev_post_url = $prev_post ? get_permalink($prev_post->ID) : null;
    $next_post_url = $next_post ? get_permalink($next_post->ID) : null;
    */

    // --- COMMON DATA RETRIEVAL ---
    $post_id = get_the_ID(); 
    $description = rwmb_meta( 'descrizione_progetto', '', $post_id ); 
    $year = rwmb_meta( 'anno', '', $post_id ); 
    $items = get_post_meta($post_id, 'prj_items', true); 
    
    $thumbnail_id = get_post_thumbnail_id($post_id); 
    $has_single_page = get_post_meta($thumbnail_id, 'has_single_page', true); 
    $thumbnail_url = $has_single_page ? get_permalink($thumbnail_id) : get_the_post_thumbnail_url($post_id, 'full'); 
    $featured_image_url = get_the_post_thumbnail_url($post_id, 'full'); 

    // --- DETERMINE LAYOUT ---
    $abilita3colonne = rwmb_meta('abilita3colonne', '', $post_id); 
    $layout_3_col = !empty($abilita3colonne) && $abilita3colonne == 1;

    if ($layout_3_col) {
         // Load CSS for 3 Columns (User provided snippet)
         wp_enqueue_style('iml-project-frontend-style-3col', IML_PLUGIN_URL . 'includes/post-types/project/frontend-style-3col.css', array(), '1.0');
        // ==========================================
        // LAYOUT 3 COLONNE (USER PROVIDED CODE)
        // ==========================================
        
        // RE-FETCH VARIABLES AS PER USER SNIPPET TO ENSURE EXACT MATCH
        $items = get_post_meta($post_id, 'prj_items', true); 
        $alignment = get_post_meta($post_id, 'prj_item_alignment', true) ?: 'square'; 
        $array = get_post_meta($post_id, 'prj_items_alignment', true); 
        
        $thumbnail_id = get_post_thumbnail_id($post_id); 
        $has_single_page = get_post_meta($thumbnail_id, 'has_single_page', true); 
        $thumbnail_url = $has_single_page ? get_permalink($thumbnail_id) : get_the_post_thumbnail_url($post_id, 'full'); 
        $lightbox_attr = $has_single_page ? '' : 'data-lightbox="gallery"'; 
        $featured_image_url = get_the_post_thumbnail_url($post_id, 'full'); 
        ?> 
        <div class="progetto-content layout-3-col"> 
            <div class="left-column-progetto"> 
            <a href="<?php echo esc_url($featured_image_url); ?>" style="color:black;" data-lightbox="gallery"> 
                <?php echo get_the_post_thumbnail($post_id, 'full'); ?></a> 
            <div class="left-column-bottom"> 
                    <nav class="foto-navigation"> 
                            <?php 
                            if (isset($prev_post_url)) { 
                            echo '<div class="nav-previous"><a href="' . esc_url($prev_post_url) . '">Previous</a></div>'; 
                            } 
                            if (isset($next_post_url)) { 
                            echo '<div class="nav-next"><a href="' . esc_url($next_post_url) . '">Next</a></div>'; 
                            } 
                            ?> 
                            <?php 
                    $parent_post_id = wp_get_post_parent_id($post_id); 
                    if ($parent_post_id != 0) { // Check if there is a parent post 
                    $parent_post_url = get_permalink($parent_post_id); // Get the permalink of the parent post 
                    echo '<a class="back" href="' . esc_url($parent_post_url) . '">Back</a>'; // Create the link to the parent post 
                    } else { 
                        echo '<a class="back" href="javascript:history.back()">Back</a>'; // Fallback to javascript back if no parent post 
                    } 
                    ?> 
                        </nav> 
            </div> 
            </div> 
            <div class="right-column-progetto"> 
            <div class="right-column-progetto-top"> 
                <h1 class="progetto-title"><?php echo get_the_title( $post_id ); ?></h1> 
                <div class="progetto-year"><?php echo esc_html( $year ); ?></div> 
                <div class="progetto-description"><?php echo do_shortcode( wpautop( $description ) );  //print_r($array);?> </div> 
            </div> 
        </div> 
        </div> 
        <div class="related-fotos gallery"> 
        <?php 
        $hiddenImages = []; 
        if (is_array($items)) { 
            foreach ($items as $foto_id) { 
                // Get the alignment for this item 
                $alignment = get_post_meta($foto_id, 'prj_item_alignment', true); 
                // Check if the item should link to a single page 
                $single_page_true = get_post_meta($foto_id, 'has_single_page', true); 
                // Get the full-size image URL and the large thumbnail URL 
                $image_url = wp_get_attachment_url($foto_id); 
                $thumbnail = wp_get_attachment_image_url($foto_id, 'large'); 
                // Determine the link URL and whether to use lightbox 
                //$link_url = $single_page_true ? get_permalink($foto_id) : esc_url($image_url); 
                $link_url = $single_page_true ? get_attachment_link($foto_id) : esc_url($image_url); 
                // if single page true add image to hiddenImages so we can show it in the lightbox anyway 
                if ($single_page_true) { 
                    $hiddenImages[] = $image_url; 
                } 
            
                $lightbox_attr = $single_page_true ? 'data-single="single-page-true"' : 'data-lightbox="gallery"'; 
                // Conditionally add a border style for items with a single page 
                //$border_style = $single_page_true ? 'border: 1px solid red;' : ''; 
                ?> 
                <a class="related-foto-item" href="<?php echo $link_url; ?>" style="color:black;" <?php echo $lightbox_attr;?>> 
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
            <div id="hidden-images" style="display: none;"> 
            <?php 
            foreach ($hiddenImages as $path) { 
                echo '<a href="' . esc_url($path) . '" data-lightbox="gallery"><img src="' . esc_url($path) . '" alt="Gallery Image"></a>'; 
            } 
            ?> 
            </div> 
            </div> 
        
        <script> 
        jQuery(document).ready(function($) { 
            // Utilizza SimpleLightbox con jQuery su tutti gli elementi che hanno data-lightbox="gallery" 
            //jQuery('a[data-lightbox="gallery"]').simpleLightbox(); 
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
            
        }); 
        console.log('PROGETTO CARICATO 3 colonne'); 
        </script>
        <?php
    } else {
        // Load CSS for 1 Column (Standard/Original)
        wp_enqueue_style('iml-project-frontend-style-1col', IML_PLUGIN_URL . 'includes/post-types/project/frontend-style-1col.css', array(), '1.0');
        // ==========================================
        // LAYOUT 1 COLONNA
        // ==========================================
        ?>
        <div class="progetto-content"> 
            <div class="left-column-progetto"> 
                <a href="<?php echo esc_url($featured_image_url); ?>" style="color:black;" data-lightbox="gallery"> 
                    <?php echo get_the_post_thumbnail($post_id, 'full'); ?>
                </a> 
                <div class="left-column-bottom"> 
                    <nav class="foto-navigation"> 
                        <?php 
                        if (isset($prev_post_url)) { 
                            echo '<div class="nav-previous"><a href="' . esc_url($prev_post_url) . '">Previous</a></div>'; 
                        } 
                        if (isset($next_post_url)) { 
                            echo '<div class="nav-next"><a href="' . esc_url($next_post_url) . '">Next</a></div>'; 
                        } 
                        ?> 
                        <?php 
                        $parent_post_id = wp_get_post_parent_id($post_id); 
                        if ($parent_post_id != 0) { 
                            $parent_post_url = get_permalink($parent_post_id); 
                            echo '<a class="back" href="' . esc_url($parent_post_url) . '">Back</a>'; 
                        } else { 
                            echo '<a class="back" href="javascript:history.back()">Back</a>'; 
                        } 
                        ?> 
                    </nav> 
                </div> 
            </div> 
            
            <div class="right-column-progetto"> 
                <div class="right-column-progetto-top"> 
                    <h1 class="progetto-title"><?php echo get_the_title( $post_id ); ?></h1> 
                    <div class="progetto-year"><?php echo esc_html( $year ); ?></div> 
                    <div class="progetto-description"><?php echo do_shortcode( wpautop( $description ) ); ?> </div> 
                </div> 
                
                <!-- Gallery is INSIDE right-column-progetto for 1 col -->
                <div class="related-fotos gallery"> 
                    <?php 
                    $hiddenImages = []; 
                    if (is_array($items)) { 
                        foreach ($items as $foto_id) { 
                            $alignment = get_post_meta($foto_id, 'prj_item_alignment', true); 
                            $single_page_true = get_post_meta($foto_id, 'has_single_page', true); 
                            $image_url = wp_get_attachment_url($foto_id); 
                            $thumbnail = wp_get_attachment_image_url($foto_id, 'large'); 
                            $link_url = $single_page_true ? get_attachment_link($foto_id) : esc_url($image_url); 
                            
                            // 1 Col specific lightbox logic
                            $lightbox_attr = $single_page_true ? '' : 'data-lightbox="gallery"';
                    
                            if ($single_page_true) { 
                                $hiddenImages[] = $image_url; 
                            } 
                            ?> 
                            <a class="related-foto-item" href="<?php echo $link_url; ?>" style="color:black;" <?php echo $lightbox_attr; ?>> 
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
                
                <div id="hidden-images" style="display: none;"> 
                    <?php 
                    foreach ($hiddenImages as $path) { 
                        echo '<a href="' . esc_url($path) . '" data-lightbox="gallery"><img src="' . esc_url($path) . '" alt="Gallery Image"></a>'; 
                    } 
                    ?> 
                </div> 
            </div> 
        </div> 
        
        <script> 
        jQuery(document).ready(function($) { 
            // Utilizza SimpleLightbox con jQuery su tutti gli elementi che hanno data-lightbox="gallery" 
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
           
            console.log('PROGETTO CARICATO 1 colonna');
        });
        </script>
        <?php
    }
    
    // --- SPACING & STYLES LOGIC (Keep existing) ---
    // Conditional check: 
    $space = rwmb_meta( 'abilitaSpazio' ); 
    $spaceVert = rwmb_meta( 'abilitaSpazioVert' ); 
    $featuredImageSpacer = rwmb_meta( 'featuredImageSpacer' ); 
    if (!isset($featuredImageSpacer)){ 
      $featuredImageSpacer = '2em'; 
    } 
    // Check if both conditions are met: 
    if ( $space && $spaceVert ) { 
        echo '<style>.related-fotos {row-gap: 2em; column-gap: 2em;}</style>'; 
        echo '<style>.progetto-content {padding-bottom: '. $featuredImageSpacer .';}</style>'; 
    } elseif ( $space ) { // Only $space is set 
        echo '<style>.related-fotos {row-gap: 2em;}</style>'; 
        echo '<style>.progetto-content {padding-bottom: '. $featuredImageSpacer .';}</style>'; 
    } elseif ( $spaceVert ) { // Only $spaceVert is set 
        echo '<style>.related-fotos {column-gap: 2em;}</style>'; 
        echo '<style>.progetto-content {padding-bottom: '. $featuredImageSpacer .';}</style>'; 
    } elseif ( $featuredImageSpacer ) { 
        echo '<style>.progetto-content {padding-bottom: '. $featuredImageSpacer .';}</style>'; 
    } 
    ?>
    <style>
    .gallery > .related-foto-item:hover{ 
        /*mix-blend-mode: exclusion; /* Applica il metodo di fusione */ 
    } 
    
    .sl-wrapper .simple-lightbox, .sl-wrapper .simple-lightbox > *{ 
      /*z-index: 30000000000!important;*/ 
      pointer-events: all; 
    }
    </style>
    <?php

    return ob_get_clean();
}
