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

    echo '<div style="position:relative;">'; // Wrapper
    echo '<ul id="add-portfolio-item" class="portfolio-dropdown">';
    echo '<li class="dropdown-toggle">Seleziona un post</li>';
    echo '<div id="portfolio-loader-status" class="portfolio-loader-status">Caricamento immagini... <span id="portfolio-loader-count">0</span>%</div>';

    // List of selectable posts
    $selectable_posts = new WP_Query([
        'post_type'      => ['progetto', 'serie', 'attachment'], // Aggiungi altri post type se necessario
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'inherit'], // Attachments usually have 'inherit' status, others 'publish'
        'orderby'        => 'date',
        'order'          => 'DESC',
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
        echo '<li value="' . esc_attr($post_id) . '">';
        if ($thumbnail_url) {
            echo '<img src="" data-src="' . esc_url($thumbnail_url) . '" class="lazy-thumb" alt="">';
        }
        echo get_the_title();
        echo ' - ';
        if($post_type === 'attachment'){echo 'Foto';} else {echo $post_type;}
        echo '</li>';
    }
    }
    wp_reset_postdata();

    echo '</ul>';
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
    $output .= '<button type="button" class="remove-item">Remove</button>';
    $output .= '<div style="color: deeppink;">  '. $post_type .'</div>';
    $output .= '</div>';

    return $output;
}

function save_portfolio_meta_box_data($post_id) {
    // Verifica la validità del nonce
    if (!isset($_POST['portfolio_meta_box_nonce']) || !wp_verify_nonce($_POST['portfolio_meta_box_nonce'], 'portfolio_save_meta_box_data')) {
        return;
    }

    // Evita il salvataggio automatico
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    $portfolio_items_alignment = array(); // Array per memorizzare gli allineamenti degli elementi

    // Salva gli elementi associati, solo se ci sono elementi
    if (isset($_POST['portfolio_items']) && !empty($_POST['portfolio_items'])) {
        $portfolio_items = explode(',', sanitize_text_field($_POST['portfolio_items']));
        
        // Assicurati che ci siano elementi validi nell'array
        $portfolio_items = array_filter($portfolio_items, function($item) {
            return !empty($item); // Filtra eventuali elementi vuoti
        });

        if (!empty($portfolio_items)) {
            update_post_meta($post_id, 'portfolio_items', $portfolio_items);
        } else {
            delete_post_meta($post_id, 'portfolio_items'); // Rimuovi il meta se non ci sono più elementi
        }
    } else {
        delete_post_meta($post_id, 'portfolio_items'); // Rimuovi il meta se non ci sono elementi inviati
    }

    // Salva l'allineamento e imposta il parent per ogni elemento, solo se ci sono dati
    if (isset($_POST['item_alignment']) && !empty($_POST['item_alignment'])) {
        foreach ($_POST['item_alignment'] as $item_id => $alignment) {
            if (!empty($item_id) && !empty($alignment)) {
                // Salva l'allineamento dell'elemento
                update_post_meta($item_id, 'portfolio_item_alignment', sanitize_text_field($alignment));
                $portfolio_items_alignment[$item_id] = sanitize_text_field($alignment); // Aggiungi all'array
            }
        }
    }

    // Salva l'array di allineamenti come meta field, solo se non è vuoto
    if (!empty($portfolio_items_alignment)) {
        update_post_meta($post_id, 'portfolio_items_alignment', $portfolio_items_alignment);
    } else {
        delete_post_meta($post_id, 'portfolio_items_alignment'); // Rimuovi il meta se vuoto
    }
}

add_action('save_post', 'save_portfolio_meta_box_data');

// Include lo stile CSS per gestire l'aspetto della griglia e dei pulsanti
add_action('admin_enqueue_scripts', 'portfolio_enqueue_admin_styles');
function portfolio_enqueue_admin_styles() {
    // Load only on portfolio post type
    $screen = get_current_screen();
    if ($screen->post_type === 'portfolio') {
        wp_enqueue_style('portfolio-admin-style', IML_PLUGIN_URL . 'includes/post-types/portfolio/admin-style.css', array(), '1.0');
    }
}

