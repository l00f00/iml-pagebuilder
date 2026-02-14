<?php
/**
 * Frontend logic (redirects, scripts, styles).
 */

if (!defined('ABSPATH')) {
    exit;
}

function iml_enqueue_frontend_scripts() {
    // Enqueue Simple Lightbox CSS
    wp_enqueue_style('simple-lightbox-css', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.3/simple-lightbox.min.css');

    // Enqueue Simple Lightbox JS
    wp_enqueue_script('simple-lightbox-js', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.3/simple-lightbox.min.js', array('jquery'), null, true);

    // Enqueue jQuery version if needed (original code had it)
    wp_enqueue_script('simple-lightbox-jquery-js', 'https://cdnjs.cloudflare.com/ajax/libs/simplelightbox/2.14.3/simple-lightbox.jquery.min.js', array('jquery'), null, true);

    // Enqueue Custom Frontend CSS
    wp_enqueue_style('iml-frontend-style', IML_PLUGIN_URL . 'frontend/style.css', array(), '1.0');

    // Enqueue Custom Frontend JS
    wp_enqueue_script('iml-frontend-script', IML_PLUGIN_URL . 'frontend/script.js', array('jquery', 'simple-lightbox-jquery-js'), '1.0', true);

    // Enqueue Lottie Web globally (needed for Homepage preloader and Header animations on all pages)
    // Loaded in HEAD (false) to ensure it is available for the critical preloader script in wp_footer
    wp_enqueue_script('lottie-web', 'https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js', array(), '5.12.2', false);

    // Custom CSS for Lightbox Z-Index
    wp_add_inline_style('simple-lightbox-css', '.sl-overlay{}.sl-wrapper, .sl-wrapper > *{z-index:300000000000000000000000000;}');
}
add_action('wp_enqueue_scripts', 'iml_enqueue_frontend_scripts');

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
