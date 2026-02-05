<?php
/**
 * Portfolio Meta Box logic.
 */

if (!defined('ABSPATH')) {
    exit;
}

function add_portfolio_meta_box() {
    add_meta_box('portfolio_custom_meta_box', 'IML Page Builder', 'portfolio_meta_box_callback', 'portfolio', 'normal', 'high');
}
add_action('add_meta_boxes', 'add_portfolio_meta_box');

function portfolio_meta_box_callback($post) {
    wp_nonce_field('portfolio_save_meta_box_data', 'portfolio_meta_box_nonce');
    $portfolio_items = get_post_meta($post->ID, 'portfolio_items', true) ?: [];
    
    echo '<button style="margin-bottom:30px;" id="portfolio_media_upload" class="button">Upload Foto</button>';
    echo '<ul id="add-portfolio-item" class="portfolio-dropdown">';
    echo '<li class="dropdown-toggle">Seleziona un post</li>';

    // List of selectable posts
    $selectable_posts = new WP_Query([
        'post_type'      => ['progetto', 'serie', 'attachment'],
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

            // Output the list item with the thumbnail and title
            echo '<li value="' . esc_attr($post_id) . '" style="display: none;">';
            if ($thumbnail_url) {
                echo '<img src="' . esc_url($thumbnail_url) . '" alt="" style="width: 100px; height: 100px; margin-right: 5px;">';
            }
            echo get_the_title();
            echo ' - ';
            if($post_type === 'attachment'){echo 'Foto';} else {echo $post_type;}
            echo '</li>';
        }
    }
    wp_reset_postdata();

    echo '</ul>';
    echo '<button type="button" id="add-item">Aggiungi alla Griglia</button>';

    // Hidden field to track post IDs
    echo '<input type="hidden" name="portfolio_items" id="portfolio_items_field" value="' . esc_attr(implode(',', $portfolio_items)) . '" />';
    
    // questa e' la griglia
    echo '<div id="portfolio-items-list">';
    foreach ($portfolio_items as $item_id) {
        echo portfolio_render_grid_item($item_id);
    }
    echo '</div>';
}

function portfolio_render_grid_item($post_id) {
    $post_type = get_post_type($post_id);
    $alignment = get_post_meta($post_id, 'portfolio_item_alignment', true) ?: 'square';
    $image_orientation = 'horizontal'; // Default orientation
    $has_single = get_post_meta($post_id, 'has_single_page', true) ?: false; // Ottiene lo stato della checkbox
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
    $output .= '</div>';
    // End of image container
    
    $output .= '<select class="item-alignment" name="item_alignment[' . esc_attr($post_id) . ']">';
    if ($image_orientation === 'horizontal') {
        $output .= '<option value="alto"' . selected($alignment, 'alto', false) . '>Alto</option>';
        $output .= '<option value="basso"' . selected($alignment, 'basso', false) . '>Basso</option>';
    } else {
        $output .= '<option value="sinistra"' . selected($alignment, 'sinistra', false) . '>Sinistra</option>';
        $output .= '<option value="destra"' . selected($alignment, 'destra', false) . '>Destra</option>';
    }
    $output .= '</select>';
    // Aggiunta della checkbox per "Has Single"
    if ($post_type === 'attachment' && $has_single) {
        $output .= '<span style="color: green;">&#10004; Foto con pagina Singola</span>';
    }
    $output .= '<div style="color: deeppink;">  '. $post_type .'</div>';
    $output .= '<button type="button" class="remove-item">Remove</button>';
    $output .= '</div>';

    return $output;
}

