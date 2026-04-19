<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Moysklad_DB_Setup {
    
    private static $files_table = 'moysklad_order_files';
    private static $log_table = 'moysklad_file_log';
    
    public static function get_files_table() {
        global $wpdb;
        return $wpdb->prefix . self::$files_table;
    }
    
    public static function get_log_table() {
        global $wpdb;
        return $wpdb->prefix . self::$log_table;
    }
    
    public static function create_tables() {
        global $wpdb;
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        
        $files_table = self::get_files_table();
        $log_table = self::get_log_table();
        $charset_collate = $wpdb->get_charset_collate();
        
        // Files table
        $sql_files = "CREATE TABLE IF NOT EXISTS $files_table (
            file_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT(20) UNSIGNED NOT NULL,
            file_url VARCHAR(500),
            mime_type VARCHAR(100),
            uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            synced_to_moysklad BOOLEAN DEFAULT FALSE,
            moysklad_sync_date DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY order_id (order_id),
            KEY synced_to_moysklad (synced_to_moysklad)
        ) $charset_collate;";
        
        // Log table
        $sql_log = "CREATE TABLE IF NOT EXISTS $log_table (
            log_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id BIGINT(20) UNSIGNED NOT NULL,
            file_id BIGINT(20) UNSIGNED,
            action VARCHAR(50) NOT NULL,
            file_name VARCHAR(255),
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            response TEXT,
            error_message TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY order_id (order_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        dbDelta( $sql_files );
        dbDelta( $sql_log );
        
        // Create upload directory
        self::create_upload_directory();
    }
    
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $moysklad_dir = $upload_dir['basedir'] . '/moysklad-order-files';
        
        if ( ! file_exists( $moysklad_dir ) ) {
            wp_mkdir_p( $moysklad_dir );
        }
        
        // Create .htaccess for security
        $htaccess_file = $moysklad_dir . '/.htaccess';
        if ( ! file_exists( $htaccess_file ) ) {
            $htaccess_content = "deny from all\n";
            file_put_contents( $htaccess_file, $htaccess_content );
        }
    }
    
    public static function delete_file_by_id( $file_id ) {
        global $wpdb;
        
        $files_table = self::get_files_table();
        $file = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $files_table WHERE file_id = %d",
            $file_id
        ) );
        
        if ( ! $file ) {
            return false;
        }
        
        // Delete physical file
        if ( file_exists( $file->file_path ) ) {
            unlink( $file->file_path );
        }
        
        // Delete from database
        $wpdb->delete( $files_table, array( 'file_id' => $file_id ), array( '%d' ) );
        
        return true;
    }
}
?>