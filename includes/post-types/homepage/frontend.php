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

            // 1. Priority: Parent Project/Portfolio
            if ($parent_id) {
                $href = get_permalink($parent_id);
                $title = get_the_title($parent_id); // Use parent title if linking to parent
            } 
            // 2. Fallback: Single Page (if enabled and no parent, or parent logic failed/desired otherwise?)
            // The user requested: "if no parent, go to single page"
            elseif ($single_page_true == '1') {
                $href = get_permalink($post_id);
            } 
            // 3. Last Resort: Image File URL
            else {
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
            background: rgba(255, 255, 255, 0.00001); /* Richiesta utente: bianco 0.1 */
            z-index: 99999999;
            margin: 0;
            padding: 0;
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
            margin: 0;
            padding: 0;
            filter: invert(1); /* Richiesta utente: inverti colore lottie */
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Debug Markers */
        .debug-marker {
            position: fixed;
            width: 20px;
            height: 20px;
            pointer-events: none;
            z-index: 100000000;
            transform: translate(-50%, -50%);
        }
        .debug-marker::before, .debug-marker::after {
            content: '';
            position: absolute;
            background: currentColor;
        }
        .debug-marker::before { top: 9px; left: 0; width: 20px; height: 2px; }
        .debug-marker::after { top: 0; left: 9px; width: 2px; height: 20px; }
        .debug-center-lottie { color: red; border: 1px solid red; border-radius: 50%; }
        .debug-center-static { color: #00ff00; border: 1px solid #00ff00; border-radius: 50%; }

        /* Static SVG elements initially hidden */
        #svg-container svg, .logoalcentro svg {
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.2s ease-in;
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

        // --- PAUSE & ADVANCE FUNCTIONS ---
        // Funzione per mettere in pausa l'animazione da console
        window.pauseLottie = function() {
            if (anim) {
                anim.pause();
                console.log('🐞 Lottie PAUSED at frame:', anim.currentFrame.toFixed(2));
            } else {
                console.warn('Lottie animation not initialized yet.');
            }
        };

        // Funzione per avanzare di N frame (default 5)
        window.advanceLottie = function(frames) {
            if (!anim) {
                console.warn('Lottie animation not initialized yet.');
                return;
            }
            // Se frames non è specificato, usa 5
            var f = (typeof frames === 'number') ? frames : 5;
            
            // Assicuriamoci che l'animazione sia in pausa per poter controllare frame per frame
            anim.pause();
            
            var current = anim.currentFrame;
            var target = current + f;
            
            // Non superare il totale dei frame
            if (target > anim.totalFrames) {
                target = anim.totalFrames;
                console.warn('Reached end of animation.');
            }
            if (target < 0) target = 0; // Supporto per rewind con valori negativi
            
            // goToAndStop(value, isFrame) -> isFrame=true
            anim.goToAndStop(target, true);
            console.log('🐞 Advanced to frame:', target.toFixed(2), '(Delta:', f, ')');
        };
        
        // --- SYNC LOGIC CONFIGURATION ---
        // Imposta a true per attivare il riposizionamento forzato dei layer Lottie
        // affinché coincidano con gli elementi statici HTML.
        var enableSync = false; 
        var debugSync = false; // ATTIVA DEBUG VISUALE (Richiesta Utente)

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

           // Apply transform only in the last 3 seconds of the animation (Richiesta utente)
           // Calculate start frame based on total frames and frame rate
           if (anim && anim.totalFrames && anim.frameRate) {
               var fps = anim.frameRate;
               var totalFrames = anim.totalFrames;
               var durationSync = 3.0; // 3.0 seconds duration
               var framesSync = fps * durationSync;
               var startSyncFrame = totalFrames - framesSync; // Start 3 seconds before end
               
               var currentFrame = anim.currentFrame;

               // Calculate interpolation progress (0 to 1)
               var progress = (currentFrame - startSyncFrame) / framesSync;
               if (progress < 0) progress = 0;
               if (progress > 1) progress = 1;
               
               // DEBUG VISUALIZERS: Se debugSync è attivo, mostra i centri
               if (debugSync) {
                   drawDebugMarkers();
               }

               // Se siamo prima dell'inizio del sync (ultimi 3s), non applichiamo transform
               if (progress === 0) {
                   return;
               }

           } else {
               return; 
           }

           // Trova l'elemento SVG generato da Lottie
           var lottieSVG = container.querySelector('svg');
           if (!lottieSVG) return;

           map.forEach(function(item) {
               // Per ora applichiamo il sync SOLO al Logo centrale se richiesto specificamente, 
               // ma la mappa contiene tutti. Se l'utente vuole centrare TUTTO l'animazione sul logo, 
               // è una logica diversa. Qui stiamo sincronizzando OGNI layer al suo statico.
               // L'utente ha detto: "l-animazione sia perfettamente centrata sull elemento ... logo al centro".
               // Assumiamo che intenda che il LAYER DEL LOGO (1071) debba combaciare con #staticLogoAlCentro.
               
               // 1. TROVA IL LAYER LOTTIE
               var layerName = item.lottie;
               var lottieLayer = lottieSVG.querySelector('g[id="' + layerName + '"]') || 
                                 lottieSVG.querySelector('g[class="' + layerName + '"]') ||
                                 lottieSVG.querySelector('g[aria-label="' + layerName + '"]');

               if (!lottieLayer) return;

               // 2. TROVA L'ELEMENTO HTML STATICO
               var htmlEl = document.querySelector(item.html);
               if (!htmlEl) return;

               // 3. CALCOLA LE POSIZIONI (BoundingBox)
               // Clear previous transform to get native position
               var currentTransform = lottieLayer.style.transform;
               lottieLayer.style.transform = ''; 
               
               var nativeRect = lottieLayer.getBoundingClientRect();
               var targetRect = htmlEl.getBoundingClientRect();

               // 4. CALCOLA IL DELTA (Differenza)
               // Vogliamo che il CENTRO del Lottie vada sul CENTRO dello Statico
               var nativeCenterX = nativeRect.left + nativeRect.width / 2;
               var nativeCenterY = nativeRect.top + nativeRect.height / 2;
               
               var targetCenterX = targetRect.left + targetRect.width / 2;
               var targetCenterY = targetRect.top + targetRect.height / 2;
               
               var deltaX = targetCenterX - nativeCenterX;
               var deltaY = targetCenterY - nativeCenterY;
               
               // Log solo per il logo centrale e solo occasionalmente per non intasare
               if (debugSync && item.lottie === "1071" && Math.random() < 0.05) {
                   console.log('🐞 Delta Logo (1071): X=' + deltaX.toFixed(2) + ', Y=' + deltaY.toFixed(2));
               }

               // 5. APPLICA LA TRASFORMAZIONE INTERPOLATA
               var currentDx = deltaX * progress;
               var currentDy = deltaY * progress;
               
               lottieLayer.style.transform = 'translate3d(' + currentDx + 'px, ' + currentDy + 'px, 0)';
            });
        }
        
        function drawDebugMarkers() {
            // Rimuovi vecchi marker
            var old = document.querySelectorAll('.debug-marker');
            old.forEach(function(el) { el.remove(); });
            
            var lottieSVG = container.querySelector('svg');
            if (!lottieSVG) return;
            
            map.forEach(function(item) {
                // Solo per il logo centrale (1071) come richiesto
                if (item.lottie !== "1071") return;
                
                var layerName = item.lottie;
                var lottieLayer = lottieSVG.querySelector('g[id="' + layerName + '"]');
                var htmlEl = document.querySelector(item.html);
                
                if (lottieLayer && htmlEl) {
                    // Centro Lottie
                    var r1 = lottieLayer.getBoundingClientRect();
                    var cx1 = r1.left + r1.width / 2;
                    var cy1 = r1.top + r1.height / 2;
                    
                    var m1 = document.createElement('div');
                    m1.className = 'debug-marker debug-center-lottie debug-marker-lottie';
                    m1.style.left = cx1 + 'px';
                    m1.style.top = cy1 + 'px';
                    document.body.appendChild(m1);
                    
                    // Centro Statico
                    var r2 = htmlEl.getBoundingClientRect();
                    var cx2 = r2.left + r2.width / 2;
                    var cy2 = r2.top + r2.height / 2;
                    
                    var m2 = document.createElement('div');
                    m2.className = 'debug-marker debug-center-static debug-marker-static';
                    m2.style.left = cx2 + 'px';
                    m2.style.top = cy2 + 'px';
                    document.body.appendChild(m2);
                }
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
                    preserveAspectRatio: 'xMidYMid meet' // Mantieni proporzioni originali e centra
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