function save_portfolio_meta_box_data($post_id) {
    if (!isset($_POST['portfolio_meta_box_nonce']) || !wp_verify_nonce($_POST['portfolio_meta_box_nonce'], 'portfolio_save_meta_box_data')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    $portfolio_items_alignment = array();
    
    if (isset($_POST['portfolio_items'])) {
        $portfolio_items = explode(',', sanitize_text_field($_POST['portfolio_items']));
        update_post_meta($post_id, 'portfolio_items', $portfolio_items);
        
        // Assegna il portfolio come parent agli attachment
        foreach ($portfolio_items as $item_id) {
            $post_type = get_post_type($item_id);
            if ($post_type === 'attachment') {
                $existing_parent = wp_get_post_parent_id($item_id);

                // Evita di sovrascrivere se il parent è di tipo progetto o serie
                if ($existing_parent && in_array(get_post_type($existing_parent), ['progetto', 'serie'], true)) {
                    // Aggiungi solo un meta specifico per il portfolio parent
                    update_post_meta($item_id, '_portfolio_parent', $post_id);
                } else {
                    // Assegna come parent diretto se non ci sono conflitti
                    wp_update_post([
                        'ID' => $item_id,
                        'post_parent' => $post_id,
                    ]);
                    update_post_meta($item_id, '_portfolio_parent', $post_id);
                }
            }
        }
    }
    // Salvataggio dell'allineamento per ogni elemento
    if (isset($_POST['item_alignment'])) {
        foreach ($_POST['item_alignment'] as $item_id => $alignment) {
            update_post_meta($item_id, 'portfolio_item_alignment', sanitize_text_field($alignment));
            $portfolio_items_alignment[$item_id] = sanitize_text_field($alignment); // Add to associative array
        }
    }
    
    // Save the associative array as a post meta
    if (!empty($portfolio_items_alignment)) {
        update_post_meta($post_id, 'portfolio_items_alignment', $portfolio_items_alignment);
    }
}
add_action('save_post', 'save_portfolio_meta_box_data');

// Include lo stile CSS per gestire l'aspetto della griglia e dei pulsanti
add_action('admin_head', 'portfolio_admin_styles');
function portfolio_admin_styles() {
    ?>
    <style>
        #portfolio-items-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        #add-item{
            margin:20px 0;
            background-color: #ff6d6d;
            border: none;
            border-radius: 15px;
            color: white;
            cursor: pointer;
        }
        .grid-item {
            flex: 0 1 calc(33.333% - 10px);
            box-sizing: border-box;
            position: relative;
            border: 1px solid #ddd;
            padding: 5px;
            aspect-ratio: 1 / 1;
        }
        .grid-item img {
            max-width: 100%;
            height: auto;
        }
        .grid-item .remove-item {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #ff6d6d;
            border: none;
            border-radius: 15px;
            color: white;
            cursor: pointer;
        }
        .ui-state-highlight {
            height: 150px;
            background-color: #fafafa;
            border: 1px dashed #ccc;
        }
        .portfolio-dropdown {
        list-style: none;
        padding: 0;
        margin: 0;
        border: 1px solid lightgray;
        position: relative; /* Parent container for absolute positioning */
        width: auto; /* Adjust width as needed */
        cursor: pointer;
    }

    .portfolio-dropdown .dropdown-toggle {
        padding: 5px 10px;
        background-color: #fff;
        position: relative;
    }

    .portfolio-dropdown .dropdown-toggle:after {
        content: '';
        position: absolute;
        right: 10px;
        top: 50%;
        border-width: 5px;
        border-style: solid;
        border-color: #000 transparent transparent transparent;
        transform: translateY(-50%);
    }

    .portfolio-dropdown li {
        padding: 5px 10px;
        background-color: #fff;
        border-top: 1px solid lightgray;
        display: none; /* Initially hide all items */
        position: relative; /* Absolutely position each item */
        width: 100%; /* Match the width of the parent container */
        box-sizing: border-box; /* Include padding and borders in the width */
        z-index: 11; /* Ensure it's above other content */
    }

    .portfolio-dropdown li:first-child {
        display: block; /* Always show the toggle item */
        position: relative; /* Position this item relative to the dropdown */
        z-index: auto; /* Reset z-index for the toggle item */
    }
    .portfolio-dropdown li:hover {
        background-color: #f2f2f2;
    }

    .portfolio-dropdown img {
        vertical-align: middle;
        margin-right: 10px;
    }
    /* Style for the image wrapper */
    .image-container {
      display: flex; /* Use flexbox for alignment 
      width:640px;
      height:640px;*/
      max-width:100%;
      height: -webkit-fill-available;
    }
    .image-container img{
      display:flex;
    max-width: 100%;
    max-height:100%;  
    object-fit:contain;/*not sure*/
    }
    
    /* Now, we align the image within the image wrapper */
    .fotoContainer.destra .image-container {
      justify-content: flex-end; /* Align image to the right */
      width: -webkit-fill-available;
    }
    .fotoContainer.destra .image-container img{
        height: 100%;
        width: auto;
    }
    
    .fotoContainer.sinistra .image-container {
      justify-content: flex-start; /* Align image to the left */
    }
    .fotoContainer.sinistra .image-container img {
        height: 100%;
        width: auto;
    }
    
    .fotoContainer.alto .image-container {
      align-items: flex-start; /* Align image to the top */
    }
    
    .fotoContainer.alto .image-container img{
        width: 100%;
        height: auto;
      object-fit:contain;
    }
    
    .fotoContainer.basso .image-container {
      align-items: flex-end; /* Align image to the bottom */
    }
    .fotoContainer.basso .image-container img {
      width: 100%;
      height: auto;
    }
    </style>
    <?php
}

