<?php
/**
 * Homepage Meta Box logic.
 */

if (!defined('ABSPATH')) {
    exit;
}

 // Add the meta box for the homepage 
 function add_homepage_meta_box() { 
     add_meta_box('homepage_custom_meta_box', 'IML Page Builder', 'homepage_meta_box_callback', 'page'); 
 } 
 add_action('add_meta_boxes', 'add_homepage_meta_box'); 
 
 // Meta box display callback for the homepage 
 function homepage_meta_box_callback($post) { 
     if ($post->ID != get_option('page_on_front')) return; 
     $post_ids = rwmb_meta('post_homepage'); 
     wp_nonce_field('homepage_save_meta_box_data', 'homepage_meta_box_nonce'); 
     $homepage_items = get_post_meta($post->ID, 'homepage_items', true) ?: []; 

     // Dropdown for selecting posts 
     echo '<ul id="add-homepage-item" class="homepage-dropdown">'; 
     echo '<li class="dropdown-toggle">Seleziona i post da aggiungere</li>'; 
 
     $selectable_posts = new WP_Query([ 
         'post_type'      => ['progetto','portfolio', 'serie', 'attachment'], 
         'posts_per_page' => -1, 
         'post_status'    => 'published', 
     ]); 
 
     if ($selectable_posts->have_posts()) { 
         while ($selectable_posts->have_posts()) { 
             $selectable_posts->the_post(); 
             $post_id = get_the_ID(); 
             $post_type = get_post_type($post_id); 
             $thumbnail_url = $post_type === 'attachment' ? wp_get_attachment_image_url($post_id, 'thumbnail') : get_the_post_thumbnail_url($post_id, 'thumbnail'); 
 
             // AGGIORNATO: Struttura interna più pulita per la griglia
             echo '<li value="' . esc_attr($post_id) . '" class="dropdown-item" style="display: none;">'; 
             if ($thumbnail_url) { 
                 echo '<div class="item-preview"><img src="' . esc_url($thumbnail_url) . '" alt=""></div>'; 
             } 
             echo '<div class="item-info">';
             echo '<span class="item-title">' . get_the_title() . '</span>';
             echo '<span class="item-type">' . ($post_type === 'attachment' ? 'Foto' : $post_type) . '</span>';
             echo '</div>';
             echo '</li>'; 
         } 
     } 
     wp_reset_postdata(); 
     echo '</ul>'; 
     echo '<button type="button" id="add-homepage-item-button">Aggiungi alla griglia</button>'; 
 
     // Hidden field to track post IDs 
     echo '<input type="hidden" name="homepage_items" id="homepage_items_field" value="' . esc_attr(implode(',', $homepage_items)) . '" />'; 
     
     // Grid for displaying selected items 
     echo '<div id="homepage-items-list">'; 
     foreach ($homepage_items as $item_id) { 
         echo homepage_render_grid_item($item_id); 
     } 
     echo '</div>'; 
 } 
 
 function homepage_render_grid_item($post_id) { 
     $post_type = get_post_type($post_id); 
     $alignment = get_post_meta($post_id, 'homepage_item_alignment', true) ?: 'square'; 
     $has_single = get_post_meta($post_id, 'has_single_page', true) ?: false; 
     $image_orientation = 'horizontal'; 
     $image_id = ('attachment' === $post_type) ? $post_id : get_post_thumbnail_id($post_id); 
     if ($image_id) { 
         $image_data = wp_get_attachment_metadata($image_id); 
         if (isset($image_data['width']) && isset($image_data['height']) && $image_data['width'] < $image_data['height']) { 
             $image_orientation = 'vertical'; 
         } 
     } 
     $output = '<div class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '">'; 
     $output .= '<div class="image-container">'; 
     if ('attachment' === $post_type) { 
         $output .= wp_get_attachment_image($post_id, 'small'); 
     } else { 
         $output .= get_the_post_thumbnail($post_id, 'small');
     } 
     $output .= '</div>'; 
     
     $output .= '<select class="item-alignment" name="item_alignment[' . esc_attr($post_id) . ']">'; 
     if ($image_orientation === 'horizontal') { 
         $output .= '<option value="alto"' . selected($alignment, 'alto', false) . '>Alto</option>'; 
         $output .= '<option value="basso"' . selected($alignment, 'basso', false) . '>Basso</option>'; 
     } else { 
         $output .= '<option value="sinistra"' . selected($alignment, 'sinistra', false) . '>Sinistra</option>'; 
         $output .= '<option value="destra"' . selected($alignment, 'destra', false) . '>Destra</option>'; 
     } 
     $output .= '</select>'; 
     if ($post_type === 'attachment' && $has_single) { 
         $output .= '<span style="color: green;">&#10004; Pagina Singola</span>'; 
     } 
     $output .= '<button type="button" class="remove-item">Remove</button>'; 
     $output .= '<div class="post-type-label">'. $post_type .'</div>'; 
     $output .= '</div>'; 
 
     return $output; 
 } 
 
 // Inline JavaScript
 add_action('admin_print_footer_scripts', 'homepage_inline_js'); 
 function homepage_inline_js() { 
     $screen = get_current_screen(); 
     if ($screen->id === 'page' && isset($_GET['post']) && $_GET['post'] == get_option('page_on_front')) { 
         ?> 
         <script type="text/javascript"> 
             jQuery(document).ready(function($) { 
                 var $list = $('#homepage-items-list'); 
                 var $field = $('#homepage_items_field'); 
                 var selectedItems = []; 
 
                 $list.sortable({ 
                     placeholder: 'ui-state-highlight', 
                     update: function(event, ui) { updateField(); } 
                 }); 
 
                 function updateField() { 
                     var ids = []; 
                     $('#homepage-items-list .grid-item').each(function() { 
                         ids.push($(this).data('id')); 
                     }); 
                     $field.val(ids.join(',')); 
                 } 
 
                 $list.on('click', '.remove-item', function() { 
                     $(this).closest('.grid-item').remove(); 
                     updateField(); 
                 }); 
 
                 // Toggle dropdown
                 $('#add-homepage-item').on('click', '.dropdown-toggle', function(event) { 
                     $(this).siblings('li').toggle(); 
                     $(this).parent().toggleClass('is-open');
                     event.stopPropagation(); 
                 }); 
 
                 // Handle selection
                 $('#add-homepage-item').on('click', 'li.dropdown-item', function(event) { 
                     var postId = $(this).attr('value'); 
                     var selectedTitle = $(this).find('.item-title').text(); 
 
                     var index = selectedItems.findIndex(item => item.id === postId); 
                     if (index > -1) { 
                         selectedItems.splice(index, 1); 
                         $(this).removeClass('selected'); 
                     } else { 
                         selectedItems.push({id: postId, title: selectedTitle}); 
                         $(this).addClass('selected'); 
                     } 
 
                     var displayText = selectedItems.map(item => item.title).join(', '); 
                     $('#add-homepage-item .dropdown-toggle').text(displayText || 'Seleziona i post'); 
                     event.stopPropagation(); 
                 }); 
 
                 // Add to grid
                 $('#add-homepage-item-button').on('click', function() { 
                     selectedItems.forEach(function(item) { 
                         // Nota: qui aggiungiamo un placeholder, l'immagine apparirà al reload
                         var gridItemHTML = '<div class="grid-item" data-id="' + item.id + '">' + 
                             '<button type="button" class="remove-item">Remove</button>' + 
                             '<p style="font-size:10px;">' + item.title + ' (Salva per vedere anteprima)</p></div>'; 
                         $list.append(gridItemHTML); 
                     }); 
 
                     updateField(); 
                     selectedItems = []; 
                     $('#add-homepage-item .dropdown-toggle').text('Seleziona i post'); 
                     $('#add-homepage-item li.dropdown-item').removeClass('selected').hide(); 
                     $('#add-homepage-item').removeClass('is-open');
                 }); 
 
                 $(document).on('click', function(event) { 
                     if (!$(event.target).closest('#add-homepage-item').length) { 
                         $('#add-homepage-item li.dropdown-item').hide(); 
                         $('#add-homepage-item').removeClass('is-open');
                     } 
                 }); 
             }); 
         </script> 
         <?php 
     } 
 } 
 
 // Inline CSS 
 add_action('admin_head', 'homepage_inline_css'); 
 function homepage_inline_css() { 
     global $post; 
     if ($post && $post->ID == get_option('page_on_front')) { 
         ?> 
         <style type="text/css"> 
         /* Griglia principale del builder */
         #homepage-items-list { 
             display: flex; 
             flex-wrap: wrap; 
             gap: 15px; 
             margin-top: 20px;
         } 
         .grid-item { 
             flex: 0 1 calc(33.333% - 15px); 
             box-sizing: border-box; 
             position: relative; 
             border: 1px solid #ccd0d4; 
             padding: 10px; 
             background: #fff;
             aspect-ratio: 1 / 1; 
             display: flex;
             flex-direction: column;
         } 
         .grid-item img { max-width: 100%; height: auto; } 
         .grid-item .remove-item { 
             position: absolute; top: 5px; right: 5px; 
             background: #d63638; border: none; color: white; 
             border-radius: 3px; cursor: pointer; padding: 2px 8px; font-size: 10px;
             z-index: 10;
         } 
         .post-type-label { color: deeppink; font-size: 10px; margin-top: auto; }
 
         /* NUOVO: Stile Dropdown a Griglia */
         .homepage-dropdown { 
             list-style: none; padding: 0; margin: 0; 
             border: 1px solid #ccd0d4; background: #fff; 
             position: relative; cursor: pointer;
             display: flex; flex-wrap: wrap; /* Attiva la griglia */
         } 
         .homepage-dropdown.is-open { padding: 10px; }
 
         .homepage-dropdown .dropdown-toggle { 
             width: 100%; padding: 12px; background: #f6f7f7; 
             font-weight: 600; border-bottom: 1px solid #ccd0d4;
             position: relative;
         } 
         .homepage-dropdown .dropdown-toggle:after { 
             content: ''; position: absolute; right: 15px; top: 50%; 
             border: 6px solid transparent; border-top-color: #333; 
             transform: translateY(-25%); 
         } 
 
         /* Elementi della griglia nel dropdown */
         .homepage-dropdown li.dropdown-item { 
             /* MODIFICA QUI PER IL NUMERO DI COLONNE:
                calc(50% - 10px)  => 2 colonne
                calc(33.3% - 10px) => 3 colonne
                calc(25% - 10px)   => 4 colonne */
             flex: 0 0 calc(25% - 10px); 
             margin: 5px; padding: 8px; 
             border: 1px solid #eee; background: #fff; 
             display: none; /* Gestito da JS */
             flex-direction: column; align-items: center; 
             box-sizing: border-box; transition: all 0.2s;
         } 
         .homepage-dropdown li.dropdown-item:hover { background: #f0f6fb; border-color: #2271b1; }
         .homepage-dropdown li.dropdown-item.selected { background: #e7f0f7; border-color: #2271b1; box-shadow: inset 0 0 0 1px #2271b1; }
 
         /* Anteprime nel dropdown */
         .homepage-dropdown .item-preview { 
             width: 100%; aspect-ratio: 1/1; 
             overflow: hidden; margin-bottom: 8px; 
             background: #f0f0f0; display: flex; align-items: center; justify-content: center;
         } 
         .homepage-dropdown .item-preview img { width: 100%; height: 100%; object-fit: cover; margin: 0; } 
 
         /* Testi nel dropdown */
         .homepage-dropdown .item-info { text-align: center; width: 100%; }
         .homepage-dropdown .item-title { 
             display: block; font-size: 10px; font-weight: 600; line-height: 1.2;
             white-space: nowrap; overflow: hidden; text-overflow: ellipsis; 
         }
         .homepage-dropdown .item-type { display: block; font-size: 8px; color: #666; text-transform: uppercase; margin-top: 2px; }
 
         /* Bottone Aggiungi */
         #add-homepage-item-button { 
             margin: 15px 0; padding: 10px 20px;
             background-color: #2271b1; border: none; 
             border-radius: 4px; color: white; cursor: pointer; 
             font-weight: 600;
         } 
         #add-homepage-item-button:hover { background-color: #135e96; }
 
         .ui-state-highlight { height: 150px; background: #f0f0f1; border: 2px dashed #ccd0d4; flex: 0 1 calc(33.333% - 15px); } 
 
         /* Container immagini nel builder */
         .image-container { display: flex; width: 100%; height: 100%; overflow: hidden; background: #f9f9f9; } 
         .image-container img { width: 100%; height: 100%; object-fit: contain; } 
         .fotoContainer.destra .image-container { justify-content: flex-end; } 
         .fotoContainer.sinistra .image-container { justify-content: flex-start; } 
         .fotoContainer.alto .image-container { align-items: flex-start; } 
         .fotoContainer.basso .image-container { align-items: flex-end; } 
         </style> 
         <?php 
     } 
 } 
 
 function save_homepage_custom_order($post_id) { 
     if (!isset($_POST['homepage_meta_box_nonce']) || !wp_verify_nonce($_POST['homepage_meta_box_nonce'], 'homepage_save_meta_box_data')) return; 
     if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return; 
     
     if (isset($_POST['homepage_items'])) { 
         $homepage_items = array_filter(explode(',', sanitize_text_field($_POST['homepage_items']))); 
         update_post_meta($post_id, 'homepage_items', $homepage_items); 
     } 
 
     if (isset($_POST['item_alignment'])) { 
         $homepage_items_alignment = array(); 
         foreach ($_POST['item_alignment'] as $item_id => $alignment) { 
             update_post_meta($item_id, 'homepage_item_alignment', sanitize_text_field($alignment)); 
             $homepage_items_alignment[$item_id] = sanitize_text_field($alignment); 
         } 
         update_post_meta($post_id, 'homepage_items_alignment', $homepage_items_alignment); 
     } 
 } 
 add_action('save_post', 'save_homepage_custom_order'); 
 
 function wpdude_disable_homepage_editor() { 
     $frontpage_id = get_option('page_on_front'); 
     $current_screen = get_current_screen(); 
     if (is_admin() && $current_screen->id === 'page' && isset($_GET['post']) && $_GET['post'] == $frontpage_id) { 
         remove_post_type_support('page', 'editor'); 
     } 
 } 
 add_action('admin_head', 'wpdude_disable_homepage_editor'); 
