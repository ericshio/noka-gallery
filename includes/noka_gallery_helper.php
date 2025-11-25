<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class Noka_Gallery_Helper {
    /**
     * Fetches a list of all Noka Galleries for use in dropdowns.
     *
     * @return array Array of gallery IDs mapped to their title (e.g., [123 => 'My Gallery (ID: 123)'])
     */
    public static function get_galleries() {
        $galleries = get_posts(array(
            'post_type'      => 'noka_gallery',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ));

        $options = array(
            'none' => esc_html__( 'Select a Noka Gallery', 'noka-gallery' ),
        );

        foreach ( $galleries as $id ) {
            $options[ $id ] = get_the_title( $id ) . ' (ID: ' . $id . ')';
        }

        return $options;
    }
}