// Include lo script JavaScript per rendere la lista "sortable" e gestire l'aggiunta e la rimozione
add_action('admin_footer', 'portfolio_admin_scripts');
function portfolio_admin_scripts() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $list = $('#portfolio-items-list');
            var $field = $('#portfolio_items_field');

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

            // Gestisci il click del pulsante di aggiunta
            $('#add-item').on('click', function() {
                var selectedID = $('#add-portfolio-item').val();
                var selectedText = $('#add-portfolio-item option:selected').text();

                if (selectedID) {
                    // Aggiungi il nuovo post alla lista
                    $list.append('<div class="grid-item" data-id="' + selectedID + '">' +
                        '<button type="button" class="remove-item">Remove</button>' + 
                        '<p>' + selectedText + '</p></div>');
                    
                    // Aggiorna il campo nascosto
                    updateField();
                }
            });
        });
    </script>
    <script>
    jQuery(document).ready(function($) {
    var selectedItems = [];

    // Toggle dropdown on click
    $('#add-portfolio-item').on('click', '.dropdown-toggle', function(event) {
        $(this).siblings('li').toggle();
        event.stopPropagation(); // Prevent this click from being propagated
    });

    // Handle dropdown item selection
    $('#add-portfolio-item li:not(.dropdown-toggle)').on('click', function() {
        var postId = $(this).attr('value');
        var selectedTitle = $(this).text();

        // Check and toggle selection
        var selectedItemIndex = selectedItems.findIndex(item => item.id === postId);
        if (selectedItemIndex > -1) {
            selectedItems.splice(selectedItemIndex, 1); // Remove item if already selected
            $(this).removeClass('selected');
        } else {
            selectedItems.push({id: postId, title: selectedTitle}); // Add new item to the selection
            $(this).addClass('selected');
        }

        // Display the selected items
        var displayText = selectedItems.map(function(item) {
            return item.title;
        }).join(', ');
        $('#add-portfolio-item .dropdown-toggle').text(displayText || 'Seleziona un post');
    });

    // Append selected items to grid on button click
    $('#add-item').on('click', function() {
        selectedItems.forEach(function(item) {
            var gridItemHTML = '<div class="grid-item" data-id="' + item.id + '">' +
                '<button type="button" class="remove-item">Remove</button>' +
                '<p>' + item.title + '</p></div>';

            $('#portfolio-items-list').append(gridItemHTML);
        });

        // Update the hidden input field
        updateField();

        // Clear selected items after adding
        selectedItems = [];
        $('#add-portfolio-item .dropdown-toggle').text('Seleziona un post');
        $('#add-portfolio-item li').removeClass('selected').hide();
    });

    // Function to update the hidden field with the current IDs
    function updateField() {
        var ids = [];
        $('#portfolio-items-list .grid-item').each(function() {
            ids.push($(this).data('id'));
        });
        $('#portfolio_items_field').val(ids.join(','));
    }

    // Close dropdown when clicking outside
    $(document).on('click', function(event) {
        if (!$(event.target).closest('#add-portfolio-item').length) {
            $('#add-portfolio-item li').not('.dropdown-toggle').hide();
        }
    });
});
jQuery(document).ready(function($) {
            // FIX: Changed ID to unique 'portfolio_media_upload' to avoid conflicts
            $('#portfolio_media_upload').click(function(e) {
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
                    var val = $('#portfolio_items_field').val();
                    var existingIds = val ? val.split(',') : [];
        
                    selections.each(function(attachment) {
                        existingIds.push(attachment.id); // Add new attachment IDs to the array
                        // Optional: Append the new item to the grid
                        var gridItemHTML = '<div class="grid-item" data-id="' + attachment.id + '">' +
                            '<button type="button" class="remove-item">Remove</button>' +
                            '<img src="' + attachment.attributes.url + '" alt="" style="max-width: 100%; height: auto;">' +
                            '</div>';
                        $('#portfolio-items-list').append(gridItemHTML);
                    });
        
                    $('#portfolio_items_field').val(existingIds.join(',')); // Update the hidden field
                });
                mediaUploader.open();
            });
        });
    </script>
    <?php
}

