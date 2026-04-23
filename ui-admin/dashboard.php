<?php if (!defined('ABSPATH')) die('No direct access allowed!'); ?>

<?php $taxonomies = get_object_taxonomies( $this->post_type ); ?>

<?php $options = $this->get_options( 'general' ); ?>

<div class="wrap">
	<h2>
		<?php _e( 'Anzeigen-Übersicht', $this->text_domain ); ?>
		<a class="button add-new-h2" href="post-new.php?post_type=<?php echo $this->post_type; ?>"><?php _e( 'Neue Anzeige erstellen', $this->text_domain ); ?></a>
	</h2>

	<h3><?php _e( 'Aktive Anzeigen', $this->text_domain ); ?></h3>
	<table class="widefat">
		<thead>
			<tr>
				<th><?php _e( 'ID', $this->text_domain ); ?></th>
				<th><?php _e( 'Titel', $this->text_domain ); ?></th>
				<th><?php _e( 'Kategorien', $this->text_domain ); ?></th>
				<th><?php _e( 'Laufzeitende', $this->text_domain ); ?></th>
				<th><?php _e( 'Bild', $this->text_domain ); ?></th>
				<th><?php _e( 'Aktionen', $this->text_domain ); ?></th>
			</tr>
		</thead>
		<tbody>

			<?php $current_user = wp_get_current_user(); ?>
			<?php query_posts( array( 'author' => $current_user->ID, 'post_type' => array( $this->post_type ), 'post_status' => 'publish' ) ); ?>
			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

			<tr>
				<td><?php the_ID(); ?></td>
				<td><?php the_title(); ?></td>
				<td><?php $_term_list = get_the_term_list(get_the_ID(), 'classifieds_categories', '',', ',''); echo is_wp_error($_term_list) ? '' : strip_tags($_term_list); ?> </td>
				<td><?php echo $this->get_expiration_date( get_the_ID() ); ?></td>
				<td>
					<?php
					if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
						if ( isset( $options['field_image_def'] ) && '' != $options['field_image_def'] )
						echo '<img width="16" height="16" title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $options['field_image_def'] . '">';
					} else {
						echo get_the_post_thumbnail( get_the_ID(), array( 16, 16 ) );
					}
					?>
				</td>
				<td>
				<a href="post.php?post=<?php the_ID(); ?>&amp;action=edit" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige bearbeiten', $this->text_domain ); ?></a> <span class="separators-<?php the_ID(); ?>"> | </span>
				<a href="javascript:classifieds.toggle_end('<?php the_ID(); ?>');" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige beenden', $this->text_domain ); ?></a> <span class="separators-<?php the_ID(); ?>"> | </span>
					<a href="javascript:classifieds.toggle_delete('<?php the_ID(); ?>');" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige loeschen', $this->text_domain ); ?></a>
					<form action="#" method="post" id="form-<?php the_ID(); ?>" class="cf-form">
						<input type="hidden" name="action" value="" />
						<input type="hidden" name="post_id" value="<?php the_ID(); ?>" />
						<input type="submit" class="button confirm" value="<?php _e( 'Bestaetigen', $this->text_domain ); ?>" name="confirm" />
						<input type="submit" class="button cancel"  value="<?php _e( 'Abbrechen', $this->text_domain ); ?>" onclick="classifieds.cancel('<?php the_ID(); ?>'); return false;" />
					</form>
				</td>
			</tr>

			<?php endwhile; ?>
			<?php wp_reset_query(); ?>

		</tbody>
	</table>

	<h3><?php _e( 'Gespeicherte Anzeigen', $this->text_domain ); ?></h3>
	<table class="widefat">
		<thead>
			<tr>
				<th><?php _e( 'ID', $this->text_domain ); ?></th>
				<th><?php _e( 'Titel', $this->text_domain ); ?></th>
				<th><?php _e( 'Kategorien', $this->text_domain ); ?></th>
				<th><?php _e( 'Laufzeitende', $this->text_domain ); ?></th>
				<th><?php _e( 'Bild', $this->text_domain ); ?></th>
				<th><?php _e( 'Aktionen', $this->text_domain ); ?></th>
			</tr>
		</thead>
		<tbody>

			<?php $current_user = wp_get_current_user(); ?>
			<?php query_posts( array( 'author' => $current_user->ID, 'post_type' => array( $this->post_type ), 'post_status' => 'draft' ) ); ?>
			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

			<tr>
				<td><?php the_ID(); ?></td>
				<td><?php the_title(); ?></td>
				<td><?php $_term_list = get_the_term_list(get_the_ID(), 'classifieds_categories', '',', ',''); echo is_wp_error($_term_list) ? '' : strip_tags($_term_list); ?> </td>
				<td><?php echo $this->get_expiration_date( get_the_ID() ); ?></td>
				<td>
					<?php
					if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
						if ( isset( $options['field_image_def'] ) && '' != $options['field_image_def'] )
						echo '<img width="16" height="16" title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $options['field_image_def'] . '">';
					} else {
						echo get_the_post_thumbnail( get_the_ID(), array( 16, 16 ) );
					}
					?>
				</td>
				<td>
				<a href="post.php?post=<?php the_ID(); ?>&amp;action=edit" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige bearbeiten', $this->text_domain ); ?></a> <span class="separators-<?php the_ID(); ?>"> | </span>
				<a href="javascript:classifieds.toggle_publish('<?php the_ID(); ?>');" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige veroeffentlichen', $this->text_domain ); ?></a> <span class="separators-<?php the_ID(); ?>"> | </span>
					<a href="javascript:classifieds.toggle_delete('<?php the_ID(); ?>');" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige loeschen', $this->text_domain ); ?></a>
					<form action="#" method="post" id="form-<?php the_ID(); ?>" class="cf-form">
						<input type="hidden" name="action" value="" />
						<input type="hidden" name="post_id" value="<?php the_ID(); ?>" />

					<?php
					$durations = array(
						'1 week'  => '1 Woche',
						'2 weeks' => '2 Wochen',
						'3 weeks' => '3 Wochen',
						'4 weeks' => '4 Wochen',
					);
					?>
					<select name="duration">
						<?php foreach ( $durations as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
						<input type="submit" class="button confirm" value="<?php _e( 'Bestätigen', $this->text_domain ); ?>" name="confirm" />
						<input type="submit" class="button cancel"  value="<?php _e( 'Abbrechen', $this->text_domain ); ?>" onclick="javascript:classifieds.cancel('<?php the_ID(); ?>'); return false;" />
					</form>
				</td>
			</tr>

			<?php endwhile; ?>
			<?php wp_reset_query(); ?>

		</tbody>
	</table>

	<h3><?php _e( 'Beendete Anzeigen', $this->text_domain ); ?></h3>
	<table class="widefat">
		<thead>
			<tr>
				<th><?php _e( 'ID', $this->text_domain ); ?></th>
				<th><?php _e( 'Titel', $this->text_domain ); ?></th>
				<th><?php _e( 'Kategorien', $this->text_domain ); ?></th>
				<th><?php _e( 'Laufzeitende', $this->text_domain ); ?></th>
				<th><?php _e( 'Bild', $this->text_domain ); ?></th>
				<th><?php _e( 'Aktionen', $this->text_domain ); ?></th>
			</tr>
		</thead>
		<tbody>

			<?php $current_user = wp_get_current_user(); ?>
			<?php query_posts( array( 'author' => $current_user->ID, 'post_type' => array( $this->post_type ), 'post_status' => 'private' ) ); ?>
			<?php if ( have_posts() ) while ( have_posts() ) : the_post(); ?>

			<tr>
				<td><?php the_ID(); ?></td>
				<td><?php the_title(); ?></td>
				<td><?php $_term_list = get_the_term_list(get_the_ID(), 'classifieds_categories', '',', ',''); echo is_wp_error($_term_list) ? '' : strip_tags($_term_list); ?> </td>
				<td><?php echo $this->get_expiration_date( get_the_ID() ); ?></td>
				<td>
					<?php
					if ( '' == get_post_meta( get_the_ID(), '_thumbnail_id', true ) ) {
						if ( isset( $options['field_image_def'] ) && '' != $options['field_image_def'] )
						echo '<img width="16" height="16" title="no image" alt="no image" class="cf-no-image wp-post-image" src="' . $options['field_image_def'] . '">';
					} else {
						echo get_the_post_thumbnail( get_the_ID(), array( 16, 16 ) );
					}
					?>
				</td>
				<td>
				<a href="post.php?post=<?php the_ID(); ?>&action=edit" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige bearbeiten', $this->text_domain ); ?></a> <span class="separators-<?php the_ID(); ?>"> | </span>
				<a href="javascript:classifieds.toggle_publish('<?php the_ID(); ?>');" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige verlaengern', $this->text_domain ); ?></a> <span class="separators-<?php the_ID(); ?>"> | </span>
					<a href="javascript:classifieds.toggle_delete('<?php the_ID(); ?>');" class="action-links-<?php the_ID(); ?>"><?php _e( 'Anzeige loeschen', $this->text_domain ); ?></a>
					<form action="#" method="post" id="form-<?php the_ID(); ?>" class="cf-form">
						<input type="hidden" name="action" value="" />
						<input type="hidden" name="post_id" value="<?php the_ID(); ?>" />
					<?php
					$durations = array(
						'1 week'  => '1 Woche',
						'2 weeks' => '2 Wochen',
						'3 weeks' => '3 Wochen',
						'4 weeks' => '4 Wochen',
					);
					?>
					<select name="duration">
						<?php foreach ( $durations as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
						<input type="submit" class="button confirm" value="<?php _e( 'Bestätigen', $this->text_domain ); ?>" name="confirm" />
						<input type="submit" class="button cancel"  value="<?php _e( 'Abbrechen', $this->text_domain ); ?>" onclick="javascript:classifieds.cancel('<?php the_ID(); ?>'); return false;" />
					</form>
				</td>
			</tr>

			<?php endwhile; ?>
			<?php wp_reset_query(); ?>

		</tbody>
	</table>
</div>