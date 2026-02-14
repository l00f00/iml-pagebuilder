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

    echo '<div id="add-homepage-item" class="homepage-dropdown">';
    echo '<div class="dropdown-toggle">Seleziona un post</div>';
    echo '<div id="homepage-loader-status" class="homepage-loader-status">Caricamento immagini... <span id="homepage-loader-count">0</span>%</div>';

    // List of selectable posts
    $selectable_posts = new WP_Query([
        'post_type'      => ['progetto', 'portfolio', 'serie', 'attachment'],
        'posts_per_page' => -1,
        'post_status'    => ['publish', 'inherit'], // Include inherit for attachments
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    if ($selectable_posts->have_posts()) {
        while ($selectable_posts->have_posts()) {
            $selectable_posts->the_post();
            $post_id = get_the_ID();
            $post_type = get_post_type($post_id);
            $thumbnail_url = '';

            if ($post_type === 'attachment') {
                 $thumbnail_url = wp_get_attachment_image_url($post_id, 'thumbnail');
            } else {
                 $thumbnail_url = get_the_post_thumbnail_url($post_id, 'thumbnail');
            }

            echo '<div class="item-preview" data-id="' . $post_id . '" data-title="' . esc_attr(get_the_title()) . '">';
            if ($thumbnail_url) {
                echo '<img src="" data-src="' . esc_url($thumbnail_url) . '" class="lazy-thumb" alt="">';
            }
            echo '<div class="item-info">';
            echo '<span class="item-title">' . get_the_title() . '</span>';
            $type_label = ($post_type === 'attachment' ? 'Foto' : $post_type);
            echo '<span class="item-type type-' . esc_attr($post_type) . '">' . $type_label . '</span>';
            echo '</div>';
            echo '</div>';
        }
        wp_reset_postdata();
    }
    echo '</div>';
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

                // Queue-based Lazy Loader
                var loadQueue = [];
                var activeLoads = 0;
                var maxConcurrent = 6;
                var isQueueRunning = false;
                var totalImages = 0;
                var loadedCount = 0;

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
                    var $parent = $(this).parent();
                    $parent.toggleClass('active');
                    
                    if ($parent.hasClass('active')) {
                        startImageLoading();
                    }
                    event.stopPropagation(); 
                }); 

                function startImageLoading() {
                    if (isQueueRunning) return;
                    
                    var $unloaded = $('#add-homepage-item .lazy-thumb').filter(function() {
                        return !$(this).attr('src');
                    });
                    
                    if ($unloaded.length === 0) return;
                    
                    console.log('Starting queue for ' + $unloaded.length + ' images...');
                    $('#homepage-loader-status').addClass('visible');
                    
                    totalImages = $('#add-homepage-item .lazy-thumb').length;
                    loadedCount = $('#add-homepage-item .lazy-thumb[src]').length;
                    updateProgress();

                    loadQueue = $unloaded.toArray();
                    isQueueRunning = true;
                    processQueue();
                }

                function processQueue() {
                    if (loadQueue.length === 0) {
                        if (activeLoads === 0) {
                            isQueueRunning = false;
                            $('#homepage-loader-status').removeClass('visible');
                        }
                        return;
                    }

                    while (activeLoads < maxConcurrent && loadQueue.length > 0) {
                        var img = loadQueue.shift();
                        var $img = $(img);
                        activeLoads++;
                        $img.off('load error');
                        $img.on('load error', function() {
                            activeLoads--;
                            loadedCount++;
                            updateProgress();
                            processQueue();
                        });
                        $img.attr('src', $img.data('src'));
                    }
                }
                
                function updateProgress() {
                    var percent = Math.round((loadedCount / totalImages) * 100);
                    $('#homepage-loader-count').text(percent);
                    
                    if (loadedCount >= totalImages) {
                         $('#homepage-loader-status').text('Caricamento completato!');
                         setTimeout(function() {
                             $('#homepage-loader-status').removeClass('visible');
                         }, 2000);
                    }
                }

                // Handle selection
                $('#add-homepage-item').on('click', '.item-preview', function(event) { 
                    var postId = $(this).data('id'); 
                    var selectedTitle = $(this).data('title'); 

                    var index = selectedItems.findIndex(item => item.id == postId); 
                    if (index > -1) { 
                        selectedItems.splice(index, 1); 
                        $(this).removeClass('selected'); 
                    } else { 
                        selectedItems.push({id: postId, title: selectedTitle}); 
                        $(this).addClass('selected'); 
                    } 

                    var displayText = selectedItems.map(item => item.title).join(', '); 
                    // Truncate for display
                    if (displayText.length > 50) displayText = displayText.substring(0, 50) + '...';
                    
                    $('#add-homepage-item .dropdown-toggle').text(displayText || 'Seleziona post (click to close)'); 
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
                    $('#add-homepage-item .dropdown-toggle').text('Seleziona un post'); 
                    $('#add-homepage-item .item-preview').removeClass('selected'); 
                    $('#add-homepage-item').removeClass('active');
                }); 

                $(document).on('click', function(event) { 
                    if (!$(event.target).closest('#add-homepage-item').length) { 
                        $('#add-homepage-item').removeClass('active');
                    } 
                }); 
            }); 
        </script> 
        <?php 
    } 
} 

// Enqueue Admin CSS 
add_action('admin_enqueue_scripts', 'homepage_enqueue_admin_styles'); 
function homepage_enqueue_admin_styles() { 
    global $post; 
    if ($post && $post->ID == get_option('page_on_front')) { 
        wp_enqueue_style('homepage-admin-style', IML_PLUGIN_URL . 'includes/post-types/homepage/admin-style.css', array(), '1.0');
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