function wpdude_display_featured_image_column( $column, $post_id ) {
    if ( 'featured_image' === $column ) {
        $post_featured_image = get_the_post_thumbnail( $post_id, 'thumbnail' );
        echo $post_featured_image ? $post_featured_image : 'No image set';
    }
}


add_filter( "manage_portfolio_posts_columns", 'wpdude_add_featured_image_column' );
add_action( "manage_portfolio_posts_custom_column", 'wpdude_display_featured_image_column', 10, 2 );

// Function to add a new column to the 'portfolio' post type
function add_portfolio_custom_column($columns) {
    $columns['associated_entries'] = 'Inserimenti Associati';
    return $columns;
}
add_filter('manage_portfolio_posts_columns', 'add_portfolio_custom_column');

// Function to populate the new column
function portfolio_custom_column_content($column, $post_id) {
    if ($column == 'associated_entries') {
        // Retrieve the array of IDs from the post meta
        $associated_ids = get_post_meta($post_id, 'portfolio_items_alignment', true);
        //print_r($associated_ids);
        if (is_array($associated_ids) && !empty($associated_ids)) {
            echo '<div class="portfolio-grid-container" style="display: flex;flex-direction: row; flex-wrap: wrap;">';
            foreach ($associated_ids as $associated_id => $allign) {
                $post_type = get_post_type($associated_id);
                $thumbnail_url = '';

                if ($post_type === 'attachment') {
                    $thumbnail_url = wp_get_attachment_image_url($associated_id, 'thumbnail');
                } else {
                    $thumbnail_url = get_the_post_thumbnail_url($associated_id, 'thumbnail');
                }

                if ($thumbnail_url) {
                    echo '<div class="portfolio-grid-item ' . esc_attr($post_type) . '">';
                    echo '<img src="' . esc_url($thumbnail_url) . '" alt="" style="width: 50px; height: 50px;">';
                    echo '</div>'; // End of .portfolio-grid-item
                }
            }
            echo '</div>'; // End of .portfolio-grid-container
        } else {
            echo 'No associated entries';
        }
    }
}
add_action('manage_portfolio_posts_custom_column', 'portfolio_custom_column_content', 10, 2);

function wpdude_add_featured_image_column($columns) {
    $columns['featured_image'] = __('Featured Image', 'text-domain');
    return $columns;
}
