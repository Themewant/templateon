<?php
namespace TEMPLATEON\StarterLibrary;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TEMPLA_Autoloader {

    const NAMESPACE_PREFIX = 'TEMPLATEON\\StarterLibrary\\';

    public static function register() {
        spl_autoload_register( [ __CLASS__, 'autoload' ] );
    }

    public static function autoload( $class ) {
        if ( strpos( $class, self::NAMESPACE_PREFIX ) !== 0 ) {
            return;
        }

        $relative = substr( $class, strlen( self::NAMESPACE_PREFIX ) );
        $parts    = explode( '\\', $relative );
        $name     = array_pop( $parts );
        $dir      = TEMPLA_INCLUDES;

        if ( ! empty( $parts ) ) {
            $dir .= strtolower( implode( DIRECTORY_SEPARATOR, $parts ) ) . DIRECTORY_SEPARATOR;
        }

        $file = $dir . 'class-templateon-' . strtolower( str_replace( '_', '-', $name ) ) . '.php';

        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
