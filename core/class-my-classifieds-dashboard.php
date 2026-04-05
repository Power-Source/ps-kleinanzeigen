<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_My_Classifieds_Dashboard {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Rendert den Inhalt eines Dashboard-Tabs.
	 *
	 * @param string $tab
	 * @param int    $paged
	 * @return string
	 */
	public function render_tab( $tab, $paged = 1 ) {
		global $current_user, $wp_query;
		$current_user = wp_get_current_user();

		if ( $tab === 'messages' ) {
			ob_start();
			include $this->core->plugin_dir . 'ui-front/general/dash-messages.php';
			return (string) ob_get_clean();
		}

		$query_args = array(
			'paged'     => max( 1, (int) $paged ),
			'post_type' => 'classifieds',
			'author'    => $current_user->ID,
		);

		if ( $tab === 'favorites' ) {
			$query_args['post_status'] = 'publish';
			unset( $query_args['author'] );
			$favorite_ids = method_exists( $this->core, 'get_favorite_ids' ) ? $this->core->get_favorite_ids() : array();
			$query_args['post__in'] = ! empty( $favorite_ids ) ? array_map( 'absint', $favorite_ids ) : array( 0 );
		} elseif ( $tab === 'saved' ) {
			$query_args['post_status'] = array( 'draft', 'pending' );
		} elseif ( $tab === 'ended' ) {
			$query_args['post_status'] = 'private';
		} else {
			$query_args['post_status'] = 'publish';
		}

		query_posts( $query_args );

		ob_start();
		if ( have_posts() ) {
			echo '<div class="cf-dashboard-grid">';
			while ( have_posts() ) {
				the_post();
				include $this->core->plugin_dir . 'ui-front/general/dash-item.php';
			}
			echo '</div>';
		} else {
			echo '<p>' . __( 'Noch keine Anzeigen in diesem Bereich.', $this->core->text_domain ) . '</p>';
		}

		$content = (string) ob_get_clean();
		wp_reset_query();

		return $content;
	}
}
