<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post, $wp_query;
$options = $this->get_options( 'general' );
$frontend_options = $this->get_options( 'frontend' );
$field_image = ( empty( $options['field_image_def'] ) ) ? $this->plugin_url . 'ui-front/general/images/blank.gif' : $options['field_image_def'];

$duration = get_post_meta( $post->ID, '_cf_duration', true );
$cost = get_post_meta( $post->ID, '_cf_cost', true );
$cost_display = is_numeric( $cost ) ? number_format_i18n( (float) $cost, 2 ) : $cost;
$gallery_ids = get_post_meta( $post->ID, '_cf_gallery_ids', true );
if ( ! is_array( $gallery_ids ) ) {
	$gallery_ids = array();
}
$featured_image_url = has_post_thumbnail() ? wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'large' ) : '';
$is_favorite = method_exists( $this, 'is_favorite_post' ) ? $this->is_favorite_post( $post->ID ) : false;
$author_id = (int) $post->post_author;
$author_display_name = get_the_author_meta( 'display_name', $author_id );
$author_registered_raw = get_the_author_meta( 'user_registered', $author_id );
$author_registered = ! empty( $author_registered_raw ) ? date_i18n( get_option( 'date_format' ), strtotime( $author_registered_raw ) ) : '';
$author_active_ads = count_user_posts( $author_id, 'classifieds', true );
$region_terms = get_the_terms( $post->ID, 'kleinanzeigen-region' );
$region_name = ( ! is_wp_error( $region_terms ) && ! empty( $region_terms ) ) ? implode( ', ', wp_list_pluck( $region_terms, 'name' ) ) : '';

$single_show_gallery = ! isset( $frontend_options['single_show_gallery'] ) || 1 === (int) $frontend_options['single_show_gallery'];
$single_show_trust_block = ! isset( $frontend_options['single_show_trust_block'] ) || 1 === (int) $frontend_options['single_show_trust_block'];
$single_show_seller_card = ! isset( $frontend_options['single_show_seller_card'] ) || 1 === (int) $frontend_options['single_show_seller_card'];
$single_show_sticky_actions = ! isset( $frontend_options['single_show_sticky_actions'] ) || 1 === (int) $frontend_options['single_show_sticky_actions'];
$single_show_reserved_badge = ! isset( $frontend_options['single_show_reserved_badge'] ) || 1 === (int) $frontend_options['single_show_reserved_badge'];
$template_preset = isset( $frontend_options['frontend_preset'] ) ? sanitize_key( (string) $frontend_options['frontend_preset'] ) : '';
if ( ! in_array( $template_preset, array( 'b2c', 'premium', 'community' ), true ) ) {
	$template_preset = 'b2c';
}

$legacy_gallery_layout_defaults = array(
	'b2c'       => 'image_only',
	'premium'   => 'slider',
	'community' => 'mosaic',
);

$legacy_gallery_layout_key = 'single_gallery_layout_' . $template_preset;
$legacy_single_gallery_layout = isset( $frontend_options[ $legacy_gallery_layout_key ] ) ? sanitize_key( (string) $frontend_options[ $legacy_gallery_layout_key ] ) : $legacy_gallery_layout_defaults[ $template_preset ];
if ( ! in_array( $legacy_single_gallery_layout, array( 'image_only', 'slider', 'mosaic' ), true ) ) {
	$legacy_single_gallery_layout = $legacy_gallery_layout_defaults[ $template_preset ];
}

$single_hero_media_mode_defaults = array(
	'b2c'       => 'featured_only',
	'premium'   => 'slider',
	'community' => 'mosaic',
);

$single_hero_media_mode_key = 'single_hero_media_mode_' . $template_preset;
$single_hero_media_mode = isset( $frontend_options[ $single_hero_media_mode_key ] ) ? sanitize_key( (string) $frontend_options[ $single_hero_media_mode_key ] ) : '';
if ( '' === $single_hero_media_mode ) {
	$single_hero_media_mode = 'image_only' === $legacy_single_gallery_layout ? 'featured_only' : $legacy_single_gallery_layout;
}
if ( ! in_array( $single_hero_media_mode, array( 'featured_only', 'slider', 'mosaic' ), true ) ) {
	$single_hero_media_mode = $single_hero_media_mode_defaults[ $template_preset ];
}

