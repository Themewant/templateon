<?php
namespace TEMPLATEON\StarterLibrary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class TEMPLA_Plugin {

    private static $instance = null;

    public $admin;
    public $assets;
    public $ajax;
    public $importer;
    public $templates;

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->templates = new TEMPLA_Templates();
        $this->admin     = new TEMPLA_Admin( $this->templates );
        $this->assets    = new TEMPLA_Assets();
        $this->importer  = new TEMPLA_Importer();
        $this->ajax      = new TEMPLA_Ajax( $this->templates, $this->importer );
    }

}
