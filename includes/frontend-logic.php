<?php
/**
 * Frontend logic (redirects, scripts).
 */

if (!defined('ABSPATH')) {
    exit;
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
