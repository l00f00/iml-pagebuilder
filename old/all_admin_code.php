// questo è tutto il codice che ho scritto e che attualmente funziona, e risiede dentro advanced-scripts (dove metto gli snippets)
// vorrei spostare questo codice in un plugin separato che faccia da builder per le pagine, LA STRUTTURA DI DATI NON DEVE ASSOLUTAMENTE CAMBIARE PERCHé é un sito in produzione

<?php


function disable_comments_support() {
    // Disable support for comments and trackbacks in post types
    foreach (get_post_types() as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }

    // Close comments on the front-end
    add_filter('comments_open', '__return_false', 20, 2);
    add_filter('pings_open', '__return_false', 20, 2);

    // Hide existing comments
    add_filter('comments_array', '__return_empty_array', 10, 2);

    // Remove comments page in menu
    add_action('admin_menu', function() {
        remove_menu_page('edit-comments.php');
    });

    // Redirect any user trying to access comments page
    add_action('admin_init', function() {
        global $pagenow;
        if ($pagenow === 'edit-comments.php') {
            wp_redirect(admin_url());
            exit;
        }
    });
}

add_action('init', 'disable_comments_support');

// Imposta 'wp_attachment_pages_enabled' sempre attivo (1)
add_action('init', function() {
    if (get_option('wp_attachment_pages_enabled') !== '1') {
        update_option('wp_attachment_pages_enabled', '1');
    }
});
function hide_default_posts_menu() {
    // Remove the "Posts" menu
    remove_menu_page('edit.php'); // Hides the "Posts" menu from the admin sidebar
}
add_action('admin_menu', 'hide_default_posts_menu');
function remove_posts_from_admin_bar($wp_admin_bar) {
    $wp_admin_bar->remove_node('new-post'); // Removes the "New Post" option
}
add_action('admin_bar_menu', 'remove_posts_from_admin_bar', 999);
function add_tags_to_attachments() {
    register_taxonomy_for_object_type('post_tag', 'attachment');
}
add_action('init', 'add_tags_to_attachments');
function enable_tags_meta_box_for_attachments() {
    add_meta_box(
        'tagsdiv-post_tag', // ID of the meta box
        __('Tags'), // Meta box title
        'post_tags_meta_box', // Callback function to display tags
        'attachment', // Post type
        'side', // Context (e.g., side, normal, advanced)
        'low' // Priority
    );
}
add_action('admin_menu', 'enable_tags_meta_box_for_attachments');

function get_the_tagged() {
    // Ensure this is a tag archive
    if (!is_tag()) {
        return [];
    }

    // Get the current tag
    $current_tag = get_queried_object();

    if (!$current_tag || !isset($current_tag->slug)) {
        return [];
    }

    // Prepare the query arguments
    $args = array(
        'post_type'      => array('portfolio', 'progetto', 'serie', 'attachment'), // Specify your post types
        'tax_query'      => array(
            array(
                'taxonomy' => 'post_tag', // Ensure it queries by tag taxonomy
                'field'    => 'slug',
                'terms'    => $current_tag->slug,
            ),
        ),
        'post_status'    => 'any', // Include all statuses to get attachments
        'posts_per_page' => -1,    // Get all posts
    );

    // Perform the query
    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $results = [];
        while ($query->have_posts()) {
            $query->the_post();
            $results[] = get_post(); // Collect the post object
        }
        wp_reset_postdata();
        return $results; // Return array of post objects
    }

    wp_reset_postdata();
    return []; // Return an empty array if no posts found
}

<?php
function add_portfolio_meta_box() {
    add_meta_box('portfolio_custom_meta_box', 'IML Page Builder', 'portfolio_meta_box_callback', 'portfolio', 'normal', 'high');
}
add_action('add_meta_boxes', 'add_portfolio_meta_box');

