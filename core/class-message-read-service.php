<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CF_Message_Read_Service {
	/** @var Classifieds_Core */
	private $core;

	public function __construct( $core ) {
		$this->core = $core;
	}

	/**
	 * Liefert alle Konversationen eines Users (als Sender oder Empfaenger).
	 *
	 * @param int    $user_id
	 * @param string $type
	 * @return array
	 */
	public function get_user_conversations( $user_id, $type = 'all' ) {
		$conversations = array();

		$args = array(
			'post_type'      => 'cf_message',
			'posts_per_page' => 100,
			'post_status'    => 'publish',
			'meta_query'     => array( 'relation' => 'OR' ),
		);

		if ( $type === 'inbox' || $type === 'all' ) {
			$inbox = get_posts( array_merge( $args, array(
				'meta_query' => array(
					array( 'key' => '_cf_msg_recipient', 'value' => $user_id, 'compare' => '=' ),
				),
				'orderby' => 'date',
				'order'   => 'DESC',
			) ) );

			foreach ( $inbox as $msg ) {
				$thread_id = get_post_meta( $msg->ID, '_cf_msg_thread_id', true ) ?: $msg->ID;
				if ( ! isset( $conversations[ $thread_id ] ) ) {
					$conversations[ $thread_id ] = array(
						'thread_id'    => $thread_id,
						'last_message' => $msg,
						'unread'       => 0,
						'type'         => 'inbox',
					);
				}
				if ( ! (int) get_post_meta( $msg->ID, '_cf_msg_read_' . $user_id, true ) ) {
					$conversations[ $thread_id ]['unread']++;
				}
			}
		}

		if ( $type === 'outbox' || $type === 'all' ) {
			$outbox = get_posts( array_merge( $args, array(
				'author'     => $user_id,
				'meta_query' => array(
					array( 'key' => '_cf_msg_recipient', 'compare' => 'EXISTS' ),
				),
				'orderby' => 'date',
				'order'   => 'DESC',
			) ) );

			foreach ( $outbox as $msg ) {
				$thread_id = get_post_meta( $msg->ID, '_cf_msg_thread_id', true ) ?: $msg->ID;
				if ( ! isset( $conversations[ $thread_id ] ) ) {
					$conversations[ $thread_id ] = array(
						'thread_id'    => $thread_id,
						'last_message' => $msg,
						'unread'       => 0,
						'type'         => 'outbox',
					);
				}
			}
		}

		usort( $conversations, function( $a, $b ) {
			return strtotime( $b['last_message']->post_date ) - strtotime( $a['last_message']->post_date );
		} );

		return array_values( $conversations );
	}

	/**
	 * Anzahl ungelesener Nachrichten fuer einen User.
	 *
	 * @param int $user_id
	 * @return int
	 */
	public function get_unread_message_count( $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}
		if ( ! $user_id ) {
			return 0;
		}

		$msgs = get_posts( array(
			'post_type'      => 'cf_message',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_query'     => array(
				array( 'key' => '_cf_msg_recipient', 'value' => $user_id, 'compare' => '=' ),
				array( 'key' => '_cf_msg_read_' . $user_id, 'compare' => 'NOT EXISTS' ),
			),
		) );

		return count( $msgs );
	}
}
