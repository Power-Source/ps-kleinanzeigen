<?php
/*
Plugin Name: PS Kleinanzeigen
Plugin URI: https://psource.eimen.net/wiki/ps-kleinanzeigen-dokumentation/
Description: PS Kleinanzeigen stellt dir alles zur Verfügung was du benötigst um eine regionale Kleinanzeigen-Plattform zu erstellen. Durch die Integrationen mit anderen PSOURCE Plugins kannst du deine Kleinanzeigenplattform jederzeit flexibel erweitern und Hochskalieren.
Version: 1.0.1
Author: PSOURCE
Author URI: https://github.com/Power-Source
License: GNU General Public License (Version 2 - GPLv2)
Text Domain: classifieds
Domain Path: /languages
Network: false
*/

$plugin_header_translate = array(
__('PS Kleinanzeigen stellt dir alles zur Verfügung was du benötigst um eine regionale Kleinanzeigen-Plattform zu erstellen. Durch die Integrationen mit anderen PSOURCE Plugins kannst du deine Kleinanzeigenplattform jederzeit flexibel erweitern und Hochskalieren.', 'classifieds'),
__('PSOURCE', 'classifieds'),
__('https://psource.eimen.net/wiki/ps-kleinanzeigen-dokumentation/', 'classifieds'),
__('PS Kleinanzeigen', 'classifieds'),
);

/*
Authors - DerN3rd

Copyright 2012-2026 PSOURCE (https://github.com/Power-Source)

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
define ( 'CF_VERSION', '1.0.1' );
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
include_once 'core/class-cf-marketpress-bridge.php';
new CF_MarketPress_Bridge();

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
include_once 'core/functions.php';