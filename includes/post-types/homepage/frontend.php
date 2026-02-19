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
        $custom_json = get_option('iml_intro_animation_json');
        $default_json = IML_PLUGIN_URL . 'frontend/assets/new.json';
        $lottie_url = $custom_json ? $custom_json : $default_json;
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
    $custom_json = get_option('iml_intro_animation_json');
    $default_json = IML_PLUGIN_URL . 'frontend/assets/new.json';
    $lottie_url = $custom_json ? $custom_json : $default_json;
    ?>
    <!-- Lottie Preloader HTML -->
    <div id="lottie-overlay" aria-hidden="true" style="display:none; opacity: 1;">
        <div id="lottie-container"></div>
    </div>

    <style>
        /* Lottie Preloader CSS */
        #lottie-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh; /* Usa viewport height per coprire lo schermo iniziale */
            background: rgba(255, 255, 255, 0.1); /* Richiesta utente: bianco 0.1 */
            z-index: 99999999;
            display: none;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            opacity: 1;
            mix-blend-mode: exclusion;
        }
        #lottie-container {
            width: 100%;
            height: 100%;
            filter: invert(1); /* Richiesta utente: inverti colore lottie */
        }
        /* Static SVG elements initially hidden */
        #svg-container svg, .logoalcentro svg {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.1s ease-in;
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
            // Ensure static elements are visible if Lottie is skipped
            var statics = document.querySelectorAll('#svg-container svg, .logoalcentro svg');
            statics.forEach(function(el) { el.style.opacity = 1; el.style.visibility = 'visible'; });
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
            
            // Show static elements before removing lottie
            var statics = document.querySelectorAll('#svg-container svg, .logoalcentro svg');
            statics.forEach(function(el) { 
                el.style.visibility = 'visible';
                el.style.opacity = '1'; 
            });
            
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

        var anim; // Declare anim here for scope visibility in debugLottie
        
        // --- SYNC LOGIC CONFIGURATION ---
        // Imposta a true per attivare il riposizionamento forzato dei layer Lottie
        // affinché coincidano con gli elementi statici HTML.
        var enableSync = true; 
        var debugSync = false; // Logga avvisi in console se false

        // Mappa di corrispondenza: NOME LAYER LOTTIE => SELETTORE HTML STATICO
        // Assicurati che il JSON Lottie sia stato esportato con l'opzione "Include Layer Names" (Bodymovin/LottieFiles)
        // in modo che i gruppi <g> abbiano id="NOME" o class="NOME".
        const map = [
           { lottie: "1072", html: "#staticIlaria" },
           { lottie: "1069", html: "#staticMagliocchetti" },
           { lottie: "1070", html: "#staticLombi" },
           { lottie: "1071", html: "#staticLogoAlCentro" }
        ];

        /**
         * Funzione che sincronizza la posizione dei layer Lottie con gli elementi HTML statici.
         * Viene chiamata ad ogni frame dell'animazione per correggere la posizione in tempo reale.
         */
        function syncElements() {
           if (!enableSync) return;

           // Trova l'elemento SVG generato da Lottie
           var lottieSVG = container.querySelector('svg');
           if (!lottieSVG) return;

           map.forEach(function(item) {
               // 1. TROVA IL LAYER LOTTIE
               // Cerca un gruppo <g> che abbia ID, Classe o Aria-Label uguale al nome mappato.
               var layerName = item.lottie;
               var lottieLayer = lottieSVG.querySelector('g[id="' + layerName + '"]') || 
                                 lottieSVG.querySelector('g[class="' + layerName + '"]') ||
                                 lottieSVG.querySelector('g[aria-label="' + layerName + '"]');

               if (!lottieLayer) {
                   if (debugSync) console.warn('Sync: Layer Lottie non trovato:', layerName);
                   return;
               }

               // 2. TROVA L'ELEMENTO HTML STATICO
               var htmlEl = document.querySelector(item.html);
               if (!htmlEl) {
                   if (debugSync) console.warn('Sync: Elemento HTML statico non trovato:', item.html);
                   return;
               }

               // 3. CALCOLA LE POSIZIONI (BoundingBox)
               // Ottiene le coordinate e dimensioni attuali in pixel rispetto alla viewport
               var layerRect = lottieLayer.getBoundingClientRect();
               var targetRect = htmlEl.getBoundingClientRect();

               // 4. CALCOLA IL DELTA (Differenza)
               // Calcoliamo quanto dobbiamo spostare il layer Lottie per sovrapporlo al target.
               // Nota: Se applichiamo la trasformazione, il getBoundingClientRect del layer cambierà al frame successivo.
               // Per evitare loop o drift, l'ideale è calcolare il delta rispetto alla posizione "senza override" 
               // oppure resettare il transform prima di misurare (costoso).
               // APPROCCIO SEMPLIFICATO: 
               // Se l'animazione Lottie porta il layer VICINO al target, questo script corregge l'errore fine.
               // Usiamo 'transform' CSS che si applica sopra la trasformazione SVG interna.
               
               var dx = targetRect.left - layerRect.left;
               var dy = targetRect.top - layerRect.top;
               
               // Se la differenza è minima (< 0.5px), evitiamo calcoli inutili (jitter fix)
               if (Math.abs(dx) < 0.5 && Math.abs(dy) < 0.5) return;

               // 5. APPLICA LA TRASFORMAZIONE
               // Usiamo translate3d per performance.
               // Importante: Questo sposta il layer visivamente.
               // ATTENZIONE: Se il layer Lottie si sta muovendo velocemente, questo potrebbe creare un effetto di "agganciamento"
               // o se il delta è grande, il layer "salterà" sulla posizione statica.
               // Se l'obiettivo è solo il match FINALE, questa logica andrebbe eseguita solo alla fine.
               // Se l'obiettivo è che il layer "segua" lo statico (o viceversa), questo va bene.
               
               // Recupera la trasformazione corrente (se già applicata da noi in frame precedenti) per sommare?
               // No, il getBoundingClientRect tiene conto delle trasformazioni CSS attive.
               // Quindi dx/dy sono il "residuo" da correggere.
               // Però applicare style.transform sovrascrive il precedente style.transform.
               // Quindi dobbiamo mantenere uno stato o accumulare? 
               // Se applichiamo transform: translate(dx, dy), al prossimo frame il rect si sarà spostato di dx, dy.
               // Quindi il nuovo dx sarà 0.
               // Ma se Lottie muove il layer internamente, il rect cambia.
               
               // Esempio: 
               // Frame 1: Lottie a 100, Target a 200. dx = +100. Apply translate(100). Visivamente a 200.
               // Frame 2: Lottie si muove a 110 (interno). CSS translate(100) ancora attivo? No, Lottie ridisegna?
               // Lottie ridisegna gli attributi SVG, ma NON lo style CSS inline (solitamente).
               // Quindi il translate(100) resta. Posizione visiva: 110 + 100 = 210. Target a 200.
               // Nuovo dx = 200 - 210 = -10.
               // Apply translate(-10)? No, sovrascrive translate(100).
               // Quindi il layer salta a 110 - 10 = 100. Sbagliato.
               
               // SOLUZIONE CORRETTA PER SYNC CONTINUO:
               // Dobbiamo leggere la trasformazione CSS attuale, parsare i valori X/Y, e sommare il nuovo delta.
               // Oppure, più semplice: Rimuovere temporaneamente il transform, misurare, calcolare il delta TOTALE, riapplicare.
               
               var currentTransform = lottieLayer.style.transform;
               lottieLayer.style.transform = ''; // Reset temporaneo per misurare la posizione "nativa" Lottie
               
               var nativeRect = lottieLayer.getBoundingClientRect();
               var totalDx = targetRect.left - nativeRect.left;
               var totalDy = targetRect.top - nativeRect.top;
               
               lottieLayer.style.transform = 'translate3d(' + totalDx + 'px, ' + totalDy + 'px, 0)';
               
               // Opzionale: Sync Scala (se necessario)
               // var scaleX = targetRect.width / nativeRect.width;
               // var scaleY = targetRect.height / nativeRect.height;
               // lottieLayer.style.transform += ' scale(' + scaleX + ',' + scaleY + ')';
           });
        }
        
        // Helper per testare trasformazioni manuali da console
        window.testTransform = function(layerId, x, y) {
            var lottieSVG = container.querySelector('svg');
            var layer = lottieSVG.querySelector('g[id="' + layerId + '"]');
            if (layer) {
                layer.style.transform = 'translate3d(' + x + 'px, ' + y + 'px, 0)';
                console.log('Applied transform to ' + layerId + ': ' + layer.style.transform);
            } else {
                console.warn('Layer ' + layerId + ' not found');
            }
        };

        // DEBUG FUNCTION: Expose to window to allow manual replay
        window.resyncLottie = function(force) {
            console.log('🐞 Manual Resync Triggered');
            var originalEnable = enableSync;
            if (force) enableSync = true;
            syncElements();
            if (force) enableSync = originalEnable;
        };

        // --- TOOL DI POSIZIONAMENTO MANUALE ---
        // Attiva questa modalità da console con: window.enableManualDrag()
        // Trascina i layer Lottie ("ILARIA", "LOGO", ecc.) e rilascia per vedere il transform CSS in console.
        window.enableManualDrag = function() {
            console.log('🐞 MANUAL DRAG ENABLED: Trascina i layer Lottie per posizionarli.');
            console.log('Il valore "transform" finale verrà stampato in console al rilascio.');
            
            // Disabilita il sync automatico per non interferire
            enableSync = false;
            
            var lottieSVG = container.querySelector('svg');
            if (!lottieSVG) { console.warn('SVG non trovato'); return; }

            map.forEach(function(item) {
                var layerName = item.lottie;
                var layer = lottieSVG.querySelector('g[id="' + layerName + '"]') || 
                            lottieSVG.querySelector('g[class="' + layerName + '"]') ||
                            lottieSVG.querySelector('g[aria-label="' + layerName + '"]');

                if (!layer) return;

                // Stile cursore
                layer.style.cursor = 'move';
                layer.style.pointerEvents = 'all'; // Assicura che riceva eventi mouse

                // Variabili per drag
                var isDragging = false;
                var startX, startY;
                var initialTransform = layer.style.transform || '';
                // Parse initial translate if exists (simple check)
                var currentX = 0, currentY = 0;
                
                // Helper per estrarre translate x,y corrente (molto grezzo)
                var match = initialTransform.match(/translate3d\(([^p]+)px,\s*([^p]+)px/);
                if (match) {
                    currentX = parseFloat(match[1]);
                    currentY = parseFloat(match[2]);
                }

                layer.addEventListener('mousedown', function(e) {
                    isDragging = true;
                    startX = e.clientX;
                    startY = e.clientY;
                    // Ricalcola currentX/Y fresco dallo stile
                    var t = layer.style.transform || '';
                    var m = t.match(/translate3d\(([^p]+)px,\s*([^p]+)px/);
                    if (m) {
                        currentX = parseFloat(m[1]);
                        currentY = parseFloat(m[2]);
                    } else {
                        currentX = 0; 
                        currentY = 0;
                    }
                    e.preventDefault();
                    e.stopPropagation();
                });

                window.addEventListener('mousemove', function(e) {
                    if (!isDragging) return;
                    var dx = e.clientX - startX;
                    var dy = e.clientY - startY;
                    var newX = currentX + dx;
                    var newY = currentY + dy;
                    
                    layer.style.transform = 'translate3d(' + newX + 'px, ' + newY + 'px, 0)';
                });

                window.addEventListener('mouseup', function(e) {
                    if (!isDragging) return;
                    isDragging = false;
                    console.log('🎯 Layer [' + layerName + '] Final Transform:');
                    console.log(layer.style.transform);
                });
            });
        };

        // Helper per stampare la posizione degli SVG statici
        window.logStaticPositions = function() {
            console.log('--- STATIC ELEMENTS POSITIONS (getBoundingClientRect) ---');
            map.forEach(function(item) {
                var el = document.querySelector(item.html);
                if (el) {
                    var rect = el.getBoundingClientRect();
                    console.log(item.html, rect);
                } else {
                    console.warn(item.html, 'NOT FOUND');
                }
            });
        };
        
        window.debugLottie = function() {
            // Re-create overlay if removed
            if (!document.body.contains(overlay)) {
                document.body.appendChild(overlay);
            }
            if (overlay) {
                overlay.style.display = 'flex';
                overlay.style.opacity = '1';
            }
            
            // IMPORTANT: Prevent 'done' from triggering removal again during debug
            done = true; // Set to true so normal 'reveal' won't hide it
            
            // Replay from middle (approx 50% frames)
            if (anim) {
                var total = anim.totalFrames || 100; // Default fallback
                var midFrame = Math.floor(total / 2);
                console.log('🐞 Debug: Replaying from frame', midFrame, 'and keeping overlay visible.');
                anim.playSegments([midFrame, total], true);
            }
        };

        try {
            console.log('Initializing Lottie animation with path:', lottieJSON);
            anim = lottie.loadAnimation({
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
                
                // --- ATTIVA SYNC ---
                // Collega la funzione di sincronizzazione all'evento enterFrame
                if (typeof syncElements === 'function' && enableSync) {
                    anim.addEventListener('enterFrame', syncElements);
                    console.log('🐞 Sync Lottie-HTML attivo.');
                }

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
