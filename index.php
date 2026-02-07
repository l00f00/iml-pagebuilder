<?php
/**
 * Plugin Name: IML Page Builder
 * Description: Custom page builder and admin modifications for IML Photographer.
 * Version: 1.0
 * Author: Trae Assistant
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('IML_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('IML_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include CPT and Meta Box Registrations (FROM registrazionecampi.php)
require_once IML_PLUGIN_DIR . 'includes/cpt-registrations.php';

// Include admin cleanup and settings
require_once IML_PLUGIN_DIR . 'includes/admin-cleanup.php';

// Include Portfolio Meta Box
require_once IML_PLUGIN_DIR . 'includes/meta-box-portfolio.php';

// Include Homepage Meta Box
require_once IML_PLUGIN_DIR . 'includes/meta-box-homepage.php';

// Include Project Meta Box
require_once IML_PLUGIN_DIR . 'includes/meta-box-project.php';

// Include Attachment Custom Fields
require_once IML_PLUGIN_DIR . 'includes/attachment-fields.php';

// Include AJAX Functions
require_once IML_PLUGIN_DIR . 'includes/ajax-functions.php';

// Include Shortcodes
require_once IML_PLUGIN_DIR . 'includes/shortcodes.php';
require_once IML_PLUGIN_DIR . 'includes/shortcode-project-single.php';

// Include Frontend Logic
require_once IML_PLUGIN_DIR . 'includes/frontend-logic.php';