function portfolio_meta_box_callback($post) {
    wp_nonce_field('portfolio_save_meta_box_data', 'portfolio_meta_box_nonce');
    $portfolio_items = get_post_meta($post->ID, 'portfolio_items', true) ?: [];
    //echo '<pre>';
    //print_r($portfolio_items); // This will print the array in a readable format
    //echo '</pre>';
    echo '<button style="margin-bottom:30px;" id="custom_media_upload" class="button">Upload Foto</button>';
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
    // facciamo vedere la spunta
    // Qui mostriamo la spunta se l'attachment ha il campo "has_single_page"
    // Qui mostriamo la spunta se l'attachment ha il campo "has_single_page"
    if ($post_type === 'attachment' && $has_single) {
        // Puoi sostituire questo con un'icona o un'immagine a tua scelta
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
                    var existingIds = $('#portfolio_items_field').val().split(',');
        
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

?>

<?php 
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
 ?>

 <?php
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
    echo '<ul id="add-prj-item" class="prj-dropdown">';
    echo '<li class="dropdown-toggle">Seleziona foto</li>';

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

        // Output the list item with the thumbnail and title
        echo '<li value="' . esc_attr($post_id) . '" style="display: none;">';
        if ($thumbnail_url) {
            echo '<img src="' . esc_url($thumbnail_url) . '" alt="" style="width: 100px; height: 100px; margin-right: 10px;">';
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
add_action('admin_head', 'prj_admin_styles');
function prj_admin_styles() {
    ?>
    <style>
        #prj-items-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        #add-prj-item-button{
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
        .prj-dropdown {
        list-style: none;
        padding: 0;
        margin: 0;
        border: 1px solid lightgray;
        position: relative; /* Parent container for absolute positioning */
        width: auto; /* Adjust width as needed */
        cursor: pointer;
    }

    .prj-dropdown .dropdown-toggle {
        padding: 5px 10px;
        background-color: #fff;
        position: relative;
    }

    .prj-dropdown .dropdown-toggle:after {
        content: '';
        position: absolute;
        right: 10px;
        top: 50%;
        border-width: 5px;
        border-style: solid;
        border-color: #000 transparent transparent transparent;
        transform: translateY(-50%);
    }

    .prj-dropdown li {
        padding: 5px 10px;
        background-color: #fff;
        border-top: 1px solid lightgray;
        display: none; /* Initially hide all items */
        position: relative; /* Absolutely position each item */
        width: 100%; /* Match the width of the parent container */
        box-sizing: border-box; /* Include padding and borders in the width */
        z-index: 11; /* Ensure it's above other content */
    }

    .prj-dropdown li:first-child {
        display: block; /* Always show the toggle item */
        position: relative; /* Position this item relative to the dropdown */
        z-index: auto; /* Reset z-index for the toggle item */
    }
    .prj-dropdown li:hover {
        background-color: #f2f2f2;
    }

    .prj-dropdown img {
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
add_action('admin_footer', 'prj_admin_scripts');
function prj_admin_scripts() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            var $list = $('#prj-items-list');
            var $field = $('#prj_items_field');

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
                var selectedID = $('#add-prj-item').val();
                var selectedText = $('#add-prj-item option:selected').text();

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
    $('#add-prj-item').on('click', '.dropdown-toggle', function(event) {
        $(this).siblings('li').toggle();
        event.stopPropagation(); // Prevent this click from being propagated
    });

    // Handle dropdown item selection
    $('#add-prj-item li:not(.dropdown-toggle)').on('click', function() {
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
        $('#add-prj-item .dropdown-toggle').text(displayText || 'Seleziona un post');
    });

    // Append selected items to grid on button click
    $('#add-item').on('click', function() {
        selectedItems.forEach(function(item) {
            var gridItemHTML = '<div class="grid-item" data-id="' + item.id + '">' +
                '<button type="button" class="remove-item">Remove</button>' +
                '<p>' + item.title + '</p></div>';

            $('#prj-items-list').append(gridItemHTML);
        });

        // Update the hidden input field
        updateField();

        // Clear selected items after adding
        selectedItems = [];
        $('#add-prj-item .dropdown-toggle').text('Seleziona un post');
        $('#add-prj-item li').removeClass('selected').hide();
    });

    // Function to update the hidden field with the current IDs
    function updateField() {
        var ids = [];
        $('#prj-items-list .grid-item').each(function() {
            ids.push($(this).data('id'));
        });
        $('#prj_items_field').val(ids.join(','));
    }

    // Close dropdown when clicking outside
    $(document).on('click', function(event) {
        if (!$(event.target).closest('#add-prj-item').length) {
            $('#add-prj-item li').not('.dropdown-toggle').hide();
        }
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
                    var existingIds = $('#prj_items_field').val().split(',');
        
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
    </script>
    <?php
}
function prj_featured_image_metabox_custom_field($content, $post_id) {
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
//$alignment = get_post_meta(get_the_ID(), '_prj_image_alignment', true);
?>

<?php

add_filter( 'attachment_fields_to_edit', 'wpdude_add_custom_attachment_fields', 10, 2 );
function wpdude_add_custom_attachment_fields( $fields, $post ) {
    // Determine image orientation
    $orientation = wpdude_get_image_orientation( $post->ID );

    // Field for image alignment (Allineamento) with conditional options
    $fields['image_allineamento'] = array(
        'label' => 'Allineamento singolo',
        'input' => 'html',
        'html'  => wpdude_create_allineamento_select( $post->ID, $orientation ),
        'helps' => 'Seleziona l\'allineamento dell\'immagine'
    );
    // Checkbox for 'Has Single Page'
    $fields['has_single_page'] = array(
        'label' => 'Ha Pagina Singola',
        'input' => 'html',
        'html'  => wpdude_create_checkbox( $post->ID, 'has_single_page', 'Check if this image has a single page' ),
    );

    return $fields;
}

function wpdude_create_allineamento_select( $post_id, $orientation ) {
    $selected_value = get_post_meta( $post_id, 'image_allineamento', true );

    // Options based on image orientation
    $options = $orientation === 'vertical' ? array('destra', 'sinistra') : array('alto', 'basso');
    
    $html = '<select name="attachments[' . $post_id . '][image_allineamento]" id="attachments-' . $post_id . '-image_allineamento">';
    foreach ( $options as $option ) {
        $selected = selected( $selected_value, $option, false );
        $html .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( ucfirst($option) ) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

function wpdude_create_checkbox( $post_id, $meta_key, $description ) {
    $value = get_post_meta( $post_id, $meta_key, true );
    $checked = checked( 1, $value, false );
    $html = '<label><input type="checkbox" name="attachments[' . $post_id . '][' . $meta_key . ']" value="1" ' . $checked . '> ' . $description . '</label>';
    return $html;
}

add_filter( 'attachment_fields_to_save', 'wpdude_save_custom_attachment_fields', 10, 2 );
function wpdude_save_custom_attachment_fields( $post, $attachment ) {
    // Save 'image_allineamento' field
    if ( isset( $attachment['image_allineamento'] ) ) {
        update_post_meta( $post['ID'], 'image_allineamento', $attachment['image_allineamento'] );
    }

    // Save 'has_single_page' checkbox
    if ( isset( $attachment['has_single_page'] ) ) {
        update_post_meta( $post['ID'], 'has_single_page', '1' );
    } else {
        delete_post_meta( $post['ID'], 'has_single_page' );
    }

    return $post;
}

function wpdude_get_image_orientation( $post_id ) {
    $image_data = wp_get_attachment_metadata( $post_id );
    if ( !empty( $image_data ) && isset( $image_data['width'] ) && isset( $image_data['height'] ) ) {
        $width = $image_data['width'];
        $height = $image_data['height'];
        return $width > $height ? 'horizontal' : 'vertical';
    }
    return 'square';
}

// Aggiungi una nuova colonna alla lista degli attachment
add_filter('manage_media_columns', 'wpdude_add_has_single_page_column');
function wpdude_add_has_single_page_column($columns) {
    $columns['has_single_page'] = 'Ha Pagina Singola';
    return $columns;
}

// Popola la nuova colonna con i dati
add_action('manage_media_custom_column', 'wpdude_show_has_single_page_status', 10, 2);
function wpdude_show_has_single_page_status($column_name, $post_id) {
    if ('has_single_page' === $column_name) {
        $has_single = get_post_meta($post_id, 'has_single_page', true);
        echo $has_single ? 'Sì' : 'No';
    }
}

<?php
// Aggiungi azioni per gestire le chiamate AJAX sia per gli utenti loggati che per i non loggati
add_action('wp_ajax_load_posts', 'load_posts_by_ajax');
add_action('wp_ajax_nopriv_load_posts', 'load_posts_by_ajax');

function load_posts_by_ajax() {
        $post_ids = isset($_POST['prj_items']) ? $_POST['prj_items'] : array();

    $posts_data = array();
    foreach ($post_ids as $post_id) {
        // Assicurati che l'ID appartenga a un attachment
        if (get_post_type($post_id) === 'attachment') {
            $image_url = wp_get_attachment_url($post_id);
            $title = get_the_title($post_id);
            if ($image_url) { // Verifica che l'URL dell'immagine esista
                $posts_data[] = array(
                    'title' => $title,
                    'image' => $image_url,
                );
            }
        }
    }

    echo json_encode($posts_data);
    wp_die(); // Termina correttamente la chiamata AJAX
}

function enqueue_simple_lightbox() {
    // Enqueue Simple Lightbox CSS
    wp_enqueue_style('simple-lightbox-css', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.3/simple-lightbox.min.css');

    // Enqueue Simple Lightbox JS
    wp_enqueue_script('simple-lightbox-js', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.3/simple-lightbox.min.js', array('jquery'), null, true);

    // Se hai bisogno della versione jQuery di Simple Lightbox, puoi metterla in coda qui. Altrimenti, commenta la linea sottostante.
    wp_enqueue_script('simple-lightbox-jquery-js', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.3/simple-lightbox.jquery.min.js', array('jquery'), null, true);
    // Inserisce JavaScript personalizzato nel footer
    add_action('wp_footer', function() {
        ?>
        <script type="text/javascript">
        </script>
        <style>.sl-overlay{}.sl-wrapper, .sl-wrapper > *{z-index:300000000000000000000000000;}</style>
        <?php
    });
}
add_action('wp_enqueue_scripts', 'enqueue_simple_lightbox');

<?php
add_action('template_redirect', function() {
    // Controlla se siamo nell'area di login o amministrazione
    if (
        strpos($_SERVER['REQUEST_URI'], '/wp-admin/') !== false || 
        strpos($_SERVER['REQUEST_URI'], '/wp-login.php') !== false
    ) {
        return; // Non fare nulla per l'area admin o la pagina di login
    }

    // Escludi gli utenti autenticati
    if ( is_user_logged_in() || is_preview() ){
        return; // Non fare nulla per gli utenti autenticati
    }

    // Controlla se l'URL corrente è la homepage
    //if (is_front_page()) {
    //    wp_redirect('https://www.imlphotographer.com/new/', 301); // Reindirizzamento permanente
    //    exit;
    //}
    // Se l'utente NON è autenticato e siamo sulla homepage, reindirizza
    if ( !is_user_logged_in() && is_front_page() ) {
        wp_redirect('https://www.imlphotographer.com/new/', 301);
        exit;
    }
    if ( is_user_logged_in() && is_front_page() && is_preview() ) {
        wp_redirect('https://www.imlphotographer.com/homeIlaria', 301);
        exit;
    }
        if ( is_user_logged_in() && is_front_page()) {
        wp_redirect('https://www.imlphotographer.com/homeIlariaBBBB', 301);
        exit;
    }
});
