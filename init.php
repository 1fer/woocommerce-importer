<?php
/**
 * =============================================================================
 * Woocommerce custom product importer for berni company
 * =============================================================================
 * Plugin Name: Berni to woocommerce
 * Plugin URI:  
 * Description: Import products from berni yml to woocommerce.
 * Author: Panevnyk Roman
 * Author URI:
 * License:
 * License URI:
 * WC requires at least: 3.0.0
 * WC tested up to: 3.7.0
 * 
 * @author Panevnyk Roman <panevnyk.roman@gmail.com>
 * @package WordPress
 * @version 1.0.0
 */ 

if ( ! defined( 'BERNI_DIR' ) ) {
   define( 'BERNI_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'BERNI_INC' ) ) {
   define( 'BERNI_INC', BERNI_DIR . 'includes/' );
}

if ( ! defined( 'BERNI_CACHE' ) ) {
   define( 'BERNI_CACHE', BERNI_DIR . 'cache/' );
}

if ( ! defined( 'BERNI_TEMPLATES' ) ) {
   define( 'BERNI_TEMPLATES', BERNI_DIR . 'templates/' );
}

if ( ! defined( 'BERNI_URL' ) ) {
   define( 'BERNI_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'BERNI_JS' ) ) {
   define( 'BERNI_JS', BERNI_URL . 'access/js/' );
}

if ( ! defined( 'BERNI_CSS' ) ) {
   define( 'BERNI_CSS', BERNI_URL . 'access/css/' );
}

/** 
 * =============================================================================
 * Init new berni plugin
 * =============================================================================
 * @action plugins_loaded
 * 
 * @method berni_init
 * @param null
 * 
 * @return void
 * @since 1.0.0
 * @author Panevnyk Roman <panevnyk.roman@gmail.com>
 */
function berni_init() {

   require_once( BERNI_INC . 'class.berni.php' );

}
add_action('plugins_loaded', 'berni_init', 0);

/** 
 * =============================================================================
 * Method on install Berni Plugin
 * =============================================================================
 * @hook register_activation_hook
 * 
 * @method install
 * @param null
 * 
 * @return void
 * @since 1.0.0
 * @author Panevnyk Roman <panevnyk.roman@gmail.com>
 */
function install() {

   global $wpdb;

   $table_berni = $wpdb->prefix . 'berni';
   $table_berni_images = $wpdb->prefix . 'berni_images';
   $table_berni_params = $wpdb->prefix . 'berni_params';
   $charset_collate = $wpdb->get_charset_collate();

   $sql = "
   CREATE TABLE $table_berni (
       id mediumint(9) NOT NULL AUTO_INCREMENT,
       berni_id int(11) NOT NULL,
       price int(11) NOT NULL,
       name varchar(255) NOT NULL,
       vendor varchar(55),
       vendor_code varchar(55),
       description TEXT,
       available tinyint(1) NOT NULL,
       category int(11),
       status tinyint(1) NOT NULL,
       date_create DATETIME NOT NULL,
       date_modificate DATETIME NOT NULL,
       PRIMARY KEY (id)
   ) $charset_collate;
   CREATE TABLE $table_berni_images (
       id mediumint(9) NOT NULL AUTO_INCREMENT,
       berni_id int(11) NOT NULL,
       picture varchar(255) DEFAULT '' NOT NULL,
       date_create DATETIME NOT NULL,
       date_modificate DATETIME NOT NULL,
       PRIMARY KEY (id)
   ) $charset_collate;
   CREATE TABLE $table_berni_params (
       id mediumint(9) NOT NULL AUTO_INCREMENT,
       berni_id int(11) NOT NULL,
       param_name varchar(255) DEFAULT '' NOT NULL,
       param_value varchar(255) DEFAULT '' NOT NULL,
       date_create DATETIME NOT NULL,
       date_modificate DATETIME NOT NULL,
       PRIMARY KEY (id)
   ) $charset_collate;
   ";

   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   dbDelta( $sql );

}
register_activation_hook(__FILE__, 'install');

/** 
 * =============================================================================
 * Method on unistall Berni Plugin
 * =============================================================================
 * @hook register_deactivation_hook
 * @hook register_uninstall_hook
 * 
 * @method unistall
 * @param null
 * 
 * @return void
 * @since 1.0.0
 * @author Panevnyk Roman <panevnyk.roman@gmail.com>
 * TODO: make unistall online when plugin was unistalled not deactivated.
 */
function unistall(){

   global $wpdb;

   $table_berni = $wpdb->prefix . 'berni';
   $table_berni_images = $wpdb->prefix . 'berni_images';
   $table_berni_params = $wpdb->prefix . 'berni_params';

   $sql = "DROP TABLE IF EXISTS 
            $table_berni, 
            $table_berni_images,
            $table_berni_params";

   $wpdb->query($sql);

}
register_deactivation_hook(__FILE__, 'unistall');
register_uninstall_hook(__FILE__, 'unistall');