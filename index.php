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
require_once IML_PLUGIN_DIR . 'includes/admin-settings.php'; // IML General Settings

// Include Attachment Custom Fields
require_once IML_PLUGIN_DIR . 'includes/attachment-fields.php';

// Include AJAX Functions
require_once IML_PLUGIN_DIR . 'includes/ajax-functions.php';

// --- NEW STRUCTURE INCLUDES ---

// Project
require_once IML_PLUGIN_DIR . 'includes/post-types/project/builder.php';
require_once IML_PLUGIN_DIR . 'includes/post-types/project/frontend.php';

// Portfolio
require_once IML_PLUGIN_DIR . 'includes/post-types/portfolio/builder.php';
require_once IML_PLUGIN_DIR . 'includes/post-types/portfolio/frontend.php';

// Homepage
require_once IML_PLUGIN_DIR . 'includes/post-types/homepage/builder.php';
require_once IML_PLUGIN_DIR . 'includes/post-types/homepage/frontend.php';

// Attachment
require_once IML_PLUGIN_DIR . 'includes/post-types/attachment/frontend.php';

// Archive
require_once IML_PLUGIN_DIR . 'includes/post-types/archive/frontend.php';

// Taxonomies Shortcodes (Tags, Categories)
require_once IML_PLUGIN_DIR . 'includes/frontend/taxonomies.php';

// Include Frontend Logic (Enqueues, Redirects)
require_once IML_PLUGIN_DIR . 'includes/frontend-logic.php';