$single_extra_gallery_position_defaults = array(
	'b2c'       => 'below_description',
	'premium'   => 'below_description',
	'community' => 'below_description',
);

$single_extra_gallery_position_key = 'single_extra_gallery_position_' . $template_preset;
$single_extra_gallery_position = isset( $frontend_options[ $single_extra_gallery_position_key ] ) ? sanitize_key( (string) $frontend_options[ $single_extra_gallery_position_key ] ) : $single_extra_gallery_position_defaults[ $template_preset ];
if ( ! in_array( $single_extra_gallery_position, array( 'above_description', 'below_description' ), true ) ) {
	$single_extra_gallery_position = $single_extra_gallery_position_defaults[ $template_preset ];
}

$single_extra_gallery_display_defaults = array(
	'b2c'       => 'grid',
	'premium'   => 'grid',
	'community' => 'grid',
);

$single_extra_gallery_display_key = 'single_extra_gallery_display_mode_' . $template_preset;
$single_extra_gallery_display_mode = isset( $frontend_options[ $single_extra_gallery_display_key ] ) ? sanitize_key( (string) $frontend_options[ $single_extra_gallery_display_key ] ) : '';
if ( '' === $single_extra_gallery_display_mode ) {
	$single_extra_gallery_display_mode = 'slider' === $legacy_single_gallery_layout ? 'slider' : 'grid';
}
if ( ! in_array( $single_extra_gallery_display_mode, array( 'grid', 'slider' ), true ) ) {
	$single_extra_gallery_display_mode = $single_extra_gallery_display_defaults[ $template_preset ];
}

if ( ! $single_show_gallery ) {
	$single_hero_media_mode = 'featured_only';
}

$featured_image_id = has_post_thumbnail() ? (int) get_post_thumbnail_id( $post->ID ) : 0;
$single_extra_gallery_ids = $gallery_ids;
$single_hero_gallery_ids = $single_extra_gallery_ids;
if ( $featured_image_id > 0 ) {
	array_unshift( $single_hero_gallery_ids, $featured_image_id );
	$single_hero_gallery_ids = array_values( array_unique( array_map( 'absint', $single_hero_gallery_ids ) ) );
}

$is_reserved = method_exists( $this, 'is_reserved_post' ) ? $this->is_reserved_post( $post->ID ) : ( '1' === (string) get_post_meta( $post->ID, '_cf_reserved', true ) );
$is_featured = method_exists( $this, 'is_featured' ) ? $this->is_featured( $post->ID ) : ( '1' === (string) get_post_meta( $post->ID, '_cf_is_featured', true ) );

$published_date = get_the_date();
$expiration_date = '';
global $Classifieds_Core;
if ( is_object( $Classifieds_Core ) && method_exists( $Classifieds_Core, 'get_expiration_date' ) ) {
	$expiration_date = $Classifieds_Core->get_expiration_date( get_the_ID() );
}

$trust_block_content = '';
if ( isset( $frontend_options['trust_block_content'] ) && '' !== trim( $frontend_options['trust_block_content'] ) ) {
	$trust_block_content = trim( $frontend_options['trust_block_content'] );
} elseif ( isset( $options['trust_block_content'] ) ) {
	$trust_block_content = trim( $options['trust_block_content'] );
}

$open_contact_form = isset( $_GET['cf_contact'] ) && '1' === wp_unslash( $_GET['cf_contact'] );

if ( isset( $_POST['_wpnonce'] ) ) : ?>
<br clear="all" />
<div id="cf-message-error">
	<?php _e( 'Das Senden der Nachricht ist fehlgeschlagen: Du hast im Kontaktformular nicht alle erforderlichen Felder korrekt ausgefuellt!', $this->text_domain ); ?>
</div>
<br clear="all" />
<?php elseif ( isset( $_GET['sent'] ) && 1 == $_GET['sent'] ) : ?>
<br clear="all" />
<div id="cf-message">
	<?php _e( 'Nachricht wird gesendet!', $this->text_domain ); ?>
</div>
<br clear="all" />
<?php elseif ( isset( $_GET['sent'] ) && 0 == $_GET['sent'] ) : ?>
<br clear="all" />
<div id="cf-message-error">
	<?php _e( 'Der E-Mail-Dienst antwortet nicht!', $this->text_domain ); ?>
</div>
<br clear="all" />
<?php endif; ?>
