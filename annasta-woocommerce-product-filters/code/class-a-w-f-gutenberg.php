<?php

defined( 'ABSPATH' ) or die( 'Access denied' );

if( ! class_exists( 'A_W_F_gutenberg' ) ) {

    class A_W_F_gutenberg {

        public function __construct() {
            add_action( 'init', array( $this, 'register_block' ) );
            add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_scripts' ), 10 );
            /* @todo hide the annasta Widget in the future versions
            add_filter( 'widget_types_to_hide_from_legacy_widget_block', array( $this, 'hide_awf_widget' ) );
            */
        }

        public function hide_awf_widget( $widget_types ) {
            $widget_types[] = 'awf_widget';
            return $widget_types;
        }

        public function enqueue_block_scripts() {
            wp_enqueue_style( 'awf-nouislider', A_W_F_PLUGIN_URL . '/styles/nouislider.min.css', array( 'awf-block-editor-style' ), A_W_F::$plugin_version );
			wp_enqueue_script( 'awf-wnumb', A_W_F_PLUGIN_URL . '/code/js/wNumb.js', array( 'awf-block-editor' ) );

            wp_enqueue_style( 'awf-block-font-awesome', A_W_F_PLUGIN_URL . '/styles/awf-font-awesome.css', array( 'awf-block-editor-style' ), A_W_F::$plugin_version );
            wp_enqueue_style( 'awf-block-style', A_W_F_PLUGIN_URL . '/styles/awf.css', array( 'awf-block-editor-style' ), A_W_F::$plugin_version );

            $uploads_dir = wp_upload_dir();
            $path = '/annasta-filters/css/' . get_option( 'awf_style_options_file' );
            if( file_exists( $uploads_dir['basedir'] . $path ) ) {
                $path = $uploads_dir['baseurl'] . $path;
                if ( is_ssl() ) { $path = str_replace( 'http://', 'https://', $path ); }
            
                wp_enqueue_style( 'awf-block-style-options', $path, array( 'awf-block-editor-style' ), A_W_F::$plugin_version );
            }

            wp_enqueue_script( 'awf-nouislider', A_W_F_PLUGIN_URL . '/code/js/nouislider.min.js', array( 'awf-block-editor' ) );
            if( A_W_F::$premium ) {
                wp_enqueue_script( 'awf-premium', A_W_F_PLUGIN_URL . '/code/js/awf-premium.js', array( 'awf-block-editor', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'awf-nouislider' ), A_W_F::$plugin_version );
            }
            wp_enqueue_script( 'awf', A_W_F_PLUGIN_URL . '/code/js/awf.js', array( 'awf-block-editor', 'jquery', 'jquery-ui-core', 'jquery-ui-sortable', 'awf-nouislider' ), A_W_F::$plugin_version );
            
        }

        public function register_block() {
            wp_register_style( 'awf-block-editor-style', A_W_F_PLUGIN_URL . '/styles/awf-block-editor.css', false, A_W_F::$plugin_version );
            wp_register_script( 'awf-block-editor', A_W_F_PLUGIN_URL . '/code/js/awf-block-editor.js', array( 'jquery', 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-element' ) );

            $presets = array( array(
                    'label' => __( 'None', 'annasta-filters' ),
                    'value' => 0
            ) );
            foreach( array_keys( A_W_F::$presets ) as $preset_id ) {
                $label = get_option( 'awf_preset_' . $preset_id . '_name', '' );
                if( empty( $label ) ) {
                    $label = sprintf( __( 'annasta Filters Preset #%1$s', 'annasta-filters' ), $preset_id );
                }
                $presets[] = array(
                    'label' => $label,
                    'value' => $preset_id
                );
            };

            wp_localize_script(
                'awf-block-editor',
                'awf_block_data',
                array(
                    'presets' => $presets,
                )
            );

            register_block_type( A_W_F_PLUGIN_PATH . 'block.json', array(
                'editor_script' => 'awf-block-editor',
                'editor_style' => 'awf-block-editor-style',
                'render_callback' => array( $this, 'render_block' )
            ) );
        }

        public function render_block( $attributes ) {

            if( ! empty( A_W_F::$front ) ) {

                if( ! isset( $attributes['annastaPreset'] ) ) {
                    $attributes['annastaPreset'] = 0;
                } else {
                    $attributes['annastaPreset'] = intval( $attributes['annastaPreset'] );
                }

                if( ! empty( $_GET['awf-block-preview'] ) ) {
                    $notice = '';

                    if( empty( $attributes['annastaPreset'] ) ) {
                        $notice = __( 'Please use the block settings to select filters preset.', 'annasta-filters' );

                    } elseif( empty( A_W_F::$presets ) ) {
                        $notice = __( 'Please use the annasta Filters > Filter presets admin section to create at least one filters preset.', 'annasta-filters' );

                    } elseif( ! isset( A_W_F::$presets[$attributes['annastaPreset']] ) ) {
                        $notice = __( 'Filters preset associated with this block may have been deleted, please use the block settings to select another preset.', 'annasta-filters' );
                    }

                    if( ! empty( $notice ) ) {
                        return '<h3 class="awf-block-preview-title">' .  __( 'annasta WooCommerce Filters', 'annasta-filters' ) . '</h3><div class="awf-block-preview-notice">' . $notice . '</div>';
                    }

                    A_W_F::$preview_mode = true;
                }

                return A_W_F::$front->display_block( $attributes['annastaPreset'], $attributes['blockID'] );
            }

            return '';
        }

    }
}

?>
