<?php
/**
 * PS Kleinanzeigen Hook-Boilerplates
 *
 * Copy/Paste-Vorlagen fuer eigene Integrationen.
 * Diese Datei ist als Referenz gedacht und muss nicht geladen werden.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ------------------------------
 * Filter: classifieds_full_access
 * ------------------------------
 */
add_filter( 'classifieds_full_access', function ( $has_access ) {
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	return (bool) $has_access;
}, 10, 1 );

/**
 * ----------------------
 * Filter: cf_pagination
 * ----------------------
 */
add_filter( 'cf_pagination', function ( $html ) {
	$html = (string) $html;
	return '<div class="my-pagination-wrapper">' . $html . '</div>';
}, 10, 1 );

/**
 * ----------------------------------------
 * Filter: classifieds_custom_templates_dir
 * ----------------------------------------
 */
add_filter( 'classifieds_custom_templates_dir', function ( $template_dir ) {
	$custom_dir = WP_CONTENT_DIR . '/my-classifieds-templates';
	if ( is_dir( $custom_dir ) ) {
		return $custom_dir;
	}

	return (string) $template_dir;
}, 10, 1 );

/**
 * ---------------------------------
 * Filter: classifieds_custom_fields
 * ---------------------------------
 */
add_filter( 'classifieds_custom_fields', function ( $custom_fields ) {
	$custom_fields = is_array( $custom_fields ) ? $custom_fields : array();

	$custom_fields['my_extra_field'] = array(
		'label' => 'Mein Zusatzfeld',
		'type'  => 'text',
	);

	return $custom_fields;
}, 10, 1 );

/**
 * --------------------------------
 * Filter: author_classifieds_link
 * --------------------------------
 */
add_filter( 'author_classifieds_link', function ( $link, $author_id, $author_nicename ) {
	$author_id = (int) $author_id;
	if ( $author_id <= 0 ) {
		return (string) $link;
	}

	return home_url( '/profil/' . $author_id . '/anzeigen/' );
}, 10, 3 );

/**
 * -------------------------------
 * Filter: cf_wp_kses_allowed_html
 * -------------------------------
 */
add_filter( 'cf_wp_kses_allowed_html', function ( $allowed_html ) {
	$allowed_html = is_array( $allowed_html ) ? $allowed_html : array();

	$allowed_html['iframe'] = array(
		'src'             => true,
		'width'           => true,
		'height'          => true,
		'allow'           => true,
		'allowfullscreen' => true,
	);

	return $allowed_html;
}, 10, 1 );

/**
 * -----------------------------------
 * Action: classifieds_affiliate_settings
 * -----------------------------------
 */
add_action( 'classifieds_affiliate_settings', function ( $affiliate_settings ) {
	$affiliate_settings = is_array( $affiliate_settings ) ? $affiliate_settings : array();

	echo '<p><strong>Eigene Affiliate-Erweiterung:</strong> aktiv.</p>';
}, 10, 1 );

/**
 * ------------------------------------------
 * Action: classifieds_affiliate_credit_purchase
 * ------------------------------------------
 */
add_action( 'classifieds_affiliate_credit_purchase', function ( $affiliate_settings, $user_id, $order_post_id, $purchased_credit_packages ) {
	$user_id = (int) $user_id;
	$order_post_id = (int) $order_post_id;

	if ( $user_id <= 0 || $order_post_id <= 0 ) {
		return;
	}

	$purchased_credit_packages = is_array( $purchased_credit_packages ) ? $purchased_credit_packages : array();
	foreach ( $purchased_credit_packages as $pkg ) {
		if ( ! is_array( $pkg ) ) {
			continue;
		}

		$product_id = isset( $pkg['product_id'] ) ? (int) $pkg['product_id'] : 0;
		$credits    = isset( $pkg['credits'] ) ? (int) $pkg['credits'] : 0;
		$quantity   = isset( $pkg['quantity'] ) ? (int) $pkg['quantity'] : 0;

		if ( $product_id <= 0 || $credits <= 0 || $quantity <= 0 ) {
			continue;
		}

		// Eigene Provisions-/Tracking-Logik.
	}
}, 10, 4 );

/**
 * --------------------------------------------
 * Action: classifieds_affiliate_one_time_purchase
 * --------------------------------------------
 */
add_action( 'classifieds_affiliate_one_time_purchase', function ( $affiliate_settings, $user_id, $order_post_id, $one_time_purchase ) {
	$user_id = (int) $user_id;
	$order_post_id = (int) $order_post_id;

	if ( $user_id <= 0 || $order_post_id <= 0 || ! is_array( $one_time_purchase ) ) {
		return;
	}

	$product_id = isset( $one_time_purchase['product_id'] ) ? (int) $one_time_purchase['product_id'] : 0;
	$price      = isset( $one_time_purchase['price'] ) ? (string) $one_time_purchase['price'] : '0';
	$quantity   = isset( $one_time_purchase['quantity'] ) ? (int) $one_time_purchase['quantity'] : 0;

	if ( $product_id <= 0 || $quantity <= 0 ) {
		return;
	}

	// Eigene Provisions-/Tracking-Logik.
}, 10, 4 );

/**
 * ---------------------------------
 * Action: cf_handle_credits_requests
 * ---------------------------------
 */
add_action( 'cf_handle_credits_requests', function () {
	// Eigene Admin-Nacharbeiten im Credits-Bereich.
}, 10, 0 );
