<?php
/**
 * Dashboard Nachrichten-View (für AJAX-Listing)
 */
$current_user_id = get_current_user_id();

// Ungelesene Nachrichten abrufen
$args = array(
	'post_type'      => 'cf_message',
	'posts_per_page' => -1,
	'meta_query'     => array(
		array(
			'key'   => '_cf_msg_recipient',
			'value' => $current_user_id,
		),
	),
	'orderby'        => 'post_date',
	'order'          => 'DESC',
);

$messages = new WP_Query( $args );

if ( $messages->have_posts() ) : ?>
	<div class="cf-messages-list">
		<?php while ( $messages->have_posts() ) : $messages->the_post();
			$sender_id = get_the_author_meta( 'ID' );
			$sender = get_userdata( $sender_id );
			$is_read = get_post_meta( get_the_ID(), '_cf_msg_read_' . $current_user_id, true );
			?>
			<div class="cf-msg-item <?php echo $is_read ? '' : 'cf-msg-unread'; ?>" data-msg-id="<?php the_ID(); ?>" data-sender-id="<?php echo esc_attr( $sender_id ); ?>">
				<div class="cf-msg-avatar">
					<?php echo get_avatar( $sender->ID, 48 ); ?>
				</div>
				<div class="cf-msg-content">
					<strong><?php echo esc_html( $sender->display_name ); ?></strong>
					<p><?php echo esc_html( wp_trim_words( get_the_content(), 20 ) ); ?></p>
					<small><?php echo esc_html( human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) ) . ' ' . __( 'her', 'ps-kleinanzeigen' ); ?></small>
				</div>
				<button class="cf-btn-open-msg" data-msg-id="<?php the_ID(); ?>"><?php _e( 'Öffnen', 'ps-kleinanzeigen' ); ?></button>
			</div>
		<?php endwhile; ?>
	</div>
<?php else : ?>
	<p><?php _e( 'Noch keine Nachrichten.', 'ps-kleinanzeigen' ); ?></p>
<?php endif;

wp_reset_postdata();
