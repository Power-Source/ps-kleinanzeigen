<?php if (!defined('ABSPATH')) die('No direct access allowed!');
/**
 * BuddyPress: Meine Anzeigen - modernes Dashboard
 */

global $bp, $wp_query, $paged;

$options_general    = $this->get_options( 'general' );
$options_frontend   = $this->get_options( 'frontend' );
$user_intro         = isset( $options_frontend['user_intro'] ) ? trim( $options_frontend['user_intro'] ) : '';
$user_show_favorites_tab   = ! isset( $options_frontend['user_show_favorites_tab'] ) || 1 === (int) $options_frontend['user_show_favorites_tab'];
$user_allow_reserve_toggle = ! isset( $options_frontend['user_allow_reserve_toggle'] ) || 1 === (int) $options_frontend['user_allow_reserve_toggle'];
$favorite_ids = method_exists( $this, 'get_favorite_ids' ) ? $this->get_favorite_ids() : array();
$unread_count = method_exists( $this, 'get_unread_message_count' ) ? $this->get_unread_message_count( bp_displayed_user_id() ) : 0;

$cf_path = $bp->displayed_user->domain . $this->classifieds_page_slug . '/' . $this->my_classifieds_page_slug;
$paged   = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

// Aktiven Tab bestimmen
if ( in_array( 'messages', (array) $bp->action_variables ) ) {
    $sub = 'messages';
} elseif ( $user_show_favorites_tab && in_array( 'favorites', (array) $bp->action_variables ) ) {
    $sub = 'favorites';
} elseif ( in_array( 'saved', (array) $bp->action_variables ) ) {
    $sub = 'saved';
} elseif ( in_array( 'ended', (array) $bp->action_variables ) ) {
    $sub = 'ended';
} else {
    $sub = 'active';
}

if ( $sub !== 'messages' ) {
    $query_args = array(
        'paged'       => $paged,
        'post_type'   => 'classifieds',
        'author'      => bp_displayed_user_id(),
    );
    if ( $sub === 'favorites' ) {
        $query_args['post_status'] = 'publish';
        unset( $query_args['author'] );
        $query_args['post__in'] = ! empty( $favorite_ids ) ? array_map( 'absint', $favorite_ids ) : array( 0 );
    } elseif ( $sub === 'saved' ) {
        $query_args['post_status'] = array( 'draft', 'pending' );
    } elseif ( $sub === 'ended' ) {
        $query_args['post_status'] = 'private';
    } else {
        $query_args['post_status'] = 'publish';
    }
    query_posts( $query_args );
}

wp_enqueue_script( 'cf-frontend', $this->plugin_url . 'ui-front/js/ui-front.js', array( 'jquery' ), false, true );
wp_localize_script( 'cf-frontend', 'cfFrontend', array(
    'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
    'nonce'      => wp_create_nonce( 'cf_send_message' ),
    'textDomain' => $this->text_domain,
    'strings'    => array(
        'sending'    => __( 'Wird gesendet\u2026', $this->text_domain ),
        'sent'       => __( 'Nachricht gesendet!', $this->text_domain ),
        'error'      => __( 'Ups, da ist was schiefgelaufen.', $this->text_domain ),
        'noMessages' => __( 'Noch keine Nachrichten.', $this->text_domain ),
    ),
) );

$is_my_profile = bp_is_my_profile();
?>

<?php if ( '' !== $user_intro ) : ?>
<div class="cf-user-intro"><?php echo wp_kses_post( wpautop( $user_intro ) ); ?></div>
<?php endif; ?>

