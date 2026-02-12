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
    // Dropdown for selecting posts 
    echo '<div style="position:relative;">'; // Wrapper for positioning
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

            // Output the list item with the thumbnail and title - GRID STYLE
            echo '<li value="' . esc_attr($post_id) . '" class="dropdown-item" style="display: none;">';
            if ($thumbnail_url) {
                echo '<div class="item-preview"><img src="' . esc_url($thumbnail_url) . '" alt=""></div>';
            } else {
                 echo '<div class="item-preview" style="background:#eee;"></div>';
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
    // Button moved/styled to be accessible
    echo '<button type="button" id="add-item" style="margin-top: 10px; width: 100%;">Aggiungi alla Griglia</button>';
    echo '</div>'; // End wrapper

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
        #add-item {
            margin: 20px 0;
            background-color: #2271b1; /* Blue like WP */
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            padding: 10px 20px;
            font-weight: 600;
        }
        #add-item:hover { background-color: #135e96; }
        
        .grid-item {
            flex: 0 1 calc(33.333% - 10px);
            box-sizing: border-box;
            position: relative;
            border: 1px solid #ddd;
            padding: 5px;
            aspect-ratio: 1 / 1;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center; /* Center horizontally by default */
            justify-content: center; /* Center vertically by default */
        }
        .grid-item.sinistra { justify-content: flex-start; align-items: flex-start; }
        .grid-item.destra { justify-content: flex-start; align-items: flex-end; }
        .grid-item.alto { justify-content: flex-start; align-items: center; }
        .grid-item.basso { justify-content: flex-end; align-items: center; }
        
        .grid-item .image-container {
             flex: 1;
             display: flex;
             align-items: inherit; /* Inherit alignment from grid-item */
             justify-content: inherit; /* Inherit justification from grid-item */
             overflow: hidden;
             background: #f9f9f9;
             width: 100%;
        }
        .grid-item img {
            max-width: 100%;
            height: auto;
            max-height: 100%;
            object-fit: contain;
        }
        .grid-item .remove-item {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: #d63638;
            border: none;
            border-radius: 3px;
            color: white;
            cursor: pointer;
            padding: 2px 8px;
            font-size: 10px;
            z-index: 10;
        }
        .ui-state-highlight {
            height: 150px;
            background-color: #fafafa;
            border: 1px dashed #ccc;
        }
        
        /* Dropdown Styles - Grid Layout */
        .portfolio-dropdown {
            list-style: none;
            padding: 0;
            margin: 0;
            border: 1px solid #ccd0d4;
            background: #fff;
            position: relative;
            width: 100%;
            cursor: pointer;
            display: flex;
            flex-wrap: wrap;
            max-height: 400px; /* Scrollable if too long */
            overflow-y: auto;
        }
        .portfolio-dropdown.is-open {
            padding: 10px;
            border-color: #8c8f94;
            box-shadow: 0 3px 5px rgba(0,0,0,0.2);
            z-index: 100;
        }

        .portfolio-dropdown .dropdown-toggle {
            width: 100%;
            padding: 10px 15px;
            background-color: #f6f7f7;
            position: sticky; /* Keeps it at top when scrolling inside ul */
            top: 0;
            z-index: 20;
            border-bottom: 1px solid #ddd;
            font-weight: 600;
        }

        .portfolio-dropdown .dropdown-toggle:after {
            content: '';
            position: absolute;
            right: 15px;
            top: 50%;
            border: 5px solid transparent;
            border-top-color: #333;
            transform: translateY(-25%);
        }

        .portfolio-dropdown li.dropdown-item {
            flex: 0 0 calc(25% - 10px); /* 4 columns */
            margin: 5px;
            padding: 8px;
            border: 1px solid #eee;
            background: #fff;
            display: none; /* Managed by JS */
            flex-direction: column;
            align-items: center;
            box-sizing: border-box;
            transition: all 0.2s;
        }
        
        /* Show items when parent has is-open class (handled via JS toggling display, 
           but we can use class based visibility if we change JS. 
           Current JS toggles display:none/block on siblings. 
           We will stick to JS toggling for now.) */

        .portfolio-dropdown li.dropdown-item:hover {
            background-color: #f0f6fb;
            border-color: #2271b1;
        }
        .portfolio-dropdown li.dropdown-item.selected {
            background-color: #e7f0f7;
            border-color: #2271b1;
            box-shadow: inset 0 0 0 1px #2271b1;
        }

        .portfolio-dropdown .item-preview {
            width: 100%;
            aspect-ratio: 1/1;
            overflow: hidden;
            margin-bottom: 8px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .portfolio-dropdown .item-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .portfolio-dropdown .item-info {
            text-align: center;
            width: 100%;
        }
        .portfolio-dropdown .item-title {
            display: block;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .portfolio-dropdown .item-type {
            display: block;
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
            margin-top: 3px;
        }

        @media (max-width: 782px) {
            .portfolio-dropdown li.dropdown-item {
                flex: 0 0 calc(50% - 10px); /* 2 columns on mobile */
            }
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

            // Toggle dropdown on click
            $('#add-portfolio-item').on('click', '.dropdown-toggle', function(event) {
                var $dropdown = $(this).parent();
                $dropdown.toggleClass('is-open');
                
                // Toggle visibility of items
                if ($dropdown.hasClass('is-open')) {
                    $(this).siblings('li').css('display', 'flex'); // Show as flex items
                } else {
                    $(this).siblings('li').hide();
                }
                event.stopPropagation();
            });

            // Handle dropdown item selection
            $('#add-portfolio-item').on('click', 'li.dropdown-item', function(event) {
                var postId = $(this).attr('value');
                var selectedTitle = $(this).find('.item-title').text();

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
                event.stopPropagation(); // Stop propagation to keep dropdown open
            });

            // Append selected items to grid on button click
            $('#add-item').on('click', function() {
                selectedItems.forEach(function(item) {
                    var gridItemHTML = '<div class="grid-item" data-id="' + item.id + '">' +
                        '<div class="image-container" style="display:flex; align-items:center; justify-content:center;">' +
                        // Placeholder image or text, real content renders on save/reload usually, 
                        // unless we fetch image src via AJAX. For now, text placeholder.
                        '<span style="font-size:10px; color:#666;">Salva per anteprima</span>' +
                        '</div>' +
                        '<select class="item-alignment" name="item_alignment[' + item.id + ']">' +
                        '<option value="alto">Alto</option><option value="basso">Basso</option>' +
                        '<option value="sinistra">Sinistra</option><option value="destra">Destra</option>' +
                        '</select>' +
                        '<div style="color: deeppink;">Item</div>' +
                        '<button type="button" class="remove-item">Remove</button>' + 
                        '</div>';

                    $list.append(gridItemHTML);
                });

                // Update the hidden input field
                updateField();

                // Clear selected items after adding
                selectedItems = [];
                $('#add-portfolio-item .dropdown-toggle').text('Seleziona un post');
                $('#add-portfolio-item li.dropdown-item').removeClass('selected').hide();
                $('#add-portfolio-item').removeClass('is-open');
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#add-portfolio-item').length) {
                    $('#add-portfolio-item li.dropdown-item').hide();
                    $('#add-portfolio-item').removeClass('is-open');
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
