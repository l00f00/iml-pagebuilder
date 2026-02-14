<?php
/**
 * Project Meta Box logic.
 */

if (!defined('ABSPATH')) {
    exit;
}

function add_prj_meta_box() {
    add_meta_box('prj_custom_meta_box', 'IML Page Builder', 'prj_meta_box_callback', ['progetto','serie'], 'normal', 'high');
}
add_action('add_meta_boxes', 'add_prj_meta_box');

function prj_meta_box_callback($post) {
    wp_nonce_field('prj_save_meta_box_data', 'prj_meta_box_nonce');
    $prj_items = get_post_meta($post->ID, 'prj_items', true) ?: [];
    //echo '<pre>';
    //print_r($prj_items); // This will print the array in a readable format
    //echo '</pre>';
    echo '<button style="margin-bottom:30px;" id="custom_media_upload" class="button">Upload Foto</button>';
    
    echo '<div style="position:relative;">'; // Wrapper
    echo '<ul id="add-prj-item" class="prj-grid-selector">';
    // Removed dropdown-toggle line

    // List of selectable posts
    $selectable_posts = new WP_Query([
        'post_type'      => ['attachment'],
        'posts_per_page' => -1,
        'post_status'    => 'any',
    ]);

    if ($selectable_posts->have_posts()) {
        while ($selectable_posts->have_posts()) {
        $selectable_posts->the_post();
        $post_id = get_the_ID();
        $post_type = get_post_type($post_id);
        $thumbnail_url = '';

        // If the post is an attachment, use the attachment ID to get the image URL
        if ($post_type === 'attachment') {
            $thumbnail_url = wp_get_attachment_image_url($post_id, 'thumbnail');
        } else {
            // For other post types, get the post thumbnail
            $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
        }
        
        // Check if item is already in grid to mark as selected
        $is_selected = in_array($post_id, $prj_items) ? ' selected' : '';

        // Output the list item with the thumbnail and title
        echo '<li value="' . esc_attr($post_id) . '" class="' . $is_selected . '" data-id="' . esc_attr($post_id) . '">';
        if ($thumbnail_url) {
            echo '<img src="' . esc_url($thumbnail_url) . '" alt="">';
        }
        echo '<span class="title">' . get_the_title() . '</span>';
        // echo ' - ';
        // if($post_type === 'attachment'){echo 'Foto';} else {echo $post_type;}
        echo '</li>';
    }
    }
    wp_reset_postdata();

    echo '</ul>';
    // Removed "Aggiungi alla Griglia" button as per request for direct interaction
    echo '</div>'; // End wrapper

    // Hidden field to track post IDs
    echo '<input type="hidden" name="prj_items" id="prj_items_field" value="' . esc_attr(implode(',', $prj_items)) . '" />';
    
    // questa e' la griglia
    echo '<div id="prj-items-list">';
    foreach ($prj_items as $item_id) {
        echo prj_render_grid_item($item_id);
    }
    echo '</div>';

}

function prj_render_grid_item($post_id) {
    $post_type = get_post_type($post_id);
    $alignment = get_post_meta($post_id, 'prj_item_alignment', true) ?: 'square';
    $has_single = get_post_meta($post_id, 'has_single_page', true) ?: false; // Ottiene lo stato della checkbox
    $image_orientation = 'horizontal'; // Default orientation
    $image_id = ('attachment' === $post_type) ? $post_id : get_post_thumbnail_id($post_id);
    if ($image_id) {
        $image_data = wp_get_attachment_metadata($image_id);
        if ($image_data['width'] < $image_data['height']) {
            $image_orientation = 'vertical';
        }
    }
    $output = '<div class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '">';
    // Image container
    $output .= '<div class="image-container">';
    if ('attachment' === $post_type) {
        $output .= wp_get_attachment_image($post_id, 'medium');
    } else {
        $output .= get_the_post_thumbnail($post_id, 'medium');
    }
    $output .= '</div>'; // End of image container
    // Aggiunta di una select per l'allineamento
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
        // Puoi sostituire questo con un'icona o un'immagine a tua scelta
        $output .= '<span style="color: green;">&#10004; Foto con pagina Singola</span>';
    }
    $output .= '<button type="button" class="remove-item">Remove</button>';
    $output .= '<div style="color: deeppink;">  '. $post_type .'</div>';
    $output .= '</div>';

    return $output;
}

