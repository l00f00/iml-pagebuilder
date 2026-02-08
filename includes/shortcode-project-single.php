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

    // --- SETUP & DATA RETRIEVAL ---
    $post_id = get_the_ID(); 
    $description = rwmb_meta( 'descrizione_progetto', '', $post_id ); 
    $year = rwmb_meta( 'anno', '', $post_id ); 
    $items = get_post_meta($post_id, 'prj_items', true); 
    
    // Retrieve the post thumbnail
    $thumbnail_id = get_post_thumbnail_id($post_id); 
    $has_single_page = get_post_meta($thumbnail_id, 'has_single_page', true); 
    $thumbnail_url = $has_single_page ? get_permalink($thumbnail_id) : wp_get_attachment_image_url($thumbnail_id, 'large'); 
    $lightbox_attr = $has_single_page ? '' : 'data-lightbox="gallery"'; 
    $featured_image_url = wp_get_attachment_image_url($thumbnail_id, 'large'); 
    
    // Conditional check for space and layout
    $space = rwmb_meta( 'abilitaSpazio' ); 
    $spaceVert = rwmb_meta( 'abilitaSpazioVert' ); 
    $featuredImageSpacer = rwmb_meta( 'featuredImageSpacer' ) ?: '2em'; 
    
    // Determine Layout: 3 Columns if 'abilita3colonne' is set to 1 (true)
    $abilita3colonne = rwmb_meta('abilita3colonne', '', $post_id); 
    $layout_3_col = !empty($abilita3colonne) && $abilita3colonne == 1;

    // Navigation URLs
    $prev_post = get_adjacent_post(false, '', true);
    $next_post = get_adjacent_post(false, '', false);
    $prev_post_url = $prev_post ? get_permalink($prev_post->ID) : null;
    $next_post_url = $next_post ? get_permalink($next_post->ID) : null;
    
    // --- START OUTPUT ---
    ?>

    <?php 
    // ==========================================================================================
    // BLOCK 1: 1Colonna (Standard Layout)
    // ==========================================================================================
    if (!$layout_3_col) : 
    ?>
    <div class="progetto-content"> 
        <div class="left-column-progetto"> 
          <a href="<?php echo esc_url($featured_image_url); ?>" style="color:black;" data-lightbox="gallery"> 
            <?php echo get_the_post_thumbnail($post_id, 'large'); ?></a> 
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
            $image_url = wp_get_attachment_image_url($foto_id, 'large'); // Changed from full URL to large size
            $thumbnail = wp_get_attachment_image_url($foto_id, 'large'); 
            // Determine the link URL and whether to use lightbox 
            //$link_url = $single_page_true ? get_permalink($foto_id) : esc_url($image_url); 
            $link_url = $single_page_true ? get_attachment_link($foto_id) : esc_url($image_url); 
            $lightbox_attr = $single_page_true ? '' : 'data-lightbox="gallery"'; 
            // Conditionally add a border style for items with a single page 
            //$border_style = $single_page_true ? 'border: 1px solid red;' : ''; 
    
            // if single page true add image to hiddenImages so we can show it in the lightbox anyway 
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

        // Force hide lightbox wrapper on close
        gallery.on('close.simplelightbox', function () {
            setTimeout(function() {
                jQuery('.sl-wrapper').fadeOut(200, function(){
                    jQuery(this).hide();
                });
            }, 100); // Small delay to allow library's own close animation to start
        });

        console.log('Progetto caricato: <?php echo $layout_3_col ? "3 colonne" : "1 colonna"; ?>');

        // Read More Functionality
        var $desc = jQuery('.progetto-description');
        var maxHeight = jQuery(window).height() * 0.6; // 60% of viewport height

        if ($desc.length && $desc.height() > maxHeight) {
            $desc.addClass('truncated');
            jQuery('<div class="read-more-btn">Read More</div>').insertAfter($desc);
            
            jQuery('.read-more-btn').on('click', function() {
                if ($desc.hasClass('truncated')) {
                    $desc.removeClass('truncated');
                    jQuery(this).text('Read Less');
                } else {
                    $desc.addClass('truncated');
                    jQuery(this).text('Read More');
                }
            });
        }
    });
    </script> <style> 
    .progetto-content { 
      display: flex; 
      flex-wrap: wrap; 
      min-height: 99vh; 
      height: 100%; /* Minimum height to fill the screen */ 
    } 
    
    .left-column-progetto { 
      width: 65%; 
      max-height:100vh;
      position: relative; /* Anchor for fixed image */
    } 
    .left-column-progetto a img { 
          position: absolute; /* Changed from fixed to absolute so it scrolls */
          top: 0; 
          left: 0; 
          width: 100%; /* Fill container width */
          height: 100vh; /* Full viewport height initial size */
          object-fit: cover; /* Cover entire area */
          padding-right: 8px; /* Padding on the right */
          box-sizing: border-box; /* Include padding in width */
     } 
     .left-column-progetto a { 
          display: block; 
     } 
    .left-column-progetto .left-column-top > *{ 
      padding: 28px 0 28px 0; 
    } 
    .left-column-bottom { 
        height: 15%; 
        display: flex; 
        flex-direction: row; 
        align-content: flex-end; 
        justify-content: flex-start; 
        align-items: flex-end; 
        position: fixed; 
        bottom: 6px; 
        /*mix-blend-mode:exclusion;*/ 
    } 
    
    .foto-navigation > * { 
        /*mix-blend-mode:exclusion;*/ 
    } 
    .foto-navigation { 
     padding-left: 5px; 
    } 
    
    .left-column-progetto .left-column-top{ 
      padding: 28px 0 28px 0; 
    } 
    
    .right-column-progetto { 
      width: 35%; 
      padding-top:65px;/*era 50 px*/ 
      background-color: white; /* Added to prevent image overlap */
      position: relative; 
      z-index: 10;
    } 
    .progetto-description {
        position: relative;
        transition: max-height 0.5s ease;
    }
    .progetto-description.truncated {
        max-height: 60vh; /* Adjust this value */
        overflow: hidden;
    }
    .progetto-description.truncated::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50px;
        background: linear-gradient(transparent, white);
    }
    .read-more-btn {
        display: block; /* Hidden by default, shown by JS if needed */
        cursor: pointer;
        color: black; /* Or styling */
        margin-top: 10px;
        text-decoration: underline;
    } 
    .right-column-progetto-top{ 
      padding-bottom:160px; 
    } 
    
    .related-fotos{ 
      display:block; 
    } 
    
    .right-column-progetto-top > .related-foto-item{ 
      max-width:100%; 
    } 
    
    .fotoContainer { 
      max-width:100%; 
    } 
    
    /* Style for the image wrapper */ 
    .image-wrapper { 
      display: flex; 
      max-width:inherit; 
      aspect-ratio: 1 / 1; 
      max-width:100%; 
      width: inherit; 
    } 
    .image-wrapper img{ 
      display: flex; 
      max-width: 100%; 
      max-height: 100%; 
      object-fit: contain; 
    } 
    
    /* Now, we align the image within the image wrapper */ 
    .fotoContainer.destra .image-wrapper { 
      justify-content: flex-end; 
      width: -webkit-fill-available; 
    } 
    .fotoContainer.destra .image-wrapper img{ 
        height: 100%; 
        width: auto; 
    } 
    
    .fotoContainer.sinistra .image-wrapper { 
      justify-content: flex-start; 
    } 
    
    .fotoContainer.sinistra .image-wrapper img { 
        height: 100%; 
        width: auto; 
    } 
    
    .fotoContainer.alto .image-wrapper { 
      align-items: flex-start; 
    } 
    
    .fotoContainer.alto .image-wrapper img{ 
        width: 100%; 
        height: auto; 
      object-fit:contain; 
    } 
    
    .fotoContainer.basso .image-wrapper { 
      align-items: flex-end; /* Align image to the bottom */ 
    } 
    .fotoContainer.basso .image-wrapper img { 
      width: 100%; 
      height: auto; 
    } 
    .back{ 
      color: white; 
      background-color: black; 
    } 
    .back:hover{ 
      color: black; 
      background-color: white; 
    } 
    /* Mobile styles */ 
    @media (max-width: 1120px) { 
        .left-column-progetto, .right-column-progetto { 
            width: 100vw; 
        } 
        .left-column-progetto > img { 
            max-width: 100%; 
            position:relative; 
        } 
        .left-column-progetto> a > img { 
            max-width: 100vw; 
            width:100%; 
            position:relative; 
        } 
    
        .right-column-progetto .related-photo-item { 
            width: 100%; 
        } 
    .back{ 
      color: white; 
      background-color: black; 
    } 
    .back:hover{ 
      color: black; 
      background-color: white; 
    } 
    } 
    
    </style>
    <?php endif; ?>

    <?php 
    // ==========================================================================================
    // BLOCK 2: 3Colonna (Grid Layout)
    // ==========================================================================================
    if ($layout_3_col) : 
    ?>
    <div class="progetto-content"> 
        <div class="left-column-progetto"> 
          <a href="<?php echo esc_url($featured_image_url); ?>" style="color:black;" data-lightbox="gallery"> 
            <?php echo get_the_post_thumbnail($post_id, 'large'); ?></a> 
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
    if (is_array($items)) { 
        foreach ($items as $foto_id) { 
            // Get the alignment for this item 
            $alignment = get_post_meta($foto_id, 'prj_item_alignment', true); 
            // Check if the item should link to a single page 
            $single_page_true = get_post_meta($foto_id, 'has_single_page', true); 
            // Get the full-size image URL and the large thumbnail URL 
            $image_url = wp_get_attachment_image_url($foto_id, 'large'); // Changed from full URL to large size
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
            spinner: true, 
            overlay: false, 
            docClose: false, 
        }); 

        // Force hide lightbox wrapper on close
        gallery.on('close.simplelightbox', function () {
            setTimeout(function() {
                jQuery('.sl-wrapper').fadeOut(200, function(){
                    jQuery(this).hide();
                });
            }, 100); // Small delay to allow library's own close animation to start
        });

        console.log('Progetto caricato: <?php echo $layout_3_col ? "3 colonne" : "1 colonna"; ?>');

        // Read More Functionality
        var $desc = jQuery('.progetto-description');
        var maxHeight = jQuery(window).height() * 0.6; // 60% of viewport height

        if ($desc.length && $desc.height() > maxHeight) {
            $desc.addClass('truncated');
            jQuery('<div class="read-more-btn">Read More</div>').insertAfter($desc);
            
            jQuery('.read-more-btn').on('click', function() {
                if ($desc.hasClass('truncated')) {
                    $desc.removeClass('truncated');
                    jQuery(this).text('Read Less');
                } else {
                    $desc.addClass('truncated');
                    jQuery(this).text('Read More');
                }
            });
        }
    });
    </script> <style> 
    .progetto-content { 
      display: flex; 
      flex-wrap: wrap; 
      min-height: 99vh; 
      height: 100%; /* Minimum height to fill the screen */ 
    } 
    
    .related-fotos { 
        display: grid; /* Using CSS Grid to layout the items */ 
        grid-template-columns: repeat(3, 1fr); /* Creates three columns of equal width */ 
    } 
    
    .left-column-progetto { 
      width: 65%; 
      max-height:100vh;
      position: relative; /* Anchor for fixed image */
    } 
    .left-column-progetto a img { 
          position: absolute; /* Changed from fixed to absolute so it scrolls */
          top: 0; 
          left: 0; 
          width: 100%; /* Fill container width */
          height: 100vh; /* Full viewport height initial size */
          object-fit: cover; /* Cover entire area */
          padding-right: 8px; /* Padding on the right */
          box-sizing: border-box; /* Include padding in width */
     } 
     .left-column-progetto a { 
          display: block; 
     }
    .left-column-progetto .left-column-top > *{ 
      padding: 28px 0 28px 0; 
    } 
    .left-column-bottom { 
        height: 15%; 
        display: flex; 
        flex-direction: row; 
        align-content: flex-end; 
        justify-content: flex-start; 
        align-items: flex-end; 
        position: fixed; 
        bottom: 6px; 
        /*mix-blend-mode:exclusion;*/ 
    } 
    
    .foto-navigation > * { 
        /*mix-blend-mode:exclusion;*/ 
    } 
    .foto-navigation { 
     padding-left: 5px; 
    } 
    
    .left-column-progetto .left-column-top{ 
      padding: 28px 0 28px 0; 
    } 
    
    .right-column-progetto { 
      width: 35%; 
      padding-top:65px;/*era 50px*/ 
      padding-right:6px;/*era 0px messo a 6 per leggibilita*/ 
      background-color: white; /* Added to prevent image overlap */
      position: relative; 
      z-index: 10;
    } 
    .progetto-description {
        position: relative;
        transition: max-height 0.5s ease;
    }
    .progetto-description.truncated {
        max-height: 60vh; /* Adjust this value */
        overflow: hidden;
    }
    .progetto-description.truncated::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 50px;
        background: linear-gradient(transparent, white);
    }
    .read-more-btn {
        display: block; /* Hidden by default, shown by JS if needed */
        cursor: pointer;
        color: black; /* Or styling */
        margin-top: 10px;
        text-decoration: underline;
    } 
    .right-column-progetto-top{ 
      padding-bottom:160px; 
    } 
    
    .right-column-progetto-top > .related-foto-item{ 
      max-width:100%; 
    } 
    
    .fotoContainer { 
      max-width:100%; 
    } 
    
    /* Style for the image wrapper */ 
    .image-wrapper { 
      display: flex; 
      max-width:inherit; 
      aspect-ratio: 1 / 1; 
      max-width:100%; 
      width: inherit; 
    } 
    .image-wrapper img{ 
      display: flex; 
      max-width: 100%; 
      max-height: 100%; 
      object-fit: contain; 
    } 
    
    /* Now, we align the image within the image wrapper */ 
    .fotoContainer.destra .image-wrapper { 
      justify-content: flex-end; 
      width: -webkit-fill-available; 
    } 
    .fotoContainer.destra .image-wrapper img{ 
        height: 100%; 
        width: auto; 
    } 
    
    .fotoContainer.sinistra .image-wrapper { 
      justify-content: flex-start; 
    } 
    
    .fotoContainer.sinistra .image-wrapper img { 
        height: 100%; 
        width: auto; 
    } 
    
    .fotoContainer.alto .image-wrapper { 
      align-items: flex-start; 
    } 
    
    .fotoContainer.alto .image-wrapper img{ 
        width: 100%; 
        height: auto; 
      object-fit:contain; 
    } 
    
    .fotoContainer.basso .image-wrapper { 
      align-items: flex-end; /* Align image to the bottom */ 
    } 
    .fotoContainer.basso .image-wrapper img { 
      width: 100%; 
      height: auto; 
    } 
    .back{ 
      color: white; 
      background-color: black; 
    } 
    .back:hover{ 
      color: black; 
      background-color: white; 
    } 
    /* Mobile styles */ 
    @media (max-width: 1120px) { 
        .left-column-progetto, .right-column-progetto { 
            width: 100vw; 
        } 
        .left-column-progetto > img { 
            max-width: 100%; 
            position:relative; 
        } 
        .left-column-progetto> a > img { 
            max-width: 100vw; 
            width:100%; 
            position:relative; 
        } 
    
        .right-column-progetto .related-photo-item { 
            width: 100%; 
        } 
    .back{ 
      color: white; 
      background-color: black; 
    } 
    .back:hover{ 
      color: black; 
      background-color: white; 
    } 
    } 
    /* Responsive adjustments for smaller screens */ 
    @media (max-width: 768px) { 
        .related-fotos { 
            grid-template-columns: repeat(2, 1fr); /* Switches to two columns on smaller screens */ 
        } 
    } 
    
    @media (max-width: 480px) { 
        .related-fotos { 
            grid-template-columns: 1fr; /* Single column layout on very small screens */ 
        } 
    } 
    
    /*[data-single="single-page-true"] { 
        border: 1px solid red;*/ 
    </style> 
    <?php endif; ?>

    <?php 
    // ==========================================================================================
    // BLOCK 3: Code Block (Scripts & Styles)
    // ==========================================================================================
    ?>
    <?php 
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
    ?><style>.gallery > .related-foto-item:hover{ 
        /*mix-blend-mode: exclusion; /* Applica il metodo di fusione */ 
    } 
    
    /*.sl-wrapper .simple-lightbox, .sl-wrapper .simple-lightbox > *{ 
      z-index: 30000000000!important; 
      pointer-events: all; 
    }*/
    /*
    .sl-wrapper .sl-close {
        z-index: 30000000001!important; 
        pointer-events: auto!important;
        display: block!important;
    }
    .sl-wrapper {
        z-index: 29999999999!important; 
    }
    */
    </style>
    <?php

    return ob_get_clean();
}
