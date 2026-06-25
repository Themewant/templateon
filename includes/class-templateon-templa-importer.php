<?php
namespace TEMPLATEON\StarterLibrary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEMPLA_Importer {

    public function import( array $template ) {
       
        if ( function_exists( 'set_time_limit' ) ) {
            @set_time_limit( 0 ); // phpcs:ignore Squiz.PHP.DiscouragedFunctions.Discouraged
        }
        wp_raise_memory_limit( 'admin' );
        ignore_user_abort( true );

        ob_start();

        $settings_done = ! empty( $template['settings'] ) ? $this->import_settings( $template['settings'] ) : true;
        $kit_done      = ! empty( $template['kit'] )      ? $this->import_kit( $template['kit'] )           : true;
        $xml_done      = ! empty( $template['xml'] )      ? $this->import_xml( $template['xml'] )           : true;
        $form_done     = ! empty( $template['form'] )     ? $this->import_form( $template['form'] )         : true;

        if ( $kit_done && $xml_done && $settings_done && $form_done ) {
            $this->assign_menu_locations();
            if ( ! empty( $template['default_homepage'] ) ) {
                $this->assign_homepage( $template['default_homepage'] );
            }
            $this->clear_elementor_cache();

            if ( ob_get_level() > 0 ) {
                ob_end_clean();
            }
            wp_send_json_success( [ 'message' => __( 'Content imported.', 'templateon' ) ] );
        }

        if ( ob_get_level() > 0 ) {
            ob_end_clean();
        }
        wp_send_json_error( [ 'message' => __( 'Import failed.', 'templateon' ) ] );
    }


    /**
     * Host allowlist for every remote URL the importer contacts.
     * @param string $url URL from the remote payload.
     * @return bool
     */
    private function is_trusted_url( $url ) {
        if ( ! is_string( $url ) || '' === $url ) {
            return false;
        }

        $host = wp_parse_url( $url, PHP_URL_HOST );
        if ( ! $host ) {
            return false;
        }
        $host = strtolower( $host );

        $allowed_hosts = (array) apply_filters(
            'templateon_trusted_asset_hosts',
            [ 'wpeasyelements.com' ]
        );

        foreach ( $allowed_hosts as $allowed ) {
            $allowed = strtolower( (string) $allowed );
            if ( '' === $allowed ) {
                continue;
            }
           
            if ( $host === $allowed || substr( $host, -( strlen( $allowed ) + 1 ) ) === '.' . $allowed ) {
                return true;
            }
        }

        return false;
    }

