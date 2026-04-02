<?php if (!defined('ABSPATH')) die('No direct access allowed!'); ?>

<?php
/**
* The template for displaying BuddyPress Classifieds component - My Classifieds page.
* You can override this file in your active theme.
*
* @package Classifieds
* @subpackage UI Front BuddyPress
* @since Classifieds 2.0
*/
?>

<?php

global $bp, $wp_query, $paged;

$options = $this->get_options( 'general' );
$options_general = $options;
$options_frontend = $this->get_options( 'frontend' );
$user_intro = isset( $options_frontend['user_intro'] ) ? trim( $options_frontend['user_intro'] ) : '';
$user_show_favorites_tab = ! isset( $options_frontend['user_show_favorites_tab'] ) || 1 === (int) $options_frontend['user_show_favorites_tab'];
$user_allow_reserve_toggle = ! isset( $options_frontend['user_allow_reserve_toggle'] ) || 1 === (int) $options_frontend['user_allow_reserve_toggle'];
$favorite_ids = method_exists( $this, 'get_favorite_ids' ) ? $this->get_favorite_ids() : array();

$cf_path = $bp->displayed_user->domain . $this->classifieds_page_slug .'/' . $this->my_classifieds_page_slug;

$paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;

//if(isset($bp)) $paged = $bp->action_variables[array_search('page',$bp->action_variables) + 1]; //find /page/x

$query_args = array(
'paged' => $paged,
'post_type' => 'classifieds',
'author' => bp_displayed_user_id(),
);

/* Get posts based on post_status */
if ( in_array( 'saved',  $bp->action_variables ) ) {
	$query_args['post_status'] = array('draft', 'pending');
	$sub = 'saved';
}
elseif ( $user_show_favorites_tab && in_array( 'favorites',  $bp->action_variables ) ) {
	$query_args['post_status'] = 'publish';
	unset( $query_args['author'] );
	$query_args['post__in'] = ! empty( $favorite_ids ) ? array_map( 'absint', $favorite_ids ) : array( 0 );
	$sub = 'favorites';
}
elseif ( in_array( 'ended',  $bp->action_variables ) ) {
	$query_args['post_status'] = 'private';
	$sub = 'ended';
}else {
	$query_args['post_status'] = 'publish';
	$sub = 'active';
}

//$wp_query = new WP_Query( array( $query_args);
query_posts($query_args);
?>

<script type="text/javascript" src="<?php echo $this->plugin_url . 'ui-front/js/ui-front.js'; ?>" >
</script>

<?php if ( !empty( $error ) ): ?>
<br /><div class="error"><?php echo $error . '<br />'; ?></div>
<?php endif; ?>