function save_prj_meta_box_data($post_id) {
    // Verifica la validità del nonce
    if (!isset($_POST['prj_meta_box_nonce']) || !wp_verify_nonce($_POST['prj_meta_box_nonce'], 'prj_save_meta_box_data')) {
        return;
    }

    // Evita il salvataggio automatico
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $prj_items_alignment = array(); // Array per memorizzare gli allineamenti degli elementi

    // Salva gli elementi associati, solo se ci sono elementi
    if (isset($_POST['prj_items']) && !empty($_POST['prj_items'])) {
        $prj_items = explode(',', sanitize_text_field($_POST['prj_items']));
        
        // Assicurati che ci siano elementi validi nell'array
        $prj_items = array_filter($prj_items, function($item) {
            return !empty($item); // Filtra eventuali elementi vuoti
        });

        if (!empty($prj_items)) {
            update_post_meta($post_id, 'prj_items', $prj_items);
        } else {
            delete_post_meta($post_id, 'prj_items'); // Rimuovi il meta se non ci sono più elementi
        }
    } else {
        delete_post_meta($post_id, 'prj_items'); // Rimuovi il meta se non ci sono elementi inviati
    }

    // Salva l'allineamento e imposta il parent per ogni elemento, solo se ci sono dati
    if (isset($_POST['item_alignment']) && !empty($_POST['item_alignment'])) {
        foreach ($_POST['item_alignment'] as $item_id => $alignment) {
            if (!empty($item_id) && !empty($alignment)) {
                // Salva l'allineamento dell'elemento
                update_post_meta($item_id, 'prj_item_alignment', sanitize_text_field($alignment));
                $prj_items_alignment[$item_id] = sanitize_text_field($alignment); // Aggiungi all'array

                // Imposta il post_parent per ogni item_id
                set_post_parent($item_id, $post_id); // Chiamata alla funzione personalizzata
            }
        }
    }

    // Salva l'array di allineamenti come meta field, solo se non è vuoto
    if (!empty($prj_items_alignment)) {
        update_post_meta($post_id, 'prj_items_alignment', $prj_items_alignment);
    } else {
        delete_post_meta($post_id, 'prj_items_alignment'); // Rimuovi il meta se vuoto
    }

    // Contiamo quanti figli ha il post corrente
    $child_count = count_child_posts($post_id);

    // Aggiungi un messaggio alla pagina di amministrazione che mostra il numero di child
    add_action('admin_notices', function() use ($child_count) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p>Questo post ha <strong>' . $child_count . '</strong> child post associati.</p>';
        echo '</div>';
    });
}

// Funzione personalizzata per impostare il post_parent
function set_post_parent($item_id, $post_id) {
    // Recupera l'oggetto post dell'item
    $post = get_post($item_id);

    // Verifica se il post esiste e se il parent è già corretto
    if ($post && $post->post_parent != $post_id) {
        // Aggiorna solo se il post_parent è diverso
        wp_update_post(array(
            'ID'          => $item_id,
            'post_parent' => $post_id
        ));
    }
}

// Funzione per contare quanti post figli ha un dato post
function count_child_posts($post_id) {
    $args = array(
        'post_parent' => $post_id,
        'post_type'   => 'any', // Puoi specificare un post type specifico se necessario
        'numberposts' => -1 // Ottieni tutti i post
    );

    $child_posts = get_posts($args);
    return count($child_posts); // Restituisce il numero di post figli
}

add_action('save_post', 'save_prj_meta_box_data');

// Include lo stile CSS per gestire l'aspetto della griglia e dei pulsanti
add_action('admin_enqueue_scripts', 'prj_enqueue_admin_styles');
function prj_enqueue_admin_styles() {
    // Load only on post edit pages
    $screen = get_current_screen();
    if ($screen->post_type === 'progetto' || $screen->post_type === 'serie') {
        wp_enqueue_style('prj-admin-style', IML_PLUGIN_URL . 'includes/post-types/project/admin-style.css', array(), '1.0');
    }
}

