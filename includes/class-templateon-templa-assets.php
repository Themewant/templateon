<?php
namespace TEMPLATEON\StarterLibrary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEMPLA_Assets {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    public function enqueue( $hook ) {
        if ( 'toplevel_page_' . TEMPLA_SLUG !== $hook ) {
            return;
        }

        $css_path = TEMPLA_PATH . 'assets/css/templateon-admin.css';
        $js_path  = TEMPLA_PATH . 'assets/js/templateon-admin.js';
        $css_ver  = file_exists( $css_path ) ? filemtime( $css_path ) : TEMPLA_VERSION;
        $js_ver   = file_exists( $js_path )  ? filemtime( $js_path )  : TEMPLA_VERSION;

        wp_enqueue_style(
            'templa-admin-css',
            TEMPLA_ASSETS . 'css/templateon-admin.css',
            [ 'dashicons' ],
            $css_ver
        );

        wp_enqueue_script(
            'templa-admin-js',
            TEMPLA_ASSETS . 'js/templateon-admin.js',
            [ 'jquery' ],
            $js_ver,
            true
        );

        $templates = TEMPLA_Plugin::instance()->templates;

        $config = [
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'nonce'     => wp_create_nonce( 'templateon_nonce' ),
            'favorites' => $templates->get_favorites(),
            'i18n'      => [
                'import'        => __( 'Import', 'templateon' ),
                'preview'       => __( 'Preview', 'templateon' ),
                'importing'     => __( 'Importing...', 'templateon' ),
                'noTemplates'   => __( 'No templates match your search.', 'templateon' ),
                'synced'        => __( 'Library synced successfully.', 'templateon' ),
                'syncFailed'    => __( 'Sync failed. Please try again.', 'templateon' ),
                'confirmReload' => __( 'Import completed. Reload to see your new site?', 'templateon' ),
                'importError'   => __( 'Import failed. Check console for details.', 'templateon' ),
                'installing'    => __( 'Installing…', 'templateon' ),
                'activating'    => __( 'Activating…', 'templateon' ),
                'active'        => __( 'Active', 'templateon' ),
                'pluginReady'   => __( 'Plugin ready.', 'templateon' ),
                'themeReady'    => __( 'Theme activated.', 'templateon' ),
            ],
        ];

        $config = (array) apply_filters( 'templa_js_config', $config );

        wp_localize_script( 'templa-admin-js', 'TEMPLATEON', $config );
    }
}
