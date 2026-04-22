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
$dashboard_preset = isset( $options_frontend['frontend_preset'] ) ? sanitize_key( (string) $options_frontend['frontend_preset'] ) : '';
if ( ! in_array( $dashboard_preset, array( 'b2c', 'premium', 'community' ), true ) ) {
	$dashboard_preset = 'b2c';
}
$community_profile_url = function_exists( 'cpc_members_get_profile_url' ) ? cpc_members_get_profile_url( $current_user->ID ) : '';
$dashboard_classes = 'cf-dashboard cf-dashboard-preset-' . $dashboard_preset;
if ( ! empty( $community_profile_url ) ) {
	$dashboard_classes .= ' has-community-profile';
}

$cf_path = get_permalink( $this->my_classifieds_page_id );
$error = get_query_var( 'cf_error' );

// Startseite als "active"
$active_tab = isset( $_GET['messages'] ) ? 'messages' : ( isset( $_GET['favorites'] ) ? 'favorites' : ( isset( $_GET['saved'] ) ? 'saved' : ( isset( $_GET['ended'] ) ? 'ended' : 'active' ) ) );

remove_filter( 'the_content', array( &$this, 'my_classifieds_content' ) );

wp_enqueue_script( 'cf-frontend', $this->plugin_url . 'ui-front/js/ui-front.js', array( 'jquery' ), filemtime( $this->plugin_dir . 'ui-front/js/ui-front.js' ), true );
wp_localize_script( 'cf-frontend', 'cfFrontend', array(
	'ajaxUrl'          => admin_url( 'admin-ajax.php' ),
	'nonce'            => wp_create_nonce( 'cf_send_message' ),
	'dashboardNonce'   => wp_create_nonce( 'cf_frontend_actions' ),
	'textDomain'       => $this->text_domain,
	'strings'          => array(
		'sending'    => __( 'Wird gesendet...', $this->text_domain ),
		'sent'       => __( 'Nachricht gesendet!', $this->text_domain ),
		'error'      => __( 'Ups, da ist was schiefgelaufen.', $this->text_domain ),
		'noMessages' => __( 'Noch keine Nachrichten.', $this->text_domain ),
	),
) );

wp_add_inline_script( 'cf-frontend', "(function(\$){
	function getActiveTabFromUrl(){
		var params = new URLSearchParams(window.location.search || '');
		if (params.get('tab')) return params.get('tab');
		if (params.has('messages')) return 'messages';
		if (params.has('favorites')) return 'favorites';
		if (params.has('saved')) return 'saved';
		if (params.has('ended')) return 'ended';
		return 'active';
	}

	function loadDashboardTab(tabName){
		var cfg = window.cfFrontend || {};
		if (!cfg.ajaxUrl) return;

		var nonce = cfg.dashboardNonce || cfg.nonce || '';
		var \$content = \$('#cf-tab-content');
		var \$loader = \$('.cf-loader');

		if (!\$content.length) return;

		\$loader.show();
		\$content.html('');

		\$.ajax({
			url: cfg.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			data: {
				action: 'cf_load_dashboard_tab',
				tab: tabName,
				nonce: nonce
			}
		}).done(function(res){
			\$loader.hide();
			if (res && res.success) {
				var html = (res.data && typeof res.data === 'object' && typeof res.data.html !== 'undefined') ? res.data.html : res.data;
				\$content.html(html || '');
				\$('.cf-nav-item').removeClass('is-active');
				\$('.cf-nav-item[data-tab=\"' + tabName + '\"]').addClass('is-active');
				window.history.pushState({tab: tabName}, '', '?tab=' + tabName);
			} else {
				var msg = (res && res.data && res.data.message) ? res.data.message : 'Fehler beim Laden des Tabs.';
				\$content.html('<div class=\"cf-notice cf-notice-error\">' + msg + '</div>');
			}
		}).fail(function(xhr){
			\$loader.hide();
			var msg = (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) ? xhr.responseJSON.data.message : 'Fehler beim Laden des Tabs.';
			\$content.html('<div class=\"cf-notice cf-notice-error\">' + msg + '</div>');
		});
	}

	\$(document).on('click', '.cf-nav-item[data-tab]', function(e){
		e.preventDefault();
		var tab = \$(this).data('tab');
		if (tab) loadDashboardTab(tab);
	});

	\$(function(){
		if (\$('#cf-dashboard-content').length) {
			loadDashboardTab(getActiveTabFromUrl());
		}
	});
})(jQuery);", 'after' );
?>

<div class="<?php echo esc_attr( $dashboard_classes ); ?>">

<?php if ( ! empty( $error ) ) : ?>
<div class="cf-notice cf-notice-error"><?php echo esc_html( $error ); ?></div>
<?php endif; ?>

<?php if ( '' !== $user_intro ) : ?>
<div class="cf-user-intro"><?php echo wp_kses_post( wpautop( $user_intro ) ); ?></div>
<?php endif; ?>

