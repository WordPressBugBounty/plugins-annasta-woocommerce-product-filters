<?php

function awf_add_rank_math_support() {
  if( is_shop() || ( is_product_category() && ( 'yes' === get_option( 'awf_archive_components_support', 'yes' ) ) ) ) {
    if( in_array( get_option( 'awf_page_title', 'wc_default' ), array( 'seo', 'awf_default' ) ) ) {
        add_filter( 'rank_math/frontend/title', function( $title ) {
          $title = '';

          return $title;
        });
    }

    if( 'yes' === get_option( 'awf_add_seo_meta_description', 'no' ) ) {
      add_filter( 'rank_math/frontend/description', function( $description ){
        $description = '';

        return $description;
      });
    }
  }
}

add_action( 'wp', 'awf_add_rank_math_support' );

?>