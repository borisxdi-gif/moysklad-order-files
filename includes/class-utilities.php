<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Moysklad_Utilities {
    
    public static function format_file_size( $bytes ) {
        $units = array( 'B', 'KB', 'MB', 'GB' );
        $bytes = max( $bytes, 0 );
        $pow = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        $pow = min( $pow, count( $units ) - 1 );
        $bytes /= ( 1 << ( 10 * $pow ) );
        
        return round( $bytes, 2 ) . ' ' . $units[$pow];
    }
    
    public static function get_moysklad_order_id( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }
        
        return $order->get_meta( 'ms_woo_integration_customerorder_id' );
    }
    
    public static function is_order_completed_or_cancelled( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return false;
        }
        
        $status = $order->get_status();
        return in_array( $status, array( 'completed', 'cancelled' ) );
    }
    
    public static function encode_file_to_base64( $file_path ) {
        if ( ! file_exists( $file_path ) ) {
            return false;
        }
        
        return base64_encode( file_get_contents( $file_path ) );
    }
    
    public static function validate_file_upload( $file, $max_files, $current_files_count, $max_size_total ) {
        $allowed_types = array( 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip' );
        $max_individual_size = 5242880; // 5 MB
        
        // Check file count
        if ( $current_files_count >= $max_files ) {
            return array( 'success' => false, 'message' => __( 'Maximum number of files reached.', 'moysklad-order-files' ) );
        }
        
        // Check file extension
        $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $file_ext, $allowed_types ) ) {
            return array( 'success' => false, 'message' => __( 'File type not allowed.', 'moysklad-order-files' ) );
        }
        
        // Check individual file size
        if ( $file['size'] > $max_individual_size ) {
            return array( 'success' => false, 'message' => sprintf( __( 'File size exceeds 5 MB limit.', 'moysklad-order-files' ) ) );
        }
        
        // Check total size
        if ( $file['size'] + $max_size_total > 10485760 ) { // 10 MB
            return array( 'success' => false, 'message' => __( 'Total file size would exceed 10 MB limit.', 'moysklad-order-files' ) );
        }
        
        return array( 'success' => true );
    }
    
    public static function sanitize_filename( $filename ) {
        $filename = sanitize_file_name( $filename );
        $filename = str_replace( ' ', '-', $filename );
        return preg_replace( '/[^a-zA-Z0-9.-]/', '', $filename );
    }
    
    public static function log_action( $order_id, $action, $status, $file_name = null, $file_id = null, $error_message = null, $response = null ) {
        global $wpdb;
        
        $log_table = Moysklad_DB_Setup::get_log_table();
        
        $wpdb->insert(
            $log_table,
            array(
                'order_id' => $order_id,
                'file_id' => $file_id,
                'action' => $action,
                'file_name' => $file_name,
                'status' => $status,
                'error_message' => $error_message,
                'response' => $response,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }
}