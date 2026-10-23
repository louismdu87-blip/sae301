<?php
/* Itération 11. */
defined( 'ABSPATH' ) || exit;

function buissiere_team_archive_order( $query ) {
    if ( ! is_admin() && $query->is_main_query() && $query->is_post_type_archive( 'equipe' ) ) {
        $query->set( 'orderby', array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
    }
}
add_action( 'pre_get_posts', 'buissiere_team_archive_order' );
