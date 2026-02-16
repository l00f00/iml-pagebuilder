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
        <p>To test the animation, create a page and insert the shortcode <code>[iml_animation_test]</code>.</p>
        <p>Or visit the test page if already created: <a href="<?php echo site_url('/animation-test/'); ?>" target="_blank">/animation-test/</a> (Ensure a page with slug 'animation-test' exists and contains the shortcode).</p>
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

// Test Shortcode
add_shortcode('iml_animation_test', 'iml_render_animation_test');
function iml_render_animation_test() {
    $custom_url = get_option('iml_intro_animation_json');
    $default_url = IML_PLUGIN_URL . 'frontend/assets/new.json';
    $lottie_url = $custom_url ? $custom_url : $default_url;
    
    ob_start();
    ?>
    <div style="padding: 20px; background: #eee; text-align: center;">
        <h3>Testing Animation: <?php echo basename($lottie_url); ?></h3>
        <p>URL: <?php echo esc_url($lottie_url); ?></p>
        <div id="test-lottie-container" style="width: 800px; height: 600px; margin: 0 auto; background: #fff; border: 1px solid #ccc;"></div>
        <button id="replay-animation" style="margin-top: 20px; padding: 10px 20px; font-size: 16px;">Replay</button>
    </div>

    <!-- Ensure Lottie is loaded -->
    <?php wp_enqueue_script('lottie-web'); ?>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var container = document.getElementById('test-lottie-container');
        var anim = lottie.loadAnimation({
            container: container,
            renderer: 'svg',
            loop: false,
            autoplay: true,
            path: '<?php echo esc_url($lottie_url); ?>',
            rendererSettings: {
                preserveAspectRatio: 'xMidYMid meet'
            }
        });

        document.getElementById('replay-animation').addEventListener('click', function() {
            anim.goToAndPlay(0);
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
