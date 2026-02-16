<?php
/**
 * Shortcode: [iml_homepage_grid] and Preloader logic
 */

if (!defined('ABSPATH')) {
    exit;
}

// Shortcode: [iml_homepage_grid]
add_shortcode('iml_homepage_grid', 'iml_render_homepage_grid');

function iml_render_homepage_grid($atts) {
    ob_start();

    // Get the Homepage ID
    $homepage_id = get_option('page_on_front');
    
    // Retrieve the items and their alignment from the meta field
    $post_ids = get_post_meta($homepage_id, 'homepage_items_alignment', true);

    if (empty($post_ids) || !is_array($post_ids)) {
        return '<p>Nessun elemento configurato per la griglia.</p>';
    }

    echo '<div id="grid-wrapper">';
    echo '<div id="custom-post-grid">';

    foreach ($post_ids as $post_id => $alignment) {
        $post_type = get_post_type($post_id);
        $post_obj = get_post($post_id);
        
        if (!$post_obj) continue;

        setup_postdata($post_obj);

        if ($post_type === 'progetto' || $post_type === 'serie' || $post_type === 'portfolio') {
            $categories = get_the_terms($post_id, 'category');
            $tags = get_the_terms($post_id, 'post_tag');
            $title = get_the_title($post_id);
            $the_thumb = get_the_post_thumbnail($post_id, 'full');
            
            echo '<a href="' . esc_url(get_permalink($post_id)) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . '" data-id="' . esc_attr($post_id) . '">';
            echo '<div class="info-overlay">';
            echo '<div class="categories-tags">';
            // Categories and tags loop commented out in original code, kept as logic placeholder
            echo '</div>';
            echo '<div class="year-title">';
            echo '<span class="title">' . esc_html($title) . '</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="image-wrapper">' . $the_thumb . '</div>';
            echo '</a>';
            
        } elseif ($post_type === 'attachment') {
            $title = get_the_title($post_id);
            $single_page_true = get_post_meta($post_id, 'has_single_page', true);
            
            // Logic to determine the link
            $parent_id = wp_get_post_parent_id($post_id);
            $parent_type = $parent_id ? get_post_type($parent_id) : null;
            $href = '';

            if ($parent_id) {
                $href = get_permalink($parent_id);
                $title = get_the_title($parent_id);
            } elseif ($single_page_true == '1' && $parent_id == 0) {
                $href = get_permalink($post_id);
            } else {
                $href = wp_get_attachment_url($post_id); 
            }

            $the_thumb = wp_get_attachment_image($post_id, 'full');
            
            echo '<a href="' . esc_url($href) . '" class="grid-item fotoContainer ' . esc_attr($alignment) . ' ' . esc_attr($post_type) . ' ' . esc_attr($parent_type) . '" data-id="' . esc_attr($post_id) . '">';
            echo '<div class="info-overlay">';
            echo '<div class="year-title" style="margin-top: auto;">';
            echo '<span class="title">' . esc_html($title) . '</span>';
            echo '</div>';
            echo '</div>';
            echo '<div class="image-wrapper">' . $the_thumb . '</div>';
            echo '</a>';
        }

        wp_reset_postdata();
    }

    echo '</div>'; // End #custom-post-grid
    echo '</div>'; // End #grid-wrapper

    return ob_get_clean();
}

/**
 * Preload Lottie assets for performance
 */
