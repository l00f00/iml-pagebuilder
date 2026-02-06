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
    wp_enqueue_script('lottie-web', 'https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js', array(), '5.12.2', true);

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

/**
 * Homepage Lottie Preloader (Desktop Only)
 * Injected via wp_footer to ensure it works even if wp_body_open is missing,
 * but with high z-index to cover everything.
 */
function iml_homepage_lottie_preloader() {
    // Esegui solo in homepage
    if ( ! is_front_page() ) {
        return;
    }
    
    // URL del file JSON dell'animazione
    $lottie_url = IML_PLUGIN_URL . 'frontend/assets/ILM_Website-Logo-nero.json';
    ?>
    <!-- Lottie Preloader HTML -->
    <div id="lottie-overlay" aria-hidden="true" style="display:none;">
        <div id="lottie-container"></div>
    </div>

    <style>
        /* Lottie Preloader CSS */
        #lottie-overlay {
            position: fixed;
            inset: 0;
            background: #ffffff; /* Sfondo bianco come richiesto ("andiamo a bianco") */
            z-index: 99999999; /* Z-index molto alto */
            display: none; /* Nascosto di default, attivato via JS se desktop */
            align-items: center;
            justify-content: center;
            pointer-events: all; /* Blocca i click durante il caricamento */
        }
        #lottie-container {
            width: 100vw;
            height: 100vh;
        }
        html.lottie-active, body.lottie-active {
            overflow: hidden !important; /* Blocca lo scroll */
        }
    </style>
    
    <!-- Script removed: Lottie is now enqueued via wp_enqueue_scripts -->
    <script>
    (function() {
        // Configurazione
        var lottieJSON = '<?php echo $lottie_url; ?>'; 
        
        // Check Desktop (min-width 1024px)
        var isDesktop = window.matchMedia('(min-width: 1024px)').matches;
        var overlay = document.getElementById('lottie-overlay');
        
        // Se non è desktop, assicurati che sia nascosto ed esci
        if (!isDesktop) {
            if (overlay) overlay.style.display = 'none';
            return;
        }

        // Se è desktop, mostra l'overlay e blocca lo scroll
        if (overlay) overlay.style.display = 'flex';
        document.documentElement.classList.add('lottie-active');
        document.body.classList.add('lottie-active');
        
        var container = document.getElementById('lottie-container');
        var done = false;

        function reveal() {
            if (done) return;
            done = true;
            
            // Fade out
            if (overlay) {
                // Imposta la transizione per durare 1 secondo (1000ms)
                overlay.style.transition = 'opacity 1s ease';
                overlay.style.opacity = '0';
                
                // Rimuovi l'elemento dal DOM dopo che la transizione è completata (1000ms)
                setTimeout(function() {
                    overlay.style.display = 'none';
                    document.documentElement.classList.remove('lottie-active');
                    document.body.classList.remove('lottie-active');
                }, 1000);
            } else {
                document.documentElement.classList.remove('lottie-active');
                document.body.classList.remove('lottie-active');
            }
        }

        // IMPOSTAZIONE TEMPISTICA FISSA:
        // L'animazione dura 7 secondi.
        // Il fade-out deve iniziare al 6° secondo (6000ms) e durare 1 secondo.
        // Quindi al termine del fade (7000ms) tutto sarà finito.
        var fadeStartTime = 6000; 

        // Avvia il reveal (fade-out) esattamente a 6000ms
        var timeoutId = setTimeout(reveal, fadeStartTime);

        try {
            var anim = lottie.loadAnimation({
                container: container,
                renderer: 'svg',
                loop: false,
                autoplay: true,
                path: lottieJSON
            });

            // Nota: Rimuoviamo la logica 'complete' automatica per rispettare rigorosamente 
            // la richiesta di timing (6s start fade, 7s end), a meno che l'animazione non fallisca.
            // Se l'animazione dura esattamente 7s, il fade inizierà mentre l'ultimo secondo di animazione gira,
            // creando l'effetto di dissolvenza finale coordinato.

            // Gestione errori (es. file json mancante) - in questo caso sblocchiamo subito
            anim.addEventListener('data_failed', function() {
                console.warn('Lottie data failed to load');
                clearTimeout(timeoutId);
                reveal();
            });
            anim.addEventListener('error', function() {
                console.warn('Lottie error');
                clearTimeout(timeoutId);
                reveal();
            });

        } catch (e) {
            console.error('Lottie init error:', e);
            clearTimeout(timeoutId);
            reveal();
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'iml_homepage_lottie_preloader');
