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
            mediaUploader = wp.media.frames.file_frame = wp.media({
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
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#iml_intro_animation_json').val(attachment.url);
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
                    width: 100%;
                    height: 100%;
                    border: none;
                    z-index: 1;
                    pointer-events: none; /* Make non-interactive as requested */
                    opacity: 1;
                }
                
                /* Layer 1: Lottie Animation (Top) */
                #lottie-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 9999;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    background: transparent; /* No background as per recent changes */
                }
                
                #lottie-container {
                    width: 100%;
                    height: 100%;
                }

                #controls {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    z-index: 10000;
                    background: rgba(0,0,0,0.8);
                    padding: 10px;
                    border-radius: 5px;
                    color: white;
                    font-family: sans-serif;
                    font-size: 12px;
                }
                button {
                    cursor: pointer;
                    padding: 5px 10px;
                    background: #fff;
                    border: none;
                    border-radius: 3px;
                }
            </style>
            <!-- Load Lottie Web from CDN or Local if available -->
            <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
        </head>
        <body>
            <!-- Background Iframe -->
            <iframe id="site-preview" src="<?php echo home_url(); ?>"></iframe>

            <!-- Animation Overlay -->
            <div id="lottie-overlay">
                <div id="lottie-container"></div>
            </div>

            <!-- Simple Controls -->
            <div id="controls">
                IML Animation Preview <br><br>
                <button id="replay-btn">Replay Animation</button>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                var container = document.getElementById('lottie-container');
                var overlay = document.getElementById('lottie-overlay');
                
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

                // On complete behavior
                anim.addEventListener('complete', function() {
                    console.log('Animation completed');
                    // Optional: fade out logic if you want to mimic the site exactly, 
                    // but user asked for "niente sfumatura subito presente" recently, 
                    // so we just let it finish.
                    // If we want to hide it to see the site behind:
                    overlay.style.display = 'none';
                });

                document.getElementById('replay-btn').addEventListener('click', function() {
                    overlay.style.display = 'flex';
                    anim.goToAndPlay(0);
                });
            });
            </script>
        </body>
        </html>
        <?php
        exit;
    }
}