    private function import_settings( $url ) {
        if ( ! $this->is_trusted_url( $url ) ) {
            return true;
        }
        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );
        if ( is_wp_error( $response ) ) {
            return true;
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return true;
        }
        foreach ( $data as $option => $value ) {
            if ( ! is_string( $option ) || '' === $option || ! $this->is_allowed_option( $option ) ) {
                continue;
            }
            update_option( $option, $value );
        }
        return true;
    }

    private function import_form( $url ) {
        if ( ! $this->is_trusted_url( $url ) ) {
            return true;
        }
        $response = wp_remote_get( $url, [ 'timeout' => 30 ] );
        if ( is_wp_error( $response ) ) {
            return true;
        }
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            return true;
        }
        foreach ( $data as $option => $value ) {
            if ( ! is_string( $option ) || '' === $option || ! $this->is_allowed_option( $option ) ) {
                continue;
            }
            update_option( $option, $value );
        }
        return true;
    }

    /**
     * Strict allowlist for imported options.
     *
     * Imported settings/form data come from remote JSON, so we never trust the
     * option names blindly. Instead of trying to block dangerous names (a
     * blocklist can always be bypassed), we only allow option names that begin
     * with a known, plugin-scoped prefix. Everything else — core options such as
     * siteurl/admin_email/active_plugins, secrets, user roles, anything
     * unexpected — is ignored. Extensions can extend the prefix list via the
     * `templateon_allowed_option_prefixes` filter.
     *
     * @param string $option Option name from the remote payload.
     * @return bool
     */
    private function is_allowed_option( $option ) {
        $allowed_prefixes = (array) apply_filters(
            'templateon_allowed_option_prefixes',
            [ 'easy_element_', 'easyel_' ]
        );

        foreach ( $allowed_prefixes as $prefix ) {
            if ( '' !== $prefix && 0 === strpos( $option, $prefix ) ) {
                return true;
            }
        }

        return false;
    }

    private function import_kit( $url ) {
        if ( ! did_action( 'elementor/loaded' ) ) {
            return true;
        }
        if ( ! $this->is_trusted_url( $url ) ) {
            return false;
        }

        $tmp = download_url( $url );
        if ( is_wp_error( $tmp ) ) {
            return false;
        }

        try {
            $module = \Elementor\Plugin::$instance->app->get_component( 'import-export' );
            $upload = $module->upload_kit( $tmp, 'local' );
            $module->import_kit( $upload['session'], [], false );
            wp_delete_file( $tmp );
            return true;
        } catch ( \Throwable $e ) {
            wp_delete_file( $tmp );
            return false;
        }
    }


    private function import_xml( $url ) {

        if ( ! $this->is_trusted_url( $url ) ) {
            return false;
        }
        $tmp = download_url( $url );
        if ( is_wp_error( $tmp ) ) {
            return false;
        }

        if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress core constant that tells WP to load importer plugins; not owned by this plugin.
            define( 'WP_LOAD_IMPORTERS', true );
        }


        if ( ! function_exists( 'get_plugins' ) ) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        $dir = '';

        foreach ( $plugins as $plugin_file => $data ) {

            if ( $plugin_file === 'wordpress-importer/wordpress-importer.php' ) {
                
                $dir = trailingslashit( WP_PLUGIN_DIR ) . dirname( $plugin_file );
                break;
            }
        }

        if ( empty( $dir ) || ! is_dir( $dir ) ) {
            wp_delete_file( $tmp );
            return false;
        }

        if ( file_exists( $dir . '/compat.php' ) ) {
            require_once $dir . '/compat.php';
        }

        if ( ! class_exists( 'WordPress\\XML\\XMLProcessor' ) && file_exists( $dir . '/php-toolkit/load.php' ) ) {
            require_once $dir . '/php-toolkit/load.php';
        }

        $parsers_dir = $dir . '/parsers';

        if ( is_dir( $parsers_dir ) ) {
            foreach ( [
                'class-wxr-parser.php',
                'class-wxr-parser-simplexml.php',
                'class-wxr-parser-xml.php',
                'class-wxr-parser-regex.php',
                'class-wxr-parser-xml-processor.php'
            ] as $f ) {

                $file = $parsers_dir . '/' . $f;

                if ( file_exists( $file ) ) {
                    require_once $file;
                }
            }
        }

        if ( ! class_exists( 'WP_Importer' ) && file_exists( ABSPATH . 'wp-admin/includes/class-wp-importer.php' ) ) {
            require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
        }

        if ( ! class_exists( 'WP_Importer' ) ) {
            wp_delete_file( $tmp );
            return false;
        }

        if ( ! class_exists( 'WP_Import' ) && file_exists( $dir . '/class-wp-import.php' ) ) {
            require_once $dir . '/class-wp-import.php';
        }

        if ( ! class_exists( 'WP_Import' ) ) {
            wp_delete_file( $tmp );
            return false;
        }

        $importer = new \WP_Import();
        $importer->fetch_attachments = false;
        // Plugin-owned filter so site owners can tweak importer options (e.g. URL rewriting).
        $importer->options = apply_filters( 'templa_wp_import_options', [ 'rewrite_urls' => false ] );

        ob_start();
        $data = $importer->parse( $tmp );
        ob_end_clean();

        if ( is_wp_error( $data ) ) {
            wp_delete_file( $tmp );
            return false;
        }

        $importer->get_authors_from_import( $data );
        $importer->posts      = $data['posts'] ?? [];
        $importer->terms      = $data['terms'] ?? [];
        $importer->categories = $data['categories'] ?? [];
        $importer->tags       = $data['tags'] ?? [];
        $importer->base_url   = esc_url( $data['base_url'] ?? '' );

        wp_suspend_cache_invalidation( true );
        wp_defer_term_counting( true );
        wp_defer_comment_counting( true );

        ob_start();
        $importer->process_categories();
        $importer->process_tags();
        $importer->process_terms();
        $importer->process_posts();
        ob_end_clean();

        wp_suspend_cache_invalidation( false );
        wp_defer_term_counting( false );
        wp_defer_comment_counting( false );

        if ( method_exists( $importer, 'backfill_parents' ) ) {
            $importer->backfill_parents();
        }
        if ( method_exists( $importer, 'backfill_attachment_urls' ) ) {
            $importer->backfill_attachment_urls();
        }
        if ( method_exists( $importer, 'remap_featured_images' ) ) {
            $importer->remap_featured_images();
        }

        wp_cache_flush();
        wp_delete_file( $tmp );
        return true;
    }

    private function clear_elementor_cache() {
        if ( class_exists( '\\Elementor\\Plugin' ) ) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
        }
    }

    private function assign_menu_locations() {
        $locations = (array) get_theme_mod( 'nav_menu_locations', [] );
        $menus     = wp_get_nav_menus();
        if ( empty( $menus ) ) {
            return;
        }

        $registered = get_registered_nav_menus();
        foreach ( $menus as $menu ) {
            if ( empty( $menu->term_id ) ) {
                continue;
            }
            foreach ( $registered as $loc_slug => $loc_name ) {
                if ( stripos( $loc_slug, 'primary' ) !== false || stripos( $loc_slug, 'main' ) !== false || stripos( $loc_slug, 'header' ) !== false ) {
                    if ( empty( $locations[ $loc_slug ] ) ) {
                        $locations[ $loc_slug ] = $menu->term_id;
                    }
                }
            }
            if ( isset( $registered['menu-1'] ) && empty( $locations['menu-1'] ) ) {
                $locations['menu-1'] = $menu->term_id;
            }
        }
        set_theme_mod( 'nav_menu_locations', $locations );
    }

    private function assign_homepage( $title ) {
        $query = new \WP_Query( [
            'post_type'      => 'page',
            'title'          => $title,
            'post_status'    => 'all',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ] );

        if ( ! empty( $query->post ) ) {
            update_option( 'page_on_front', $query->post->ID );
            update_option( 'show_on_front', 'page' );
        }

        $blog = new \WP_Query( [
            'post_type'      => 'page',
            'title'          => 'Blog',
            'post_status'    => 'all',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        ] );
        if ( ! empty( $blog->post ) ) {
            update_option( 'page_for_posts', $blog->post->ID );
        }
    }
}