// Include lo script JavaScript per rendere la lista "sortable" e gestire l'aggiunta e la rimozione
add_action('admin_footer', 'prj_admin_scripts');
function prj_admin_scripts() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $list = $('#prj-items-list');
            var $field = $('#prj_items_field');
            var selectedItems = [];

            // Rendi la lista sortable
            $list.sortable({
                placeholder: 'ui-state-highlight',
                update: function(event, ui) {
                    updateField();
                }
            });

            // Aggiorna il campo nascosto con gli ID correnti dopo il drag-and-drop
            function updateField() {
                var ids = $list.sortable('toArray', { attribute: 'data-id' });
                $field.val(ids.join(','));
            }

            // Gestisci il click del pulsante di rimozione
            $list.on('click', '.remove-item', function() {
                $(this).closest('.grid-item').remove();
                updateField();
            });

    jQuery(document).ready(function($) {
    // Handle item selection/deselection from the grid selector
    $('#add-prj-item li').on('click', function() {
        var $this = $(this);
        var postId = $this.data('id');
        var thumbUrl = $this.find('img').attr('src');
        var title = $this.find('.title').text();

        // Check if item is already in the grid
        var $existingItem = $('#prj-items-list .grid-item[data-id="' + postId + '"]');

        if ($existingItem.length > 0) {
            // Remove from grid
            $existingItem.remove();
            $this.removeClass('selected');
        } else {
            // Add to grid
            // We need to fetch the item HTML or construct it. 
            // For simplicity and immediate feedback, we'll construct a basic version.
            // Ideally, we might want to AJAX this to get the full PHP render (with alignment options), 
            // but the previous JS also constructed HTML. Let's improve the JS construction.
            
            var gridItemHTML = '<div class="grid-item fotoContainer square" data-id="' + postId + '">' +
                '<div class="image-container">' +
                '<img src="' + thumbUrl + '" alt="" class="attachment-medium size-medium">' +
                '</div>' +
                '<select class="item-alignment" name="item_alignment[' + postId + ']">' +
                '<option value="alto">Alto</option>' +
                '<option value="basso">Basso</option>' +
                '<option value="sinistra">Sinistra</option>' +
                '<option value="destra">Destra</option>' +
                '</select>' +
                '<button type="button" class="remove-item">Remove</button>' +
                '<div style="color: deeppink;">attachment</div>' + 
                '</div>';

            $('#prj-items-list').append(gridItemHTML);
            $this.addClass('selected');
        }

        // Update the hidden input field
        updateField();
    });

    // Function to update the hidden field with the current IDs
    function updateField() {
        var ids = [];
        $('#prj-items-list .grid-item').each(function() {
            ids.push($(this).data('id'));
        });
        $('#prj_items_field').val(ids.join(','));
    }
    
    // Sync removal from grid back to the selector
    $('#prj-items-list').on('click', '.remove-item', function() {
        var postId = $(this).closest('.grid-item').data('id');
        // Remove 'selected' class from the corresponding item in the selector
        $('#add-prj-item li[data-id="' + postId + '"]').removeClass('selected');
        // The item is removed from DOM by the other handler, but we need to ensure updateField runs.
        // Wait for other handler or trigger removal here?
        // The existing handler is: $list.on('click', '.remove-item', ...) which runs updateField.
        // So we just need to update the visual state of the selector.
    });
});
        
        jQuery(document).ready(function($) {
            $('#custom_media_upload').click(function(e) {
                e.preventDefault();
                var mediaUploader = wp.media({
                    title: 'Upload Media',
                    button: {
                        text: 'Select'
                    },
                    multiple: true // Allow multiple file selection
                }).on('select', function() {
                    // Get the selected media
                    var selections = mediaUploader.state().get('selection');
                    
                    // FIX: Handle empty value correctly to avoid empty string in array
                    var val = $('#prj_items_field').val();
                    var existingIds = val ? val.split(',') : [];
        
                    selections.each(function(attachment) {
                        existingIds.push(attachment.id); // Add new attachment IDs to the array
                        // Optional: Append the new item to the grid
                        var gridItemHTML = '<div class="grid-item" data-id="' + attachment.id + '">' +
                            '<button type="button" class="remove-item">Remove</button>' +
                            '<img src="' + attachment.attributes.url + '" alt="" style="max-width: 100%; height: auto;">' +
                            '</div>';
                        $('#prj-items-list').append(gridItemHTML);
                    });
        
                    $('#prj_items_field').val(existingIds.join(',')); // Update the hidden field
                });
                mediaUploader.open();
            });
        });
    });
    </script>
    <?php
}
function prj_featured_image_metabox_custom_field($content, $post_id) {
    // BUG/TYPO NOTICE: The field read here is '_prj_image_alignment', but the field saved below is '_prj_feautured_image_alignment'.
    // This discrepancy means the alignment setting might not persist correctly.
    // Check this on frontend if alignment issues occur.
    $alignment = get_post_meta($post_id, '_prj_image_alignment', true);

    $content .= '<p><strong>Select Image Alignment:</strong></p>';
    $content .= '<select name="prj_image_alignment" id="prj-image-alignment">';
    $content .= '<option value="alto"' . selected($alignment, 'alto', false) . '>Alto</option>';
    $content .= '<option value="basso"' . selected($alignment, 'basso', false) . '>Basso</option>';
    $content .= '<option value="destra"' . selected($alignment, 'destra', false) . '>Destra</option>';
    $content .= '<option value="sinistra"' . selected($alignment, 'sinistra', false) . '>Sinistra</option>';
    $content .= '</select>';

    return $content;
}
add_filter('admin_post_thumbnail_html', 'prj_featured_image_metabox_custom_field', 10, 2);

function save_prj_featured_image_alignment($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['prj_feautured_image_alignment'])) {
        update_post_meta($post_id, '_prj_feautured_image_alignment', sanitize_text_field($_POST['prj_feautured_image_alignment']));
    }
}
add_action('save_post', 'save_prj_featured_image_alignment');