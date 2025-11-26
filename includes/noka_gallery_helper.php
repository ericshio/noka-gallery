<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Noka_Gallery_Helper {
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
            // FORCE STRING KEY to prevent JSON array conversion
            $str_id = (string) $id; 
            $options[ $str_id ] = get_the_title( $id ) . ' (ID: ' . $id . ')';
        }

        return $options;
    }
}