<div class="profile">
	<?php if ( '' !== $user_intro ) : ?>
	<div class="cf-user-intro"><?php echo wp_kses_post( wpautop( $user_intro ) ); ?></div>
	<?php endif; ?>

	<?php if ( bp_is_my_profile() ): ?>

	<?php if ( $this->is_full_access() ): ?>
	<div class="av-credits"><?php _e( 'Du kannst neue Anzeigen erstellen.', $this->text_domain ); ?></div>
	<?php elseif($this->use_credits): ?>
	<div class="av-credits"><?php _e( 'Deine verfuegbaren Credits:', $this->text_domain ); ?> <?php echo $this->transactions->credits; ?></div>
	<?php else:
	echo do_shortcode('[cf_checkout_btn text="' . __('Anzeigenpaket kaufen', $this->text_domain) . '" view="loggedin"]');
	?>
	<?php endif; ?>

	<ul class="cf_tabs">
		<li class="<?php if ( in_array( 'active', $bp->action_variables ) || empty( $bp->action_variables ) ) echo 'cf_active current'; ?>"><a href="<?php echo $cf_path . '/active/'; ?>"><?php _e( 'Aktive Anzeigen', $this->text_domain ); ?></a></li>
		<?php if ( $user_show_favorites_tab ) : ?>
		<li class="<?php if ( in_array( 'favorites',  $bp->action_variables ) ) echo 'cf_active current'; ?>"><a href="<?php echo $cf_path . '/favorites/'; ?>"><?php _e( 'Gemerkte Anzeigen', $this->text_domain ); ?></a></li>
		<?php endif; ?>
		<li class="<?php if ( in_array( 'saved',  $bp->action_variables ) ) echo 'cf_active current'; ?>"><a href="<?php echo $cf_path . '/saved/'; ?>"><?php _e( 'Gespeicherte Anzeigen', $this->text_domain ); ?></a></li>
		<li class="<?php if ( in_array( 'ended',  $bp->action_variables ) ) echo 'cf_active current'; ?>"><a href="<?php echo $cf_path . '/ended/'; ?>"><?php _e( 'Beendete Anzeigen', $this->text_domain ); ?></a></li>
	</ul>

	<?php endif; ?>

	<div class="clear"></div>

	<?php

	/* Build messages */
	if ( ! have_posts() ) {
		$msg   = __( 'Es wurden keine Anzeigen gefunden.', $this->text_domain );
		$class = 'info';
	} elseif ( isset( $action ) && $action == 'end' ) {
		$msg = sprintf( __( 'Anzeige "%s" wurde beendet.', $this->text_domain ), $post_title );
		$class = 'updated';
	} elseif ( isset( $action ) && $action == 'renew' ) {
		$msg = sprintf( __( 'Anzeige "%s" wurde verlaengert.', $this->text_domain ), $post_title );
		$class = 'updated';
	} elseif ( isset( $action ) && $action == 'edit' ) {
		$msg = sprintf( __( 'Anzeige "%s" wurde gespeichert.', $this->text_domain ), $post_title );
		$class = 'updated';
	} elseif ( isset( $action ) && $action == 'delete' ) {
		$msg = sprintf( __( 'Anzeige "%s" wurde geloescht.', $this->text_domain ), $post_title );
		$class = 'updated';
	}
	?>

	<?php if ( isset( $msg ) ): ?>
	<div class="<?php echo $class; ?>" id="message">
		<p><?php echo $msg; ?></p>
	</div>
	<?php endif; ?>

	<div class="cf_tab_container">

		<?php /* Display navigation to next/previous pages when applicable */ ?>
		<?php echo $this->pagination( $this->pagination_bottom ); ?>
		<br clear="both" />
		<div class="cf-listing-grid cf-my-listing-grid">
		<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>
		<?php $is_reserved = method_exists( $this, 'is_reserved_post' ) && $this->is_reserved_post( get_the_ID() ); ?>

		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?> >
			<div class="cf-ad">
				<div class="cf-pad">
					<div class="cf-image">
						<?php
						if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
							if ( ! empty( $options_general['field_image_def'] ) )
							echo '<img width="150" height="150" title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $options_general['field_image_def'] . '">';
						} else {
							echo get_the_post_thumbnail( get_the_ID(), array( 200, 150 ) );
						}
						?>
					</div>
					<div class="cf-info">
						<?php if ( $is_reserved ) : ?>
							<span class="cf-status-badge is-reserved"><?php _e( 'Reserviert', $this->text_domain ); ?></span>
						<?php endif; ?>
						<table>
							<tr>
								<th><?php _e( 'Titel', $this->text_domain ); ?></th>
								<td>
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Kategorien', $this->text_domain ); ?></th>
								<td>
									<?php $taxonomies = get_object_taxonomies( 'classifieds', 'names' ); ?>
									<?php foreach ( $taxonomies as $taxonomy ): ?>
									<?php echo get_the_term_list( get_the_ID(), $taxonomy, '', ', ', '' ) . ' '; ?>
									<?php endforeach; ?>
								</td>
							</tr>
							<tr>
								<th><?php _e( 'Laeuft ab', $this->text_domain ); ?></th>
								<td><?php echo $this->get_expiration_date( get_the_ID() ); ?></td>
							</tr>
						</table>
					</div>

					<form action="#" method="post" id="action-form-<?php the_ID(); ?>" class="action-form">
						<?php wp_nonce_field('verify'); ?>
						<input type="hidden" name="post_id" value="<?php the_ID(); ?>" />
						<input type="hidden" name="url" value="<?php the_permalink(); ?>" />

						<?php if ( bp_is_my_profile() ):
						if(current_user_can('edit_classified', get_the_ID())){
							echo do_shortcode('[cf_edit_classified_btn text="' . __('Anzeige bearbeiten', $this->text_domain) . '" view="always" post="' . get_the_ID() . '"]');
						}
						?>

						<?php if ( isset( $sub ) && $sub == 'active' ): ?>
						<?php if ( $user_allow_reserve_toggle ) : ?>
						<button type="submit" name="reserve" value="<?php echo $is_reserved ? esc_attr__( 'Reservierung aufheben', $this->text_domain ) : esc_attr__( 'Als reserviert markieren', $this->text_domain ); ?>" onclick="classifieds.toggle_reserve('<?php the_ID(); ?>'); return false;" ><?php echo $is_reserved ? esc_html__( 'Reservierung aufheben', $this->text_domain ) : esc_html__( 'Als reserviert markieren', $this->text_domain ); ?></button>
						<?php endif; ?>
						<button type="submit" name="end" value="<?php _e('Anzeige beenden', $this->text_domain ); ?>" onclick="classifieds.toggle_end('<?php the_ID(); ?>'); return false;" ><?php _e('Anzeige beenden', $this->text_domain ); ?></button>
						<?php elseif ( isset( $sub ) && $sub == 'favorites' ): ?>
						<a class="button" href="<?php the_permalink(); ?>"><?php _e('Anzeige ansehen', $this->text_domain ); ?></a>
						<button type="button" class="button cf-favorite-toggle <?php echo method_exists( $this, 'is_favorite_post' ) && $this->is_favorite_post( get_the_ID() ) ? 'is-active' : ''; ?>" data-post-id="<?php the_ID(); ?>">
							<span class="cf-favorite-label-default"><?php _e( 'Merken', $this->text_domain ); ?></span>
							<span class="cf-favorite-label-active"><?php _e( 'Gemerkt', $this->text_domain ); ?></span>
						</button>
						<?php elseif ( isset( $sub ) && ( $sub == 'saved' || $sub == 'ended' ) ): ?>
						<button type="submit" name="renew" value="<?php _e('Anzeige verlaengern', $this->text_domain ); ?>" onclick="classifieds.toggle_renew('<?php the_ID(); ?>'); return false;" ><?php _e('Anzeige verlaengern', $this->text_domain ); ?></button>
						<?php endif; ?>

						<?php if(current_user_can( 'delete_classifieds' ) && 'favorites' !== $sub): ?>
						<button type="submit" name="delete" value="<?php _e('Anzeige loeschen', $this->text_domain ); ?>" onclick="classifieds.toggle_delete('<?php the_ID(); ?>'); return false;" ><?php _e('Anzeige loeschen', $this->text_domain ); ?></button>
						<?php endif; ?>

						<?php endif; ?>

					</form>

					<?php if ( bp_is_my_profile() ): ?>

					<form action="#" method="post" id="confirm-form-<?php the_ID(); ?>" class="confirm-form">
						<?php wp_nonce_field('verify'); ?>
						<input type="hidden" name="action" />
						<input type="hidden" name="post_id" value="<?php the_ID(); ?>" />
						<input type="hidden" name="post_title" value="<?php the_title(); ?>" />

						<span id="cf-delete-<?php the_ID(); ?>"><?php _e('Anzeige loeschen', $this->text_domain ); ?></span>
						<span id="cf-renew-<?php the_ID(); ?>"><?php _e('Anzeige verlaengern', $this->text_domain ); ?></span>
						<span id="cf-end-<?php the_ID(); ?>"><?php _e('Anzeige beenden', $this->text_domain ); ?></span>
						<span id="cf-reserve-<?php the_ID(); ?>"><?php echo $is_reserved ? esc_html__( 'Reservierung aufheben', $this->text_domain ) : esc_html__( 'Als reserviert markieren', $this->text_domain ); ?></span>

						<?php if ( $sub == 'saved' || $sub == 'ended' ):
						$cf_payments = $this->get_options('payments');
						$cf_general = $this->get_options('general');

						//Get duration options from native fields.
						$durations = isset( $cf_general['duration_options'] ) && is_array( $cf_general['duration_options'] )
							? $cf_general['duration_options']
							: array();
						if ( empty( $durations ) ) {
							$durations = array(
								'1 week'  => '1 Woche',
								'2 weeks' => '2 Wochen',
								'3 weeks' => '3 Wochen',
								'4 weeks' => '4 Wochen',
							);
						}
						?>
						<select name="duration">
							<?php
							//make duration options
							foreach ( $durations as $duration_value => $duration_label ):
							if( empty($duration_value ) ) continue;
							$duration_text = is_string( $duration_label ) ? $duration_label : $duration_value;
							preg_match( '/\d+/', (string) $duration_value, $duration_matches );
							$duration_weeks = ! empty( $duration_matches[0] ) ? (int) $duration_matches[0] : 0;
							if($this->use_credits):
							?>
							<option value="<?php echo esc_attr( $duration_value ); ?>"><?php  echo sprintf(__('%s für %s Credits', $this->text_domain), $duration_text, $duration_weeks * $cf_payments['credits_per_week']); ?></option>
							<?php else: ?>
							<option value="<?php echo esc_attr( $duration_value ); ?>"><?php echo esc_html( $duration_text ); ?></option>
							<?php endif; ?>
							<?php endforeach; ?>
						</select>
						<?php endif; ?>
						<input type="submit" class="button confirm" value="<?php _e( 'Bestaetigen', $this->text_domain ); ?>" name="confirm" />
						<input type="submit" class="button cancel"  value="<?php _e( 'Abbrechen', $this->text_domain ); ?>" onclick="classifieds.cancel('<?php the_ID(); ?>'); return false;" />
					</form>

					<?php endif; ?>
					<div class="clear"></div>

				</div>
			</div>
		</div><!-- #post-## -->
		<div class="clear"></div>

		<?php endwhile; ?>
		</div>
		<?php /* Display navigation to next/previous pages when applicable */ ?>
		<?php echo $this->pagination( $this->pagination_bottom ); ?>
	</div><!-- .cf_tab_container -->
	<?php if(is_object($wp_query)) $wp_query->post_count = 0; ?>
	<!-- End my Classifieds -->
</div>