// Include lo script JavaScript per rendere la lista "sortable" e gestire l'aggiunta e la rimozione
add_action('admin_footer', 'portfolio_admin_scripts');
function portfolio_admin_scripts() {
    $screen = get_current_screen();
    if ($screen->post_type !== 'portfolio') return;
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
                    updatePortfolioField();
                }
            });

            // Function to update the hidden field with the current IDs
            function updatePortfolioField() {
                var ids = [];
                $('#portfolio-items-list .grid-item').each(function() {
                    var id = $(this).data('id');
                    if (id) ids.push(id);
                });
                
                $field.val(ids.join(','));
            }

            // Gestisci il click del pulsante di rimozione
            $list.on('click', '.remove-item', function() {
                $(this).closest('.grid-item').remove();
                updatePortfolioField();
            });

            // Queue-based Lazy Loader
            var loadQueue = [];
            var activeLoads = 0;
            var maxConcurrent = 6; // Browser typically allows 6 connections per domain
            var isQueueRunning = false;
            var totalImages = 0;
            var loadedCount = 0;

            // Toggle dropdown on click
            $('#add-portfolio-item').on('click', '.dropdown-toggle', function(event) {
                var $parent = $(this).parent();
                $parent.toggleClass('active');
                
                if ($parent.hasClass('active')) {
                    startImageLoading();
                }
                event.stopPropagation(); 
            });

            function startImageLoading() {
                // Only start if not already running and there are images to load
                if (isQueueRunning) return;
                
                // Initialize queue with all unloaded images
                var $unloaded = $('#add-portfolio-item .lazy-thumb').filter(function() {
                    return !$(this).attr('src');
                });
                
                if ($unloaded.length === 0) return; // All done
                
                console.log('Starting queue for ' + $unloaded.length + ' images...');
                $('#portfolio-loader-status').addClass('visible');
                
                // Reset counts for progress bar (based on total in list)
                totalImages = $('#add-portfolio-item .lazy-thumb').length;
                loadedCount = $('#add-portfolio-item .lazy-thumb[src]').length;
                updateProgress();

                // Convert jQuery object to array for the queue
                loadQueue = $unloaded.toArray();
                isQueueRunning = true;
                
                // Kick off the initial batch
                processQueue();
            }

            function processQueue() {
                // Stop if queue empty
                if (loadQueue.length === 0) {
                    if (activeLoads === 0) {
                        isQueueRunning = false;
                        $('#portfolio-loader-status').removeClass('visible');
                        console.log('All images loaded.');
                    }
                    return;
                }

                // Fill up the concurrent slots
                while (activeLoads < maxConcurrent && loadQueue.length > 0) {
                    var img = loadQueue.shift();
                    var $img = $(img);
                    
                    activeLoads++;
                    
                    $img.off('load error');
                    
                    $img.on('load error', function() {
                        activeLoads--;
                        loadedCount++;
                        updateProgress();
                        // Trigger next immediately
                        processQueue();
                    });
                    
                    // Start load
                    $img.attr('src', $img.data('src'));
                }
            }
            
            function updateProgress() {
                var percent = Math.round((loadedCount / totalImages) * 100);
                $('#portfolio-loader-count').text(percent);
                
                if (loadedCount >= totalImages) {
                     $('#portfolio-loader-status').text('Caricamento completato!');
                     setTimeout(function() {
                         $('#portfolio-loader-status').removeClass('visible');
                     }, 2000);
                }
            }

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

                // Update toggle text summary
                var displayText = selectedItems.map(function(item) {
                     // Simplify title for display
                     return item.title.substring(0, 15) + (item.title.length>15?'...':'');
                }).join(', ');
                $('#add-portfolio-item .dropdown-toggle').text(displayText || 'Seleziona foto (click to close)');
            });

            // Close dropdown when clicking outside
            $(document).on('click', function(event) {
                if (!$(event.target).closest('#add-portfolio-item').length) {
                    $('#add-portfolio-item').removeClass('active');
                }
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
                updatePortfolioField();

                // Clear selected items after adding
                selectedItems = [];
                $('#add-portfolio-item .dropdown-toggle').text('Seleziona un post');
                $('#add-portfolio-item li').removeClass('selected');
                $('#add-portfolio-item').removeClass('active');
            });
        });
    </script>
    <?php
}