<div class="cf-dashboard">

    <!-- Sidebar -->
    <aside class="cf-dashboard-sidebar">
        <?php if ( $is_my_profile && function_exists( 'bp_get_loggedin_user_avatar' ) ) : ?>
        <div class="cf-dashboard-user">
            <?php echo bp_get_loggedin_user_avatar( array( 'type' => 'thumb', 'width' => 40, 'height' => 40, 'class' => 'cf-avatar' ) ); ?>
            <div class="cf-dashboard-user-info">
                <strong><?php echo esc_html( bp_get_displayed_user_fullname() ); ?></strong>
                <?php if ( $this->use_credits && isset( $this->transactions->credits ) ) : ?>
                <span class="cf-credit-count"><?php echo (int) $this->transactions->credits; ?> Credits</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <nav class="cf-dashboard-nav">
            <a href="<?php echo esc_url( $cf_path . '/active/' ); ?>" class="cf-nav-item<?php echo $sub === 'active' ? ' is-active' : ''; ?>">
                <span class="cf-nav-icon">📋</span>
                <?php _e( 'Meine Anzeigen', $this->text_domain ); ?>
            </a>
            <?php if ( $user_show_favorites_tab ) : ?>
            <a href="<?php echo esc_url( $cf_path . '/favorites/' ); ?>" class="cf-nav-item<?php echo $sub === 'favorites' ? ' is-active' : ''; ?>">
                <span class="cf-nav-icon">❤️</span>
                <?php _e( 'Gemerkt', $this->text_domain ); ?>
                <?php if ( ! empty( $favorite_ids ) ) : ?>
                <span class="cf-unread-badge"><?php echo count( $favorite_ids ); ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <a href="<?php echo esc_url( $cf_path . '/saved/' ); ?>" class="cf-nav-item<?php echo $sub === 'saved' ? ' is-active' : ''; ?>">
                <span class="cf-nav-icon">📝</span>
                <?php _e( 'Entwürfe', $this->text_domain ); ?>
            </a>
            <a href="<?php echo esc_url( $cf_path . '/ended/' ); ?>" class="cf-nav-item<?php echo $sub === 'ended' ? ' is-active' : ''; ?>">
                <span class="cf-nav-icon">🗃️</span>
                <?php _e( 'Beendet', $this->text_domain ); ?>
            </a>
            <?php if ( $is_my_profile ) : ?>
            <a href="<?php echo esc_url( $cf_path . '/messages/' ); ?>" class="cf-nav-item<?php echo $sub === 'messages' ? ' is-active' : ''; ?>">
                <span class="cf-nav-icon">✉️</span>
                <?php _e( 'Nachrichten', $this->text_domain ); ?>
                <?php if ( $unread_count > 0 ) : ?>
                <span class="cf-unread-badge"><?php echo (int) $unread_count; ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
        </nav>

        <?php if ( $is_my_profile ) : ?>
        <div class="cf-dashboard-actions">
            <?php echo do_shortcode( '[cf_add_classified_btn text="' . esc_attr__( '+ Anzeige aufgeben', $this->text_domain ) . '" view="loggedin"]' ); ?>
        </div>
        <?php endif; ?>
    </aside><!-- .cf-dashboard-sidebar -->

    <!-- Main Content -->
    <main class="cf-dashboard-main">

        <?php /* ---- NACHRICHTEN ---- */ ?>
        <?php if ( $sub === 'messages' && $is_my_profile ) : ?>

        <div class="cf-dashboard-tab-header">
            <h2><?php _e( 'Nachrichten', $this->text_domain ); ?></h2>
        </div>

        <?php
        $conversations = method_exists( $this, 'get_user_conversations' ) ? $this->get_user_conversations( bp_displayed_user_id() ) : array();
        ?>

        <div class="cf-messages-layout">

            <div class="cf-inbox-list" id="cf-inbox-list">
                <div class="cf-inbox-header">
                    <h3><?php _e( 'Posteingang', $this->text_domain ); ?></h3>
                </div>

                <?php if ( empty( $conversations ) ) : ?>
                <div class="cf-empty-state" style="padding:32px 16px;">
                    <span>📭</span>
                    <p><?php _e( 'Noch keine Nachrichten.', $this->text_domain ); ?></p>
                </div>
                <?php else : ?>
                <?php foreach ( $conversations as $conv ) :
                    $other_user_id = (int) $conv->other_user_id;
                    $other_avatar  = get_avatar( $other_user_id, 44, '', '', array( 'class' => '' ) );
                    $other_name    = get_the_author_meta( 'display_name', $other_user_id );
                    $ad_title      = get_the_title( (int) $conv->ad_id );
                    $has_unread    = isset( $conv->unread ) && $conv->unread > 0;
                ?>
                <div class="cf-inbox-item<?php echo $has_unread ? ' has-unread' : ''; ?>"
                     onclick="cfDashboard.openConversation('<?php echo esc_js( $conv->thread_id ); ?>')"
                     data-thread="<?php echo esc_attr( $conv->thread_id ); ?>">
                    <div class="cf-inbox-avatar">
                        <?php echo $other_avatar; ?>
                        <?php if ( $has_unread ) : ?>
                        <span class="cf-unread-dot"><?php echo (int) $conv->unread; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="cf-inbox-content">
                        <div class="cf-inbox-meta">
                            <strong><?php echo esc_html( $other_name ); ?></strong>
                            <time><?php echo esc_html( human_time_diff( strtotime( $conv->last_date ), current_time( 'timestamp' ) ) ); ?></time>
                        </div>
                        <?php if ( ! empty( $ad_title ) ) : ?>
                        <div class="cf-inbox-ad-ref">📌 <?php echo esc_html( $ad_title ); ?></div>
                        <?php endif; ?>
                        <p class="cf-inbox-preview"><?php echo esc_html( wp_trim_words( $conv->last_message, 10 ) ); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="cf-conversation-view" id="cf-conversation-view">
                <div class="cf-conversation-placeholder">
                    <span>💬</span>
                    <p><?php _e( 'Wähle eine Konversation aus.', $this->text_domain ); ?></p>
                </div>
            </div>

        </div>

        <?php /* ---- ANZEIGEN-TABS ---- */ ?>
        <?php else : ?>

        <div class="cf-dashboard-tab-header">
            <h2>
                <?php
                if ( $sub === 'favorites' )       _e( 'Gemerkte Anzeigen', $this->text_domain );
                elseif ( $sub === 'saved' )        _e( 'Entwürfe', $this->text_domain );
                elseif ( $sub === 'ended' )        _e( 'Beendete Anzeigen', $this->text_domain );
                else                               _e( 'Meine Anzeigen', $this->text_domain );
                ?>
            </h2>
        </div>

        <?php if ( ! have_posts() ) : ?>
        <div class="cf-empty-state">
            <span>📭</span>
            <p><?php _e( 'Keine Anzeigen gefunden.', $this->text_domain ); ?></p>
            <?php if ( $sub === 'active' && $is_my_profile ) : ?>
            <?php echo do_shortcode( '[cf_add_classified_btn text="' . esc_attr__( 'Erste Anzeige aufgeben', $this->text_domain ) . '" view="loggedin"]' ); ?>
            <?php endif; ?>
        </div>
        <?php else : ?>

        <div class="cf-listing-grid cf-my-listing-grid">
        <?php while ( have_posts() ) : the_post();
            $ad_id = get_the_ID();
            $is_reserved = method_exists( $this, 'is_reserved_post' ) && $this->is_reserved_post( $ad_id );
            $thumb_url = has_post_thumbnail( $ad_id ) ? get_the_post_thumbnail_url( $ad_id, 'medium' ) : ( ! empty( $options_general['field_image_def'] ) ? $options_general['field_image_def'] : '' );
            $cost = get_post_meta( $ad_id, '_cf_cost', true );
            $cost_display = is_numeric( $cost ) ? number_format_i18n( (float) $cost, 2 ) . ' €' : esc_html( $cost );
        ?>
        <div class="cf-card" id="post-<?php the_ID(); ?>">
            <?php if ( ! empty( $thumb_url ) ) : ?>
            <a href="<?php the_permalink(); ?>" class="cf-card-image-wrap">
                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>" class="cf-card-image" loading="lazy">
                <?php if ( $is_reserved ) : ?>
                <span class="cf-status-badge is-reserved"><?php _e( 'Reserviert', $this->text_domain ); ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>
            <div class="cf-card-body">
                <h3 class="cf-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <?php if ( ! empty( $cost_display ) ) : ?>
                <div class="cf-card-price"><?php echo $cost_display; ?></div>
                <?php endif; ?>
                <div class="cf-card-meta">
                    <?php echo $this->get_expiration_date( $ad_id ); ?>
                </div>
                <?php if ( $is_my_profile ) : ?>
                <div class="cf-card-actions">
                    <?php if ( current_user_can( 'edit_classified', $ad_id ) ) :
                        echo do_shortcode( '[cf_edit_classified_btn text="' . esc_attr__( 'Bearbeiten', $this->text_domain ) . '" view="always" post="' . $ad_id . '"]' );
                    endif; ?>

                    <?php if ( $sub === 'active' ) : ?>
                        <?php if ( $user_allow_reserve_toggle ) : ?>
                        <button type="button" class="button cf-btn-sm cf-reserve-toggle" data-post-id="<?php echo $ad_id; ?>">
                            <?php echo $is_reserved ? esc_html__( 'Reservierung aufheben', $this->text_domain ) : esc_html__( 'Reservieren', $this->text_domain ); ?>
                        </button>
                        <?php endif; ?>
                        <form method="post" action="#" style="display:inline;">
                            <?php wp_nonce_field( 'verify' ); ?>
                            <input type="hidden" name="post_id" value="<?php echo $ad_id; ?>">
                            <input type="hidden" name="action" value="end">
                            <button type="submit" name="confirm" value="1" class="button cf-btn-sm"><?php _e( 'Beenden', $this->text_domain ); ?></button>
                        </form>
                    <?php elseif ( $sub === 'favorites' ) : ?>
                        <button type="button" class="button cf-favorite-toggle is-active cf-btn-sm" data-post-id="<?php echo $ad_id; ?>">
                            <span class="cf-favorite-label-active"><?php _e( 'Gemerkt', $this->text_domain ); ?></span>
                        </button>
                    <?php elseif ( $sub === 'saved' || $sub === 'ended' ) : ?>
                        <form method="post" action="#" style="display:inline;">
                            <?php wp_nonce_field( 'verify' ); ?>
                            <input type="hidden" name="post_id" value="<?php echo $ad_id; ?>">
                            <input type="hidden" name="action" value="renew">
                            <input type="hidden" name="duration" value="">
                            <button type="submit" name="confirm" value="1" class="button cf-btn-sm"><?php _e( 'Verlängern', $this->text_domain ); ?></button>
                        </form>
                    <?php endif; ?>

                    <?php if ( current_user_can( 'delete_classifieds' ) && 'favorites' !== $sub ) : ?>
                    <form method="post" action="#" style="display:inline;" onsubmit="return confirm('<?php echo esc_js( __( 'Anzeige wirklich löschen?', $this->text_domain ) ); ?>')">
                        <?php wp_nonce_field( 'verify' ); ?>
                        <input type="hidden" name="post_id" value="<?php echo $ad_id; ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" name="confirm" value="1" class="button cf-btn-sm cf-btn-danger"><?php _e( 'Löschen', $this->text_domain ); ?></button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endwhile; ?>
        </div>

        <?php echo $this->pagination( $this->pagination_bottom ); ?>
        <?php endif; ?>

        <?php endif; ?>

    </main>

</div><!-- .cf-dashboard -->

<?php if ( is_object( $wp_query ) ) $wp_query->post_count = 0; ?>
