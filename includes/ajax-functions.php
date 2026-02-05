<?php
/**
 * AJAX Functions.
 */

if (!defined('ABSPATH')) {
    exit;
}

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
