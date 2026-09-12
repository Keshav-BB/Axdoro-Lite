<?php
/**
 * AXDORO Logger wrapper
 *
 * @package AXDORO_Commerce
 */

defined( 'ABSPATH' ) || exit;

class AXDORO_Logger {

    const CONTEXT = 'axdoro-commerce';

    public static function log( $message, $level = 'info' ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            if ( is_array( $message ) || is_object( $message ) ) {
                $message = print_r( $message, true );
            }
            $logger->log( $level, $message, array( 'source' => self::CONTEXT ) );
        }
    }

    public static function info( $message ) {
        self::log( $message, 'info' );
    }

    public static function error( $message ) {
        self::log( $message, 'error' );
    }

    public static function warning( $message ) {
        self::log( $message, 'warning' );
    }

    public static function debug( $message ) {
        $settings = get_option( 'axdoro_settings', array() );
        $debug    = isset( $settings['debug_mode'] ) ? $settings['debug_mode'] : 'no';
        if ( 'yes' === $debug ) {
            self::log( $message, 'debug' );
        }
    }
}
