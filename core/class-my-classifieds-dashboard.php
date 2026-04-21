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
		global $current_user;
		$current_user = wp_get_current_user();
		$payments_options = $this->core->get_options( 'payments' );
		$featured_enabled = ! empty( $payments_options['enable_featured'] );
		$featured_ids = array();

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

		$query = new WP_Query( $query_args );

		if ( $featured_enabled && 'active' === $tab ) {
			$featured_query = new WP_Query(
				array(
					'post_type'      => 'classifieds',
					'post_status'    => 'publish',
					'author'         => $current_user->ID,
					'posts_per_page' => 6,
					'meta_query'     => array(
						array(
							'key'     => '_cf_is_featured',
							'value'   => 1,
							'compare' => '=',
						),
					),
				)
			);

			if ( $featured_query->have_posts() ) {
				$featured_ids = wp_list_pluck( $featured_query->posts, 'ID' );
				$query_args['post__not_in'] = array_map( 'absint', $featured_ids );
				$query = new WP_Query( $query_args );
			}
		}

		ob_start();
		if ( ! empty( $featured_ids ) && isset( $featured_query ) && $featured_query->have_posts() ) {
			echo '<section class="cf-dashboard-section cf-dashboard-section-featured">';
			echo '<header class="cf-dashboard-section-head"><h3>' . esc_html__( 'Featured Anzeigen', $this->core->text_domain ) . '</h3></header>';
			echo '<div class="cf-dashboard-grid cf-dashboard-grid-featured">';
			while ( $featured_query->have_posts() ) {
				$featured_query->the_post();
				$cf_dashboard_tab = $tab;
				include $this->core->plugin_dir . 'ui-front/general/dash-item.php';
			}
			echo '</div>';
			echo '</section>';
			wp_reset_postdata();
		}

		if ( $query->have_posts() ) {
			echo '<div class="cf-dashboard-grid">';
			while ( $query->have_posts() ) {
				$query->the_post();
				$cf_dashboard_tab = $tab;
				include $this->core->plugin_dir . 'ui-front/general/dash-item.php';
			}
			echo '</div>';
		} elseif ( empty( $featured_ids ) ) {
			echo '<p>' . __( 'Noch keine Anzeigen in diesem Bereich.', $this->core->text_domain ) . '</p>';
		}

		$content = (string) ob_get_clean();
		wp_reset_postdata();

		return $content;
	}
}
