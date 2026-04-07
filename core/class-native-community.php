<?php
/**
 * PS-Community Integration für Kleinanzeigen
 * 
 * Zeigt Benutzer-Anzeigen dans das ps-community Member-Profil
 *
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PS_Native_Community {

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Hook initialisierung
     */
    private function init_hooks() {
        // Member-Profil Anzeigen als Tab/Section ergänzen
        add_action( 'cpc_members_directory_user_details_hook', array( $this, 'render_member_classifieds_summary' ), 10, 1 );
        add_action( 'cpc_members_profile_main_hook', array( $this, 'render_member_classifieds_section' ), 10, 1 );

        // Shortcode für Benutzer-Anzeigen
        add_shortcode( 'cf_user_classifieds', array( $this, 'render_user_classifieds_shortcode' ) );
    }

    /**
     * Zeige Anzeigen-Zusammenfassung dans Member-Directory-Karte
     * 
     * @param object $user WP_User
     */
    public function render_member_classifieds_summary( $user ) {
        if ( ! is_object( $user ) || empty( $user->ID ) ) {
            return;
        }

        $count = $this->get_user_classifieds_count( $user->ID );
        if ( $count <= 0 ) {
            return;
        }

        echo '<div class="cf-member-summary">';
        echo '<strong>' . esc_html__( 'Anzeigen', 'ps-kleinanzeigen' ) . ':</strong> ';
        echo '<a href="' . esc_url( $this->get_member_classifieds_url( $user->ID ) ) . '">';
        echo esc_html( $count );
        echo '</a>';
        echo '</div>';
    }

    /**
     * Zeige Anzeigen-Sektion dans Member-Profil
     * 
     * @param object $user WP_User
     */
    public function render_member_classifieds_section( $user ) {
        if ( ! is_object( $user ) || empty( $user->ID ) ) {
            return;
        }

        $count = $this->get_user_classifieds_count( $user->ID );
        if ( $count <= 0 ) {
            return;
        }

        echo '<div class="cf-member-classifieds-section">';
        echo '<h3>' . esc_html__( 'Meine Anzeigen', 'ps-kleinanzeigen' ) . '</h3>';
        echo do_shortcode( '[cf_user_classifieds user_id="' . intval( $user->ID ) . '"]' );
        echo '</div>';
    }

    /**
     * Shortcode: [cf_user_classifieds user_id="123" limit="5"]
     * 
     * @param array $atts Shortcode Attribute
     * @return string HTML
     */
    public function render_user_classifieds_shortcode( $atts ) {
        $atts = shortcode_atts(
            array(
                'user_id' => 0,
                'limit'  => 10,
            ),
            $atts,
            'cf_user_classifieds'
        );

        $user_id = (int) $atts['user_id'];
        $limit   = (int) $atts['limit'];

        if ( $user_id <= 0 ) {
            return '';
        }

        $args = array(
            'post_type'      => 'classifieds',
            'post_status'    => 'publish',
            'author'         => $user_id,
            'posts_per_page' => $limit,
        );

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return '<p>' . esc_html__( 'Keine Anzeigen gefunden.', 'ps-kleinanzeigen' ) . '</p>';
        }

        ob_start();
        echo '<div class="cf-user-classifieds-grid">';

        while ( $query->have_posts() ) {
            $query->the_post();
            global $post;

            $price = get_post_meta( $post->ID, '_cf_cost', true );
            $duration = get_post_meta( $post->ID, '_cf_duration', true );
            $image_id = get_post_thumbnail_id( $post->ID );
            $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium_large' ) : '';

            echo '<div class="cf-classified-card">';
            if ( $image_url ) {
                echo '<a href="' . esc_url( get_permalink() ) . '" class="cf-card-image">';
                echo '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( get_the_title() ) . '" loading="lazy">';
                echo '</a>';
            }
            echo '<div class="cf-card-content">';
            echo '<h4><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4>';
            if ( $price ) {
                echo '<div class="cf-price">' . esc_html( $price ) . '</div>';
            }
            if ( $duration ) {
                echo '<div class="cf-duration">' . esc_html__( 'Läuft bis:', 'ps-kleinanzeigen' ) . ' ' . esc_html( $duration ) . '</div>';
            }
            echo '</div>';
            echo '</div>';
        }

        echo '</div>';
        wp_reset_postdata();

        return ob_get_clean();
    }

    /**
     * Bekomme Anzeigen-URL für einen User
     * 
     * @param int $user_id
     * @return string URL
     */
    public static function get_member_classifieds_url( $user_id ) {
        $user_id = (int) $user_id;
        if ( $user_id <= 0 ) {
            return '';
        }

        // Nutze ps-community Profile URL und ergänze Query-Param
        if ( function_exists( 'cpc_members_get_profile_url' ) ) {
            $profile_url = cpc_members_get_profile_url( $user_id );
            return add_query_arg( 'cf_tab', 'classifieds', $profile_url );
        }

        // Fallback auf Author-URL
        return add_query_arg( 'cf_tab', 'classifieds', get_author_posts_url( $user_id ) );
    }

    /**
     * Bekomme Anzeigen-Count für einen User
     * 
     * @param int $user_id
     * @return int
     */
    public static function get_user_classifieds_count( $user_id ) {
        $user_id = (int) $user_id;
        if ( $user_id <= 0 ) {
            return 0;
        }

        return (int) count_user_posts( $user_id, 'classifieds', true );
    }
}

// Initialisierung
if ( function_exists( 'cpc_members_is_core_enabled' ) && cpc_members_is_core_enabled() ) {
    new PS_Native_Community();
}
?>
