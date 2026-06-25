<?php
namespace TEMPLATEON\StarterLibrary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEMPLA_Admin {

    private $templates;
    public $page_hook;

    public function __construct( TEMPLA_Templates $templates ) {
        $this->templates = $templates;
        add_action( 'admin_menu', [ $this, 'register_menu' ] );
        add_filter( 'admin_body_class', [ $this, 'add_body_class' ] );
        add_filter( 'plugin_action_links_' . TEMPLA_BASENAME, [ $this, 'add_action_links' ] );
    }

    /**
     * Adds a "View Template Library" link to the plugin row actions.
     *
     * @param array $links Existing plugin action links.
     * @return array
     */
    public function add_action_links( $links ) {
        $library_link = sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
            esc_url( admin_url( 'admin.php?page=' . TEMPLA_SLUG ) ),
            esc_html__( 'View Template Library', 'templateon' )
        );

        array_unshift( $links, $library_link );

        return $links;
    }

    public function add_body_class( $classes ) {
        $screen = get_current_screen();
        if ( $screen && 'toplevel_page_' . TEMPLA_SLUG === $screen->id ) {
            $classes .= ' templa-fullscreen';
        }
        return $classes;
    }

    public function register_menu() {
        $this->page_hook = add_menu_page(
            __( 'Templateon', 'templateon' ),
            __( 'Templateon', 'templateon' ),
            'manage_options',
            TEMPLA_SLUG,
            [ $this, 'render_page' ],
            'data:image/svg+xml;base64,' . base64_encode( '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><rect x="1.5" y="1.5" width="17" height="4.5" rx="2" fill="white"/><rect x="1.5" y="8" width="5.5" height="10.5" rx="2" fill="white"/><rect x="9" y="8" width="9.5" height="4.5" rx="2" fill="white"/><rect x="9" y="14.5" width="9.5" height="4" rx="2" fill="white"/></svg>' ),
            58
        );
    }

    public function render_page() {
        $templates   = $this->templates->get_all();
        $categories  = $this->templates->get_category_tree();
        $favorites   = $this->templates->get_favorites();
        $total_count = count( $templates );
        $template_count   = count( array_filter( $templates, static fn( $t ) => 'pro' === apply_filters( 'templa_card_type', 'free', $t ) ) );
        $free_count  = $total_count - $template_count;

        include TEMPLA_TEMPLATES . 'admin-page.php';
    }

    public function get_templates_data() {
        return $this->templates;
    }
}
