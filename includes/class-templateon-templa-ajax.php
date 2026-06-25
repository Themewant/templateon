<?php
namespace TEMPLATEON\StarterLibrary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEMPLA_Ajax {

    private $templates;
    private $importer;

    public function __construct( TEMPLA_Templates $templates, TEMPLA_Importer $importer ) {
        $this->templates = $templates;
        $this->importer  = $importer;

        add_action( 'wp_ajax_templateon_toggle_favorite', [ $this, 'toggle_favorite' ] );
        add_action( 'wp_ajax_templateon_sync_library',   [ $this, 'sync_library' ] );
        add_action( 'wp_ajax_templateon_install_plugin', [ $this, 'install_plugin' ] );
        add_action( 'wp_ajax_templateon_activate_plugin',[ $this, 'activate_plugin' ] );
        add_action( 'wp_ajax_templateon_install_theme',  [ $this, 'install_theme' ] );
        add_action( 'wp_ajax_templateon_activate_theme', [ $this, 'activate_theme' ] );
        add_action( 'wp_ajax_templateon_import_content', [ $this, 'import_content' ] );
    }

    private function verify( $cap = 'manage_options' ) {
        check_ajax_referer( 'templateon_nonce', 'nonce' );
        if ( ! current_user_can( $cap ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'templateon' ) ] );
        }
    }

    public function toggle_favorite() {
        $this->verify();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in $this->verify().
        $slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
        if ( empty( $slug ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid template.', 'templateon' ) ] );
        }

        wp_send_json_success( $this->templates->toggle_favorite( $slug ) );
    }

    public function sync_library() {
        $this->verify();
        $data = $this->templates->sync_library();
        wp_send_json_success( [
            'message' => __( 'Library synced.', 'templateon' ),
            'count'   => is_array( $data ) ? count( $data ) : 0,
        ] );
    }

    public function install_plugin() {
        $this->verify( 'install_plugins' );

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in $this->verify().
        $slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

        if ( empty( $slug ) ) {
            wp_send_json_error( [ 'message' => __( 'Plugin slug missing.', 'templateon' ) ] );
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

       
        $download_url = (string) apply_filters( 'templateon_plugin_download_url', '', $slug );

        if ( '' === $download_url ) {
            $api = plugins_api( 'plugin_information', [ 'slug' => $slug, 'fields' => [ 'sections' => false ] ] );
            if ( is_wp_error( $api ) ) {
                wp_send_json_error( [ 'message' => $api->get_error_message() ] );
            }
            $download_url = $api->download_link;
        }

        $skin     = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader( $skin );
        $result   = $upgrader->install( $download_url );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }

        wp_send_json_success( [ 'message' => __( 'Plugin installed.', 'templateon' ) ] );
    }

    public function activate_plugin() {
        $this->verify( 'activate_plugins' );

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- Nonce verified in $this->verify().
        $slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
        $init = isset( $_POST['init'] ) ? sanitize_text_field( wp_unslash( $_POST['init'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $all_plugins = get_plugins();

        if ( empty( $init ) || ! isset( $all_plugins[ $init ] ) ) {
            $init = '';
            foreach ( $all_plugins as $file => $_meta ) {
                if ( dirname( $file ) === $slug ) {
                    $init = $file;
                    break;
                }
            }
        }

        if ( empty( $init ) ) {
            wp_send_json_error( [ 'message' => __( 'Plugin not found.', 'templateon' ) ] );
        }

        $result = activate_plugin( $init );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        wp_send_json_success( [ 'message' => __( 'Plugin activated.', 'templateon' ) ] );
    }

    public function install_theme() {
        $this->verify( 'install_themes' );

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in $this->verify().
        $slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';

        if ( empty( $slug ) ) {
            wp_send_json_error( [ 'message' => __( 'Theme slug missing.', 'templateon' ) ] );
        }

        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/theme.php';

        $api = themes_api( 'theme_information', [ 'slug' => $slug, 'fields' => [ 'sections' => false ] ] );
        if ( is_wp_error( $api ) ) {
            wp_send_json_error( [ 'message' => $api->get_error_message() ] );
        }

        $skin     = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Theme_Upgrader( $skin );
        $result   = $upgrader->install( $api->download_link );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ] );
        }
        wp_send_json_success( [ 'message' => __( 'Theme installed.', 'templateon' ) ] );
    }

    public function activate_theme() {
        $this->verify( 'switch_themes' );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in $this->verify().
        $slug = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
        if ( empty( $slug ) ) {
            wp_send_json_error( [ 'message' => __( 'Theme slug missing.', 'templateon' ) ] );
        }
        switch_theme( $slug );
        wp_send_json_success( [ 'message' => __( 'Theme activated.', 'templateon' ) ] );
    }

    public function import_content() {
        $this->verify();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in $this->verify().
        $slug = isset( $_POST['template'] ) ? sanitize_key( wp_unslash( $_POST['template'] ) ) : '';
        $template = null;
        foreach ( $this->templates->get_all() as $t ) {
            if ( $t['slug'] === $slug ) {
                $template = $t;
                break;
            }
        }
        if ( ! $template ) {
            wp_send_json_error( [ 'message' => __( 'Template not found.', 'templateon' ) ] );
        }

        if ( ! apply_filters( 'templa_can_import_template', true, $template ) ) {
            wp_send_json_error( [ 'message' => __( 'You do not have permission to import this template.', 'templateon' ) ] );
        }

        $this->importer->import( $template );
    }
}
