<?php
/*
Plugin Name: Kleinanzeigen
Plugin URI: https://cp-psource.github.io/classifieds/
Description: Vollständiges Kleinanzeigen-Plugin mit nativen WordPress APIs. Beste Integration mit ps-community für Member- und Vertrauenssystem.
Version: 1.0.0
Author: PSOURCE
Author URI: https://github.com/cp-psource
License: GNU General Public License (Version 2 - GPLv2)
Text Domain: classifieds
Domain Path: /languages
Network: false
*/

$plugin_header_translate = array(
__('Kleinanzeigen - Füge Kleinanzeigen zu Deinem Blog oder Netzwerk hinzu. Erstelle und verwalte Anzeigen, lade Bilder hoch, sende E-Mails, aktiviere das Kreditsystem und berechne Deinen Benutzern die Platzierung von Anzeigen in Deinem Netzwerk.', 'classifieds'),
__('PSOURCE', 'classifieds'),
__('https://cp-psource.github.io/classifieds', 'classifieds'),
__('Kleinanzeigen', 'classifieds'),
);

/*
Authors - DerN3rd

Copyright 2012-2024 PSOURCE (https://github.com/cp-psource)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA


/* Define plugin version */
define ( 'CF_VERSION', '1.0.0' );
define ( 'CF_DB_VERSION', '1.0' );

/* define the plugin folder url */
define ( 'CF_PLUGIN_URL', plugin_dir_url(__FILE__));
/* define the plugin folder dir */
define ( 'CF_PLUGIN_DIR', plugin_dir_path(__FILE__));
// The key for the options array
define( 'CF_TEXT_DOMAIN', 'classifieds' );
// The key for the options array
define( 'CF_OPTIONS_NAME', 'classifieds_options' );
// The key for the captcha transient
define( 'CF_CAPTCHA', 'cf_captcha_' );

// ========================================
// PHASE 1: Native WordPress Post Types, Taxonomies & Custom Fields
// Replaces CustomPress dependency with core WordPress APIs
// ========================================

// Register post types
include_once 'core/class-native-post-types.php';
new PS_Native_Post_Types();

// Register taxonomies
include_once 'core/class-native-taxonomies.php';
new PS_Native_Taxonomies();

// Legacy compatibility classes still used by core logic
include_once 'core/class-cf-fields.php';
include_once 'core/class-cf-transactions.php';

// Handle custom fields (meta)
include_once 'core/class-native-custom-fields.php';
new PS_Native_Custom_Fields();

// ps-community Integration (replaces BuddyPress)
include_once 'core/class-native-community.php';
new PS_Native_Community();

// Load core plugin data
include_once 'core/data.php';
new Classifieds_Core_Data();

// Empty deactivation hook (using flush_rewrite_rules is handled by WordPress)
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );

/* Load plugin files */
include_once 'core/core.php';
include_once 'core/payments.php';
include_once 'core/paypal-express-gateway.php';
include_once 'core/functions.php';