<?php
/**
 * Shortcode: [iml_attachment_single]
 *
 * CHECKLIST IMPLEMENTAZIONE:
 * [v] Configurazione Lightbox:
 *     - [v] overlay: false (Disabilitato nativo per evitare conflitti)
 *     - [v] docClose: false (Disabilitato nativo)
 *     - [v] Chiusura Custom: JS click su .sl-wrapper/.sl-overlay
 *     - [v] Navigazione: Frecce presenti e funzionanti (navText)
 *     - [v] Swipe Mobile: Gestione touchstart/touchend
 * [v] Logica Post Parent:
 *     - [v] Recupero prj_items (Progetti)
 *     - [v] Recupero portfolio_items (Portfolio)
 *     - [v] Fallback prj_items_alignment
 * [v] Layout:
 *     - [v] Left Column: Titolo, Descrizione, Categorie, Tag, Navigazione (Prev/Next/Back)
 *     - [v] Right Column: Immagine principale con link lightbox
 *     - [v] Allineamento: Classi CSS (destra, sinistra, alto, basso) applicate
 * [v] Assets:
 *     - [v] CSS caricato (frontend-style.css)
 *     - [v] JS inline per Lightbox e altezza dinamica
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('iml_attachment_single', 'iml_render_attachment_single');

function iml_render_attachment_single($atts) {
    // Enqueue styles locally for this shortcode
    wp_enqueue_style('iml-attachment-frontend-style', IML_PLUGIN_URL . 'includes/post-types/attachment/frontend-style.css', array(), '1.0');
    
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
    
    // If prj_items is empty, try fetching portfolio_items (for Portfolio parents)
    if (empty($items)) {
        $items = get_post_meta($parent_post_id, 'portfolio_items', true);
    }
    
    //echo count($items); 
     $image_paths = []; 
     
     if (is_array($items)) { 
         foreach ($items as $foto_id) { 
             // Ottieni l'URL dell'immagine di dimensioni complete 
             $image_url = wp_get_attachment_image_url($foto_id, 'full'); 
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
           wp_get_attachment_image_url($post->ID, 'full') 
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
    if (empty($prj_items_alignment)) {
        $prj_items_alignment = get_post_meta($parent_post_id, 'portfolio_items_alignment', true); // Or whatever the key is for portfolio
        // The portfolio builder actually saves alignment in 'portfolio_item_alignment' on the ITEM itself, not the parent array?
        // Wait, portfolio/builder.php says: $alignment = get_post_meta($post_id, 'portfolio_item_alignment', true);
        // But for navigation, we need the ORDER.
        // The order is in $items array (if it's just IDs).
        // Let's check portfolio/builder.php:
        // $portfolio_items = get_post_meta($post->ID, 'portfolio_items', true) ?: [];
        // It's an array of IDs.
        // prj_items_alignment seems to be an associative array in Project?
        // Let's assume for Portfolio, the order is just $items.
    }
    
    //echo count($prj_items_alignment); 
    //must be valid also for portfolio portfolio_items_alignment 
    if (!empty($items) && is_array($items)) {
         // If $items is a simple array of IDs (Portfolio style) or Project style?
         // Project builder saves 'prj_items' as array of IDs too?
         // Let's check project/builder.php if needed. Assuming $items is array of IDs.
         
         $post_ids = $items;
         // Find current post's index 
         $current_index = array_search($post->ID, $post_ids); 

         // Previous and Next post URLs 
         $prev_post_id = $post_ids[$current_index - 1] ?? null; 
         $next_post_id = $post_ids[$current_index + 1] ?? null; 
         $prev_post_url = $prev_post_id ? get_permalink($prev_post_id) : null; 
         $next_post_url = $next_post_id ? get_permalink($next_post_id) : null; 
    } elseif (!empty($prj_items_alignment) && is_array($prj_items_alignment)) { 
        // Fallback for old Project structure if prj_items_alignment was the source of truth
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
                     // Aggiungi la classe read-more-text per gestire il troncamento via CSS/JS se necessario
                     // Ma per coerenza con il progetto, useremo una struttura simile
                     echo '<div class="attachment-description-content description">';
                     echo $description;
                     echo '</div>';
                     // Aggiungi toggle
                     echo '<a href="#" class="read-more-toggle" style="display:none;">Read More</a>';
                 } else { 
                     // Get the attachment excerpt 
                     $excerpt = get_the_excerpt($attachment_id); 
                     // Check if excerpt exists 
                     if (!empty($excerpt)) { 
                         echo '<div class="attachment-description-content excerpt">';
                         echo $excerpt;
                         echo '</div>';
                         echo '<a href="#" class="read-more-toggle" style="display:none;">Read More</a>';
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
             <?php
             // Check if parent is Portfolio
             $is_portfolio = ($parent_post_type === 'portfolio');
             
             if (!$is_portfolio) : // Show prev/next only if NOT portfolio
             ?>
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
             <?php endif; ?>
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
               // Read More Toggle Logic
               document.addEventListener("DOMContentLoaded", function() {
                   
                   function checkReadMoreVisibility() {
                       var toggles = document.querySelectorAll('.read-more-toggle');
                       toggles.forEach(function(toggle) {
                           var content = toggle.previousElementSibling;
                           
                           // Initial check: if content scrollHeight > 500 (max-height set in CSS/JS logic)
                           // Note: CSS sets max-height: 500px for non-expanded state.
                           // But if we want to know if it overflows 500px, we should check if scrollHeight > 500.
                           
                           if (content && content.scrollHeight > 500) {
                               toggle.style.display = 'block';
                           } else {
                               toggle.style.display = 'none';
                           }
                       });
                   }

                   // Run on load
                   checkReadMoreVisibility();
                   
                   // Run on resize
                   window.addEventListener('resize', checkReadMoreVisibility);
                   
                   // Click handler (delegated or direct)
                   var toggles = document.querySelectorAll('.read-more-toggle');
                   toggles.forEach(function(toggle) {
                       toggle.addEventListener('click', function(e) {
                           e.preventDefault();
                           var content = this.previousElementSibling;
                           content.classList.toggle('expanded');
                           if (content.classList.contains('expanded')) {
                               content.style.maxHeight = content.scrollHeight + 'px';
                               this.textContent = 'Read Less';
                           } else {
                               content.style.maxHeight = '500px';
                               this.textContent = 'Read More';
                           }
                       });
                   });
               });

               function emToPixels(em, element) { 
                   return em * parseFloat(getComputedStyle(element).fontSize); 
               } 
               // Adjust the height of 'fotoContent' 
               document.addEventListener("DOMContentLoaded", function() { 
                   var fotoContent = document.querySelector('.foto-content'); 
                   if (fotoContent) { 
                       // Initially set height to calc logic if needed, OR just set to auto if that's what user wants
                       // User requested: "setta la height di foto content su auto quando la pagina e' stata caricata"
                       // So we override the previous logic or append this.
                       
                       // Let's keep the initial calculation if useful for layout, then switch to auto?
                       // Or just force auto. The user says "quando la pagina e' stata caricata".
                       // This event IS DOMContentLoaded.
                       
                       // Previous logic:
                       // var emInPixels = emToPixels(2, document.body); 
                       // fotoContent.style.height = (window.innerHeight - emInPixels) + 'px'; 
                       
                       // New logic requested:
                       fotoContent.style.height = '93vh';
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
          // However, if the user explicitly set 'sinistra', 'alto', or 'basso', we must respect it.
          // 'square' usually means default or unset in some contexts, but let's check.
          if (empty($alignment) || $alignment === 'square') { 
              $alignment = 'destra'; 
          } 
          ?> 
          <a class="related-foto-item" href="<?php echo esc_url($image_url); ?>" style="color:black;" data-lightbox="gallery"> 
          <div class="fotoContainer <?php echo esc_attr($alignment); ?>"> 
               <div class="image-wrapper"> 
                   <?php echo wp_get_attachment_image($attachment_id, 'full'); ?> 
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
        docClose: true, 
    }); 

    // Custom close on wrapper or overlay click
    $(document).off('click', '.sl-wrapper, .sl-overlay').on('click', '.sl-wrapper, .sl-overlay', function(e) {
        // Close if clicking directly on wrapper, overlay, or close button (x)
        if ($(e.target).is('.sl-wrapper, .sl-overlay, .sl-close, .sl-close *')) {
            gallery.close();
        }
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
       //console.warn("Image not found"); 
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