add_action('wp_head', function() {
    if ( is_front_page() && !is_user_logged_in() ) {
        $lottie_url = IML_PLUGIN_URL . 'frontend/assets/new.json';
        echo '<link rel="preload" href="' . esc_url($lottie_url) . '" as="fetch" crossorigin="anonymous">';
    }
}, 5);

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
    $lottie_url = IML_PLUGIN_URL . 'frontend/assets/new.json';
    ?>
    <!-- Lottie Preloader HTML -->
    <div id="lottie-overlay" aria-hidden="true" style="display:none; opacity: 1;">
        <div id="lottie-container"></div>
    </div>

    <style>
        /* Lottie Preloader CSS */
        #lottie-overlay {
            position: fixed;
            inset: 0;
            background: transparent; /* Sfondo trasparente (richiesta utente: "niente sfondo") */
            z-index: 99999999; /* Z-index molto alto */
            display: none; /* Nascosto di default, attivato via JS se desktop */
            align-items: center;
            justify-content: center;
            pointer-events: none; /* Permetti click sotto (richiesta utente implicita con "niente sfondo") */
            opacity: 1; /* Assicuriamo opacità iniziale */
        }
        #lottie-container {
            width: 100vw;
            height: 100vh;
        }
        html.lottie-active, body.lottie-active {
            /* overflow: hidden !important; */ /* Disabilitato blocco scroll (opzionale se sfondo è trasparente) */
        }
    </style>
    
    <!-- Script removed: Lottie is now enqueued via wp_enqueue_scripts -->
    <script>
    (function() {
        var startTime = Date.now();
        console.log('🐞 Lottie START time:', 0, 'ms (Absolute:', startTime, ')');

        // Configurazione
        var lottieJSON = '<?php echo $lottie_url; ?>'; 
        
        // Check Desktop (min-width 1024px)
        var isDesktop = window.matchMedia('(min-width: 1024px)').matches;
        var overlay = document.getElementById('lottie-overlay');
        
        // Se non è desktop, assicurati che sia nascosto ed esci
        if (!isDesktop) {
            console.log('Lottie: Not desktop, skipping.');
            if (overlay) overlay.style.display = 'none';
            return;
        }

        // Se è desktop, mostra l'overlay
        if (overlay) {
            overlay.style.display = 'flex';
            overlay.style.opacity = '1'; // Force opacity
        }
        // document.documentElement.classList.add('lottie-active');
        // document.body.classList.add('lottie-active');
        
        var container = document.getElementById('lottie-container');
        var done = false;

        function reveal(reason) {
            var now = Date.now();
            console.log('🐞 Lottie END triggered by:', reason, '| Time elapsed:', now - startTime, 'ms');
            
            if (done) return;
            done = true;
            
            // Rimozione immediata (richiesta utente: "niente sfumatura")
            if (overlay) {
                overlay.style.display = 'none';
                overlay.remove(); // Rimuovi completamente dal DOM
                // document.documentElement.classList.remove('lottie-active');
                // document.body.classList.remove('lottie-active');
                console.log('🐞 Lottie DOM Removed completely at:', Date.now() - startTime, 'ms');
            }
        }

        // FALLBACK DI SICUREZZA LUNGO: 15 secondi
        // Questo scatta solo se l'evento 'complete' fallisce per qualche motivo
        var timeoutId = setTimeout(function() {
            reveal('fallback_timeout_15s');
        }, 15000);

        try {
            console.log('Initializing Lottie animation with path:', lottieJSON);
            var anim = lottie.loadAnimation({
                container: container,
                renderer: 'svg',
                loop: false,
                autoplay: false, // Autoplay disabilitato per gestire start manuale
                path: lottieJSON,
                rendererSettings: {
                    preserveAspectRatio: 'none' // STRETCH to fill container
                }
            });

            // Log durata animazione e avvio dal frame desiderato
            anim.addEventListener('DOMLoaded', function() {
                console.log('🐞 Lottie DOM Loaded. Total frames:', anim.totalFrames, 'Frame rate:', anim.frameRate);
                var duration = anim.totalFrames / anim.frameRate;
                console.log('🐞 Estimated duration (s):', duration);

                // RICHIESTA UTENTE: Saltare i primi 15 frame
                // Nota: 15 frame @ 24fps sono circa 0.6 secondi.
                var startFrame = 15; 
                
                // Controllo di sicurezza: se 100 è troppo, partiamo da 0 o da un valore più basso (es. 12 frame = 0.5s)
                if (startFrame >= anim.totalFrames) {
                     console.warn('🐞 Start frame 100 is > total frames. Resetting to 0.');
                     startFrame = 0;
                }

                console.log('🐞 Starting animation from frame:', startFrame);
                // playSegments accetta [inizio, fine], true = force immediate render
                anim.playSegments([startFrame, anim.totalFrames], true);
            });
            
            // Quando l'animazione è COMPLETATA (fine dei 7s), avvia il fade-out
            anim.addEventListener('complete', function() {
                 console.log('🐞 Lottie Animation Complete Event fired at:', Date.now() - startTime, 'ms');
                 clearTimeout(timeoutId); // Annulla il fallback
                 reveal('complete_event');
            });

            // Gestione errori
            anim.addEventListener('data_failed', function() {
                console.warn('Lottie data failed to load');
                clearTimeout(timeoutId);
                reveal('data_failed');
            });
            anim.addEventListener('error', function() {
                console.warn('Lottie error');
                clearTimeout(timeoutId);
                reveal('error');
            });

        } catch (e) {
            console.error('Lottie init error:', e);
            clearTimeout(timeoutId);
            reveal('catch_error');
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'iml_homepage_lottie_preloader');
