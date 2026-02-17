<?php
/**
 * IML General Settings Page
 * Handles global settings like Intro Animation JSON.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Register the menu
add_action('admin_menu', 'iml_register_general_settings_page');
function iml_register_general_settings_page() {
    add_menu_page(
        'IML General',           // Page Title
        'IML General',           // Menu Title
        'manage_options',        // Capability
        'iml-general-settings',  // Menu Slug
        'iml_general_settings_page_html', // Callback
        'dashicons-admin-generic', // Icon
        2                        // Position
    );
}

// Register settings
add_action('admin_init', 'iml_register_general_settings');
function iml_register_general_settings() {
    register_setting('iml_general_settings_group', 'iml_intro_animation_json');
}

// Render the settings page
function iml_general_settings_page_html() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save settings if posted
    if (isset($_GET['settings-updated'])) {
        add_settings_error('iml_messages', 'iml_message', __('Settings Saved', 'iml-textdomain'), 'updated');
    }
    settings_errors('iml_messages');
    
    $current_json = get_option('iml_intro_animation_json');
    $default_json = IML_PLUGIN_URL . 'frontend/assets/new.json';
    $active_json = $current_json ? $current_json : $default_json;
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        
        <form action="options.php" method="post">
            <?php
            settings_fields('iml_general_settings_group');
            do_settings_sections('iml_general_settings_group');
            ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Intro Animation JSON</th>
                    <td>
                        <input type="text" name="iml_intro_animation_json" id="iml_intro_animation_json" value="<?php echo esc_attr($current_json); ?>" class="regular-text" readonly />
                        <button type="button" class="button" id="upload_json_button">Select/Upload JSON</button>
                        <button type="button" class="button" id="reset_json_button">Reset to Default</button>
                        <p class="description">Select a .json file from the Media Library for the homepage intro animation.</p>
                        <p class="description"><strong>Default:</strong> <code><?php echo esc_html($default_json); ?></code></p>
                        <p class="description"><strong>Active:</strong> <code><?php echo esc_html($active_json); ?></code></p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Save Settings'); ?>
        </form>

        <hr>

        <h2>Animation Test Page</h2>
        <p>Click the button below to open a full-screen preview of the animation in a new tab.</p>
        <p>This preview page is visible only to logged-in users and overlays the animation on the live homepage to simulate the real experience.</p>
        
        <a href="<?php echo esc_url(add_query_arg('iml_animation_preview', '1', home_url())); ?>" target="_blank" class="button button-primary button-large">
            Open Live Animation Preview
        </a>
    </div>

    <script>
    jQuery(document).ready(function($){
        var mediaUploader;
        
        $('#upload_json_button').click(function(e) {
            e.preventDefault();
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            mediaUploader = wp.media({
                title: 'Choose Animation JSON',
                button: {
                    text: 'Choose JSON'
                },
                multiple: false,
                library: {
                    type: 'application/json' // Filter for JSON if WP allows, otherwise usually all files
                }
            });
            
            mediaUploader.on('select', function() {
                var selection = mediaUploader.state().get('selection');
                if (selection && selection.first()) {
                    var attachment = selection.first().toJSON();
                    $('#iml_intro_animation_json').val(attachment.url);
                }
            });
            
            mediaUploader.open();
        });

        $('#reset_json_button').click(function(e) {
            e.preventDefault();
            $('#iml_intro_animation_json').val('');
        });
    });
    </script>
    <?php
}

// Allow JSON upload (security note: enable with caution, but required for this task)
add_filter('upload_mimes', 'iml_allow_json_mime');
function iml_allow_json_mime($mimes) {
    $mimes['json'] = 'application/json';
    return $mimes;
}

// Fix: Add explicit support for JSON upload via wp_handle_upload_prefilter if needed
// Sometimes WP checks file type strictly. This ensures real MIME type detection passes.
add_filter('wp_check_filetype_and_ext', 'iml_check_json_filetype', 10, 4);
function iml_check_json_filetype($data, $file, $filename, $mimes) {
    $filetype = wp_check_filetype( $filename, $mimes );
    $ext = $filetype['ext'];
    $type = $filetype['type'];
    $proper_filename = $data['proper_filename'];

    if ( $ext === 'json' ) {
        $type = 'application/json';
        $ext = 'json';
    }
    
    return compact( 'ext', 'type', 'proper_filename' );
}

// Handle Animation Preview Request
add_action('template_redirect', 'iml_handle_animation_preview');
function iml_handle_animation_preview() {
    if (isset($_GET['iml_animation_preview']) && $_GET['iml_animation_preview'] === '1') {
        // Security Check: Only logged-in users can view this
        if (!is_user_logged_in()) {
            wp_die('Access denied. You must be logged in to view the animation preview.', 'Access Denied', array('response' => 403));
        }

        $custom_url = get_option('iml_intro_animation_json');
        $default_url = IML_PLUGIN_URL . 'frontend/assets/new.json';
        $lottie_url = $custom_url ? $custom_url : $default_url;
        
        // Disable admin bar for cleaner view
        show_admin_bar(false);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>IML Animation Preview</title>
            <style>
                body, html {
                    margin: 0;
                    padding: 0;
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                    background: #fff;
                }
                
                /* Layer 0: Homepage Iframe (Bottom) */
                #site-preview {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    width: 100dvw;
                    height: 100dvh;
                    border: none;
                    z-index: 1;
                    pointer-events: none; /* Make non-interactive as requested */
                    opacity: 1;
                }
                
                /* Layer 0.5: White Overlay (Optional) */
                #white-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(255, 255, 255, 0.5);
                    z-index: 2;
                    display: none; /* Hidden by default */
                    pointer-events: none;
                }
                
                /* Layer 0.6: Grid Overlay (Optional) */
                #grid-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 3;
                    display: none; /* Hidden by default */
                    pointer-events: none;
                    background-size: 20px 20px;
                    background-image:
                        linear-gradient(to right, rgba(0, 0, 0, 0.1) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(0, 0, 0, 0.1) 1px, transparent 1px);
                }

                /* Layer 1: Lottie Animation (Top) */
                #lottie-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100vw;
                    height: 100vh;
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: transparent; /* No background as per recent changes */
                    pointer-events: none;
                }
                
                #lottie-container {
                    width: 100vw;
                    height: 100vh;
                }

                #controls {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 10000;
                    background: rgba(0,0,0,0.8);
                    padding: 15px;
                    border-radius: 8px;
                    color: white;
                    font-family: sans-serif;
                    font-size: 13px;
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    box-shadow: 0 4px 6px rgba(0,0,0,0.3);
                }
                #controls h4 {
                    margin: 0 0 5px 0;
                    font-size: 14px;
                    color: #ddd;
                }
                .control-group {
                    display: flex;
                    gap: 5px;
                    flex-wrap: wrap;
                }
                button {
                    cursor: pointer;
                    padding: 6px 12px;
                    background: #444;
                    color: #fff;
                    border: 1px solid #555;
                    border-radius: 4px;
                    font-size: 12px;
                    transition: background 0.2s;
                }
                button:hover {
                    background: #555;
                }
                button.active {
                    background: #0073aa;
                    border-color: #0073aa;
                }
                button#replay-btn {
                    background: #d63638;
                    border-color: #d63638;
                    font-weight: bold;
                }
                button#replay-btn:hover {
                    background: #e04f51;
                }
                
                /* Timeline Slider */
                .timeline-container {
                    width: 100%;
                    margin-bottom: 5px;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }
                input[type=range] {
                    flex-grow: 1;
                    cursor: pointer;
                }
                #current-frame-display {
                    font-family: monospace;
                    min-width: 50px;
                    text-align: right;
                }
                
                /* Dimensions Controls */
                .dimensions-controls {
                    display: flex;
                    gap: 10px;
                    margin-top: 10px;
                    padding-top: 10px;
                    border-top: 1px solid #555;
                }
                .input-group {
                    display: flex;
                    flex-direction: column;
                    gap: 2px;
                }
                .input-group label {
                    font-size: 10px;
                    color: #aaa;
                }
                .input-group input {
                    width: 60px;
                    padding: 4px;
                    border-radius: 3px;
                    border: 1px solid #555;
                    background: #333;
                    color: white;
                }
                
                /* Responsive 100% full screen fix */
                body, html {
                    width: 100vw;
                    height: 100vh;
                    width: 100dvw;
                    height: 100dvh;
                    margin: 0;
                    padding: 0;
                    overflow: hidden;
                }

                #site-preview, #white-overlay, #grid-overlay {
                    /* Default to full screen, but can be overridden by JS */
                    width: 100%;
                    height: 100%;
                }
                
                /* Dynamic Lottie Wrapper */
                #lottie-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%; /* Default full, but overridden by JS via container? No, overlay is the wrapper */
                    height: 100%;
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: transparent;
                    pointer-events: none;
                }
                #lottie-container {
                    width: 100%;
                    height: 100%;
                    /* Will be overridden by JS */
                }
            </style>
            <!-- Load Lottie Web from CDN or Local if available -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
        </head>
        <body>
            <!-- Background Iframe -->
            <iframe id="site-preview" src="<?php echo home_url(); ?>"></iframe>
            
            <!-- Optional Overlays -->
            <div id="white-overlay"></div>
            <div id="grid-overlay"></div>

            <!-- Animation Overlay -->
            <div id="lottie-overlay">
                <div id="lottie-container"></div>
            </div>

            <!-- Enhanced Controls -->
            <div id="controls">
                <h4>IML Animation Preview</h4>
                <div class="timeline-container">
                    <input type="range" id="anim-slider" min="0" value="0" step="1">
                    <span id="current-frame-display">0</span>
                </div>
                <div class="control-group">
                    <button id="replay-btn">Replay</button>
                    <button id="play-pause-btn">Pause</button>
                    <button id="first-frame-btn">First Frame</button>
                    <button id="last-frame-btn">Last Frame</button>
                </div>
                <div class="control-group">
                    <button id="toggle-white-btn">White Overlay (50%)</button>
                    <button id="toggle-grid-btn">Grid Overlay</button>
                </div>
                
                <div class="dimensions-controls">
                    <div class="input-group">
                        <label>Anim W (px)</label>
                        <input type="text" id="anim-w" value="100%">
                    </div>
                    <div class="input-group">
                        <label>Anim H (px)</label>
                        <input type="text" id="anim-h" value="100%">
                    </div>
                    <div class="input-group">
                        <label>Iframe W (px)</label>
                        <input type="text" id="iframe-w" value="100%">
                    </div>
                    <div class="input-group">
                        <label>Iframe H (px)</label>
                        <input type="text" id="iframe-h" value="100%">
                    </div>
                    <div class="input-group">
                        <label>Viewport</label>
                        <span id="viewport-size" style="font-size:10px; padding-top:5px; color:#aaa;">-</span>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var container = document.getElementById('lottie-container');
                var overlay = document.getElementById('lottie-overlay');
                var whiteOverlay = document.getElementById('white-overlay');
                var gridOverlay = document.getElementById('grid-overlay');
                var iframe = document.getElementById('site-preview');
                
                var playPauseBtn = document.getElementById('play-pause-btn');
                var slider = document.getElementById('anim-slider');
                var frameDisplay = document.getElementById('current-frame-display');
                
                var animW = document.getElementById('anim-w');
                var animH = document.getElementById('anim-h');
                var iframeW = document.getElementById('iframe-w');
                var iframeH = document.getElementById('iframe-h');
                var viewportSize = document.getElementById('viewport-size');
                
                // Load saved dimensions
                var savedAnimW = localStorage.getItem('iml_anim_w');
                var savedAnimH = localStorage.getItem('iml_anim_h');
                var savedIframeW = localStorage.getItem('iml_iframe_w');
                var savedIframeH = localStorage.getItem('iml_iframe_h');
                
                if(savedAnimW) { animW.value = savedAnimW; container.style.width = savedAnimW; }
                if(savedAnimH) { animH.value = savedAnimH; container.style.height = savedAnimH; }
                if(savedIframeW) { iframeW.value = savedIframeW; iframe.style.width = savedIframeW; }
                if(savedIframeH) { iframeH.value = savedIframeH; iframe.style.height = savedIframeH; }
                
                // Update Viewport Info
                function updateViewportInfo() {
                    viewportSize.textContent = window.innerWidth + 'x' + window.innerHeight;
                }
                window.addEventListener('resize', updateViewportInfo);
                updateViewportInfo();
                
                var anim = lottie.loadAnimation({
                    container: container,
                    renderer: 'svg',
                    loop: false,
                    autoplay: true,
                    path: '<?php echo esc_url($lottie_url); ?>', // Load the JSON
                    rendererSettings: {
                        preserveAspectRatio: 'xMidYMid slice' // Fullscreen cover behavior
                    }
                });
                
                // Dimension Inputs
                function updateDimensions() {
                    // Update Animation Container
                    var aw = animW.value;
                    var ah = animH.value;
                    container.style.width = aw;
                    container.style.height = ah;
                    localStorage.setItem('iml_anim_w', aw);
                    localStorage.setItem('iml_anim_h', ah);
                    
                    // Update Iframe
                    var iw = iframeW.value;
                    var ih = iframeH.value;
                    iframe.style.width = iw;
                    iframe.style.height = ih;
                    localStorage.setItem('iml_iframe_w', iw);
                    localStorage.setItem('iml_iframe_h', ih);
                    
                    // Sync overlays with iframe size (optional, but good for testing alignment)
                    // whiteOverlay.style.width = iw;
                    // whiteOverlay.style.height = ih;
                    // gridOverlay.style.width = iw;
                    // gridOverlay.style.height = ih;

                    // Trigger resize for Lottie
                    anim.resize();
                }
                
                animW.addEventListener('change', updateDimensions);
                animH.addEventListener('change', updateDimensions);
                iframeW.addEventListener('change', updateDimensions);
                iframeH.addEventListener('change', updateDimensions);

                var isPlaying = true;
                var totalFrames = 0;

                // Initialize Slider when data is ready
                anim.addEventListener('DOMLoaded', function() {
                    totalFrames = anim.totalFrames;
                    slider.max = totalFrames - 1;
                });

                // Update Slider during playback
                anim.addEventListener('enterFrame', function(e) {
                    if (isPlaying) {
                        slider.value = e.currentTime;
                        frameDisplay.textContent = Math.round(e.currentTime);
                    }
                });

                // Handle Slider Input (Scrubbing)
                slider.addEventListener('input', function() {
                    var frame = parseFloat(this.value);
                    overlay.style.display = 'flex'; // Ensure visible
                    anim.goToAndStop(frame, true);
                    frameDisplay.textContent = Math.round(frame);
                    
                    // Pause when scrubbing
                    isPlaying = false;
                    playPauseBtn.textContent = 'Play';
                });

                // On complete behavior
                anim.addEventListener('complete', function() {
                    console.log('Animation completed');
                    overlay.style.display = 'none';
                    playPauseBtn.textContent = 'Play';
                    isPlaying = false;
                    slider.value = totalFrames - 1;
                    frameDisplay.textContent = Math.round(totalFrames - 1);
                });

                // Replay
                document.getElementById('replay-btn').addEventListener('click', function() {
                    overlay.style.display = 'flex';
                    anim.goToAndPlay(0);
                    playPauseBtn.textContent = 'Pause';
                    isPlaying = true;
                });

                // Play/Pause
                playPauseBtn.addEventListener('click', function() {
                    if (isPlaying) {
                        anim.pause();
                        this.textContent = 'Play';
                    } else {
                        // If animation was hidden/finished, show it again first if needed
                        if (overlay.style.display === 'none') {
                            overlay.style.display = 'flex';
                            anim.goToAndPlay(0); // Restart if finished
                        } else {
                            anim.play();
                        }
                        this.textContent = 'Pause';
                    }
                    isPlaying = !isPlaying;
                });

                // Toggle White Overlay
                document.getElementById('toggle-white-btn').addEventListener('click', function() {
                    this.classList.toggle('active');
                    whiteOverlay.style.display = whiteOverlay.style.display === 'block' ? 'none' : 'block';
                });

                // Toggle Grid Overlay
                document.getElementById('toggle-grid-btn').addEventListener('click', function() {
                    this.classList.toggle('active');
                    gridOverlay.style.display = gridOverlay.style.display === 'block' ? 'none' : 'block';
                });

                // First Frame
                document.getElementById('first-frame-btn').addEventListener('click', function() {
                    overlay.style.display = 'flex';
                    anim.goToAndStop(0, true);
                    playPauseBtn.textContent = 'Play';
                    isPlaying = false;
                    slider.value = 0;
                    frameDisplay.textContent = '0';
                });

                // Last Frame
                document.getElementById('last-frame-btn').addEventListener('click', function() {
                    overlay.style.display = 'flex';
                    var lastFrame = anim.totalFrames - 1; 
                    anim.goToAndStop(lastFrame, true); 
                    playPauseBtn.textContent = 'Play';
                    isPlaying = false;
                    slider.value = lastFrame;
                    frameDisplay.textContent = Math.round(lastFrame);
                });
            });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
