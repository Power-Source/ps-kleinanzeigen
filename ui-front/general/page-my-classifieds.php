<?php
/**
 * Dashboard: Meine Anzeigen, Nachrichten, Gemerkte Anzeigen (AJAX-Version)
 */

global $current_user, $wp_query;
$current_user = wp_get_current_user();

$options_frontend = $this->get_options( 'frontend' );
$user_intro = isset( $options_frontend['user_intro'] ) ? trim( $options_frontend['user_intro'] ) : '';
$user_show_favorites_tab = ! isset( $options_frontend['user_show_favorites_tab'] ) || 1 === (int) $options_frontend['user_show_favorites_tab'];
$unread_count = method_exists( $this, 'get_unread_message_count' ) ? $this->get_unread_message_count( $current_user->ID ) : 0;

$cf_path = get_permalink( $this->my_classifieds_page_id );
$error = get_query_var( 'cf_error' );

// Startseite als "active"
$active_tab = isset( $_GET['messages'] ) ? 'messages' : ( isset( $_GET['favorites'] ) ? 'favorites' : ( isset( $_GET['saved'] ) ? 'saved' : ( isset( $_GET['ended'] ) ? 'ended' : 'active' ) ) );

remove_filter( 'the_content', array( &$this, 'my_classifieds_content' ) );

wp_enqueue_script( 'cf-frontend', $this->plugin_url . 'ui-front/js/ui-front.js', array( 'jquery' ), false, true );
wp_localize_script( 'cf-frontend', 'cfFrontend', array(
	'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
	'nonce'            => wp_create_nonce( 'cf_send_message' ),
	'dashboardNonce'   => wp_create_nonce( 'cf_dashboard_nonce' ),
	'textDomain'       => $this->text_domain,
	'strings'          => array(
		'sending'    => __( 'Wird gesendet...', $this->text_domain ),
		'sent'       => __( 'Nachricht gesendet!', $this->text_domain ),
		'error'      => __( 'Ups, da ist was schiefgelaufen.', $this->text_domain ),
		'noMessages' => __( 'Noch keine Nachrichten.', $this->text_domain ),
	),
) );
?>

<div class="cf-dashboard">

<?php if ( ! empty( $error ) ) : ?>
<div class="cf-notice cf-notice-error"><?php echo esc_html( $error ); ?></div>
<?php endif; ?>

<?php if ( '' !== $user_intro ) : ?>
<div class="cf-user-intro"><?php echo wp_kses_post( wpautop( $user_intro ) ); ?></div>
<?php endif; ?>

<aside class="cf-dashboard-sidebar">
<div class="cf-dashboard-user">
<?php echo get_avatar( $current_user->ID, 64, '', '', array( 'class' => 'cf-avatar' ) ); ?>
<div class="cf-dashboard-user-info">
<strong><?php echo esc_html( $current_user->display_name ); ?></strong>
<?php if ( $this->is_full_access() ) : ?>
<span class="cf-user-badge"><?php _e( 'Voller Zugriff', $this->text_domain ); ?></span>
<?php elseif ( $this->use_credits ) : ?>
<span class="cf-credit-count"><?php echo esc_html( $this->transactions->credits ); ?> <?php _e( 'Credits', $this->text_domain ); ?></span>
<?php endif; ?>
</div>
</div>

<nav class="cf-dashboard-nav">
<a href="<?php echo esc_url( $cf_path ); ?>" class="cf-nav-item <?php echo $active_tab === 'active' ? 'is-active' : ''; ?>" data-tab="active">
<span class="cf-nav-icon">&#x1F4CB;</span> <?php _e( 'Meine Anzeigen', $this->text_domain ); ?>
</a>
<?php if ( $user_show_favorites_tab ) : ?>
<a href="<?php echo esc_url( $cf_path . '?favorites' ); ?>" class="cf-nav-item <?php echo $active_tab === 'favorites' ? 'is-active' : ''; ?>" data-tab="favorites">
<span class="cf-nav-icon">&#x1F516;</span> <?php _e( 'Gemerkte Anzeigen', $this->text_domain ); ?>
</a>
<?php endif; ?>
<a href="<?php echo esc_url( $cf_path . '?saved' ); ?>" class="cf-nav-item <?php echo $active_tab === 'saved' ? 'is-active' : ''; ?>" data-tab="saved">
<span class="cf-nav-icon">&#x1F4DD;</span> <?php _e( 'Entwürfe', $this->text_domain ); ?>
</a>
<a href="<?php echo esc_url( $cf_path . '?ended' ); ?>" class="cf-nav-item <?php echo $active_tab === 'ended' ? 'is-active' : ''; ?>" data-tab="ended">
<span class="cf-nav-icon">&#x1F4E6;</span> <?php _e( 'Beendete Anzeigen', $this->text_domain ); ?>
</a>
<a href="<?php echo esc_url( $cf_path . '?messages' ); ?>" class="cf-nav-item <?php echo $active_tab === 'messages' ? 'is-active' : ''; ?>" data-tab="messages">
<span class="cf-nav-icon">&#x1F4AC;</span> <?php _e( 'Nachrichten', $this->text_domain ); ?>
<?php if ( $unread_count > 0 ) : ?>
<span class="cf-unread-badge"><?php echo esc_html( $unread_count ); ?></span>
<?php endif; ?>
</a>
</nav>

<div class="cf-dashboard-actions">
<?php echo do_shortcode( '[cf_add_classified_btn text="' . esc_attr__( 'Neue Anzeige', $this->text_domain ) . '" view="loggedin"]' ); ?>
</div>
</aside>

<main class="cf-dashboard-main">
<div id="cf-dashboard-content" class="cf-dashboard-content">
<div class="cf-loader" style="text-align: center; padding: 40px; display: none;">
<p><?php _e( 'Lädt...', $this->text_domain ); ?></p>
</div>
<div id="cf-tab-content" class="cf-tab-content">
<!-- Inhalt wird hier per AJAX geladen -->
</div>
</div>
</main>

</div>

<?php
if ( isset( $wp_query ) ) $wp_query->post_count = 0;
wp_reset_query();
?>