<?php
// Membership/Tarif-Status Box (oben auf der Seite, optional basierend auf Einstellungen)
$options_frontend = $this->get_options( 'frontend' );
$show_tarif_box = ! empty( $options_frontend['tarif_status_enabled'] ) || ! empty( $options_frontend['tariff_status_enabled'] );

if ( $show_tarif_box ) {
	$membership_name = '';
	$expiry_formatted = '';
	$is_member = false;

	// Get active membership (kompatibel mit unterschiedlichen Membership2-Versionen)
	$member = null;
	if ( class_exists( 'MS_Factory' ) && method_exists( 'MS_Factory', 'load' ) ) {
		$member = MS_Factory::load( 'MS_Model_Member', (int) $current_user->ID );
	} elseif ( class_exists( 'MS_Model_Member' ) && method_exists( 'MS_Model_Member', 'load' ) ) {
		$member = MS_Model_Member::load( (int) $current_user->ID );
	}

	if ( is_object( $member ) && method_exists( $member, 'get_memberships' ) ) {
		$memberships = $member->get_memberships( array( 'status' => 'active' ) );
		if ( ! empty( $memberships ) ) {
			$is_member = true;
			$active_ms = array_shift( $memberships );
			if ( is_object( $active_ms ) ) {
				$membership_name = isset( $active_ms->name ) ? (string) $active_ms->name : '';
				if ( method_exists( $active_ms, 'get_expiration_date' ) ) {
					$expiry_date = $active_ms->get_expiration_date();
					if ( $expiry_date && $expiry_date > 0 ) {
						$expiry_formatted = date_i18n( get_option( 'date_format' ), $expiry_date );
					}
				}
			}
		}
	}

	// Build inline styles from settings
	$bg_color = isset( $options_frontend['tarif_status_bg_color'] ) ? $options_frontend['tarif_status_bg_color'] : ( isset( $options_frontend['tariff_status_bg_color'] ) ? $options_frontend['tariff_status_bg_color'] : '#f0f4f8' );
	$border_color = isset( $options_frontend['tarif_status_border_color'] ) ? $options_frontend['tarif_status_border_color'] : ( isset( $options_frontend['tariff_status_border_color'] ) ? $options_frontend['tariff_status_border_color'] : '#2271b1' );
	$text_color = isset( $options_frontend['tarif_status_text_color'] ) ? $options_frontend['tarif_status_text_color'] : ( isset( $options_frontend['tariff_status_text_color'] ) ? $options_frontend['tariff_status_text_color'] : '#333333' );
	$heading_color = isset( $options_frontend['tarif_status_heading_color'] ) ? $options_frontend['tarif_status_heading_color'] : ( isset( $options_frontend['tariff_status_heading_color'] ) ? $options_frontend['tariff_status_heading_color'] : '#1a1a1a' );
	$warning_color = isset( $options_frontend['tarif_status_warning_color'] ) ? $options_frontend['tarif_status_warning_color'] : ( isset( $options_frontend['tariff_status_warning_color'] ) ? $options_frontend['tariff_status_warning_color'] : '#d32f2f' );
	$text_size = isset( $options_frontend['tarif_status_text_size'] ) ? $options_frontend['tarif_status_text_size'] : ( isset( $options_frontend['tariff_status_text_size'] ) ? $options_frontend['tariff_status_text_size'] : 'normal' );
	$padding = isset( $options_frontend['tarif_status_padding'] ) ? absint( $options_frontend['tarif_status_padding'] ) : ( isset( $options_frontend['tariff_status_padding'] ) ? absint( $options_frontend['tariff_status_padding'] ) : 15 );

	$text_sizes = array(
		'small' => '12px',
		'normal' => '14px',
		'medium' => '16px',
		'large' => '18px',
	);
	$font_size = isset( $text_sizes[ $text_size ] ) ? $text_sizes[ $text_size ] : '14px';

	$box_style = "background-color: {$bg_color}; border-left: 4px solid {$border_color}; color: {$text_color}; padding: {$padding}px; border-radius: 4px; margin-bottom: 20px; font-size: {$font_size};";
	$heading_style = "color: {$heading_color}; margin: 0 0 8px 0;";
	$warning_style = "color: {$warning_color}; font-weight: bold;";

	if ( $is_member || $this->use_credits ) :
		?>
<div class="cf-tarif-status-box" style="<?php echo esc_attr( $box_style ); ?>">
	<?php if ( $is_member ) : ?>
		<div class="cf-tarif-section" style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid rgba(0,0,0,0.1);">
			<strong style="<?php echo esc_attr( $heading_style ); ?>">🎫 <?php _e( 'Aktueller Tarif', $this->text_domain ); ?></strong>
			<div style="margin-top: 5px;">
				<span><?php echo esc_html( $membership_name ); ?></span>
				<?php if ( $expiry_formatted ) : ?>
					<div style="margin-top: 4px; opacity: 0.9;">
						<?php printf( __( 'Gültig bis: %s', $this->text_domain ), $expiry_formatted ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
	
	<?php if ( $this->use_credits ) : ?>
		<div class="cf-tarif-section">
			<strong style="<?php echo esc_attr( $heading_style ); ?>">💳 <?php _e( 'Credits verfügbar', $this->text_domain ); ?></strong>
			<div style="margin-top: 5px;">
				<span><?php echo esc_html( $this->transactions->credits ); ?> Credits</span>
				<?php 
					$credit_warning = $this->transactions->credits <= 5;
					if ( $credit_warning ) {
						echo '<div style="' . esc_attr( $warning_style ) . '; margin-top: 4px;">' . __( '⚠️ Credits werden knapp!', $this->text_domain ) . '</div>';
					}
				?>
			</div>
		</div>
	<?php endif; ?>
</div>
		<?php
	endif;
}
?>

<aside class="cf-dashboard-sidebar">
<div class="cf-dashboard-user">
<?php if ( ! empty( $community_profile_url ) ) : ?>
<a class="cf-dashboard-avatar-link" href="<?php echo esc_url( $community_profile_url ); ?>">
	<?php echo get_avatar( $current_user->ID, 64, '', '', array( 'class' => 'cf-avatar' ) ); ?>
</a>
<?php else : ?>
<?php echo get_avatar( $current_user->ID, 64, '', '', array( 'class' => 'cf-avatar' ) ); ?>
<?php endif; ?>
<div class="cf-dashboard-user-info">
<?php if ( ! empty( $community_profile_url ) ) : ?>
<a class="cf-dashboard-profile-link" href="<?php echo esc_url( $community_profile_url ); ?>">
	<strong><?php echo esc_html( $current_user->display_name ); ?></strong>
</a>
<?php else : ?>
<strong><?php echo esc_html( $current_user->display_name ); ?></strong>
<?php endif; ?>
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

<?php
// Dashboard Credit-Status Card
$options_payments = $this->get_options( 'payments' );
$credits_feature_enabled = ! empty( $options_payments['enable_credits'] );
$show_credit_status = ! empty( $options_payments['dashboard_show_credit_status'] ) && $credits_feature_enabled;
$warning_threshold = isset( $options_payments['dashboard_credit_warning_threshold'] ) ? absint( $options_payments['dashboard_credit_warning_threshold'] ) : 5;
$featured_pkg_id = empty( $options_payments['featured_credit_package_id'] ) ? 0 : absint( $options_payments['featured_credit_package_id'] );
$current_credits = isset( $this->transactions->credits ) ? (int) $this->transactions->credits : 0;
$is_low = $current_credits <= $warning_threshold;

if ( $show_credit_status ) :
	$mp_credit_packages = ! empty( $options_payments['mp_credit_packages'] ) && is_array( $options_payments['mp_credit_packages'] ) ? $options_payments['mp_credit_packages'] : array();
	$featured_package = null;
	
	if ( $featured_pkg_id > 0 ) {
		foreach ( $mp_credit_packages as $pkg ) {
			if ( abs( $pkg['product_id'] ) == $featured_pkg_id ) {
				$featured_package = $pkg;
				break;
			}
		}
	}
	?>
<div class="cf-credit-status-card <?php echo $is_low ? 'cf-credit-low' : ''; ?>">
	<div class="cf-credit-status-header">
		<span class="cf-credit-title"><?php _e( 'Deine Credits', $this->text_domain ); ?></span>
	</div>
	<div class="cf-credit-status-amount-row">
		<strong><?php echo esc_html( $current_credits ); ?> Credits</strong>
		<?php if ( $is_low ) : ?>
			<span class="cf-credit-warning"><?php printf( __( 'Achtung: Nur noch %d!', $this->text_domain ), $current_credits ); ?></span>
		<?php else : ?>
			<span class="cf-credit-info"><?php _e( 'verfügbar', $this->text_domain ); ?></span>
		<?php endif; ?>
	</div>
	<div class="cf-credit-status-body">
		<?php if ( $featured_package && ! empty( $featured_package['product_id'] ) ) : ?>
			<?php $feat_pkg_url = get_permalink( $featured_package['product_id'] ); ?>
			<?php if ( $feat_pkg_url ) : ?>
			<div class="cf-featured-package-btn">
				<?php if ( $is_low ) : ?>
					<a href="<?php echo esc_url( $feat_pkg_url ); ?>" class="cf-btn cf-btn-featured cf-btn-alert">
						<?php echo esc_html( $featured_package['label'] ); ?>
					</a>
				<?php else : ?>
					<a href="<?php echo esc_url( $feat_pkg_url ); ?>" class="cf-btn cf-btn-featured">
						<?php echo esc_html( $featured_package['label'] ); ?>
					</a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</div>
<?php endif; ?>

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
