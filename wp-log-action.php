<?php 
/*
Plugin Name: WP Log Action
Description: Provides a common interface to log messages the WordPress way.
Author: Webhead LLC
Author URI: http://webheadcoder.com 
Version: 0.51
*/

define( 'WPLA_VERSION', '0.51');

define( 'WPLA_PLUGIN', __FILE__);
define( 'WPLA_DIR', dirname( WPLA_PLUGIN ) );

define( 'WPLA_OPTIONS_NAME', 'wpla_options' );
define( 'WPLA_OPTIONS_PAGE_ID', 'wpla-options' );

require_once( WPLA_DIR . '/WPLA_Logger.php' );
require_once( WPLA_DIR . '/options-page.php' );
require_once( WPLA_DIR . '/wpla-activity.php' );


/**
 * Initialize the logger
 */
function wpla_setup() {
    $wpla = WPLA_Logger::get_instance();
    $levels = $wpla->get_log_types();
    foreach( $levels as $level ) {
        add_action( 'wp_log_' . $level, array( $wpla, $level ), 10, 2 );   
    }

    add_action( 'wp_log_debug_hook', array( $wpla, 'debug_hook' ), 10 );

    add_action( 'wp_log_debug_query_start', array( $wpla, 'debug_query_start' ), 10 );
    add_action( 'wp_log_debug_query_stop', array( $wpla, 'debug_query_stop' ), 10 );
    
    if ( wpla_option( 'log_doing_it_wrong', true ) ) {
        add_action( 'doing_it_wrong_run', 'wpla_doing_it_wrong_run', 10, 3 );
    }
    if ( wpla_option( 'log_deprecated', true ) ) {
        add_action( 'deprecated_function_run', 'wpla_deprecated_function_run', 10, 3 );
    }
    if ( wpla_option( 'log_plugins', true ) ) {
        add_action( 'activated_plugin', 'wpla_log_plugin_activated', 10, 2 );
        add_action( 'deactivated_plugin', 'wpla_log_plugin_deactivated', 10, 2 );

        add_action( 'delete_plugin', 'wpla_log_plugin_delete', 10 );
        add_action( 'deleted_plugin', 'wpla_log_plugin_deleted', 10, 2 );

        add_action( 'upgrader_process_complete', 'wpla_log_installed_or_updated', 10, 2 );
    }
    if ( wpla_option( 'log_core', true ) ) {
        add_action( '_core_updated_successfully', 'wpla_log_core_updated' );
    }
}
add_action( 'plugins_loaded', 'wpla_setup' );


register_activation_hook( WPLA_PLUGIN, 'wpla_activation' );
register_activation_hook( WPLA_PLUGIN, 'wpla_deactivation' );

/**
 * Setup the tables.
 */
function wpla_activation( $network_wide ) {
    global $wpdb;

    $wpla = WPLA_Logger::get_instance();

    if ( is_multisite() && $network_wide ) {
        // Get all blogs in the network and activate plugin on each one
        $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
        foreach ( $blog_ids as $blog_id ) {
            switch_to_blog( $blog_id );
            $wpla->setup_table();
            restore_current_blog();
        }
    } else {
        $wpla->setup_table();
    }

    wpla_schedule_events();
}
add_action( 'wpla_activation', 'wpla_activation' );

/**
 * Deactivation
 */
function wpla_deactivation() {
    wp_clear_scheduled_hook( 'wpla_daily_purge' );
}
add_action( 'wpla_deactivation', 'wpla_deactivation' );

/**
 * Schedule events
 */
function wpla_schedule_events() {
    if ( ! wp_next_scheduled ( 'wpla_daily_purge' ) ) {
        $datetime = new DateTime( 'midnight', wp_timezone() );
        $datetime->setTimezone( new DateTimeZone( 'UTC' ) );
        wp_schedule_event( $datetime->format( 'U' ), 'daily', 'wpla_daily_purge' );
    }
}

/**
 * Creating table whenever a new blog is created
 */
function wpla_on_create_blog( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    if ( is_plugin_active_for_network( 'wp-log-action/wp-log-action.php' ) ) {
        switch_to_blog( $blog_id );
        $wpla = WPLA_Logger::get_instance();
        $wpla->setup_table();
        restore_current_blog();
    }
}
add_action( 'wpmu_new_blog', 'wpla_on_create_blog', 10, 6 );

/**
 * Deleting the table whenever a blog is deleted
 */
function wpla_on_delete_blog( $tables ) {
    global $wpdb;
    $wpla = WPLA_Logger::get_instance();
    $tables[] = $wpla->table_name;
    return $tables;
}
add_filter( 'wpmu_drop_tables', 'wpla_on_delete_blog' );

/**
 * Add a menu item to the tools menu.
 */
function wpla_add_menu() {
    add_management_page( 'Logs', 'Logs', 'manage_options', 'wpla', 'wpla_output' );
}
add_action( 'admin_menu', 'wpla_add_menu' );


/**
 * Output the log page.
 */
function wpla_output() {
    require_once(dirname(__FILE__) . '/wpla-output-table.php');

    $wp_list_table = new WPLA_Options_Table();
    $wp_list_table->prepare_items();

    echo '<div class="wrap"><h2>WP Log Action</h2>';

    echo '<form method="get">'; //for search
    echo '<input type="hidden" name="page" value="wpla">';
    $wp_list_table->display();
    echo '</form></div><!-- .wrap -->';
}

/**
 * Handle the export here before anything is output.
 */
function wpla_handle_export() {
    global $pagenow;
    if ( 'tools.php' !== $pagenow || empty( $_REQUEST['action'] ) || $_REQUEST['action'] !== 'export' ) {
        return;
    }
    if ( !current_user_can( 'manage_options' ) ) {
        return;
    }
    $wpla = WPLA_Logger::get_instance();
    $args = $_REQUEST;
    if ( isset( $args['ids'] ) ) {
        unset( $args['ids'] );
    }
    if ( !empty( $_REQUEST['orderby'] ) ) {
        $args['orderby'] = $_REQUEST['orderby'];
    }
    if ( !empty( $_REQUEST['order'] ) ) {
        $args['order'] = $_REQUEST['order'];
    }
    unset( $args['paged'] );
    $args['items_per_page'] = -1;
    $wpla->to_csv( $args );
}
add_action( 'admin_init', 'wpla_handle_export' );

/**
 * Enqueue the styls.
 */
function wpla_admin_enqueue($hook) {
    if( stripos($hook, 'wpla' ) === FALSE)
        return;

    wp_enqueue_style( 'datepicker', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css' );
    wp_enqueue_style( 'wpla_style', 
        plugins_url('/css/wpla.css', __FILE__), 
        WPLA_VERSION 
    );

    wp_enqueue_script( 'wpla_script', 
        plugins_url('/js/wpla.js', __FILE__) , 
        array('jquery', 'jquery-ui-core', 'jquery-ui-datepicker'),
        WPLA_VERSION,
        true 
    );
}
add_action( 'admin_enqueue_scripts', 'wpla_admin_enqueue' );


/**
 * Add Settings link to plugins
 */
function wpla_settings_link($links, $file) {
    static $this_plugin;
    if (!$this_plugin) $this_plugin = plugin_basename( WPLA_PLUGIN );
    if ($file == $this_plugin){
        $settings_link = '<a href="' . admin_url( 'options-general.php?page=' . WPLA_OPTIONS_PAGE_ID . ' ' ) . '">'.__( 'Settings' ).'</a>';
        array_unshift($links, $settings_link);
    }
    return $links;
 }
add_filter('plugin_action_links', 'wpla_settings_link', 10, 2 );

/**
 * Get option
 */
function wpla_option( $name, $default = false ) {
    $options = get_option( WPLA_OPTIONS_NAME );
    if ( !empty( $options ) && isset( $options[$name] ) ) {
        $ret = $options[$name];
    }
    else {
        $ret = $default;
    }
    return $ret;
}

/**
 * Purge the db 
 */
function wpla_purge() {
    $keep = wpla_option( 'keep', MONTH_IN_SECONDS * 12 );
    if ( empty( $keep ) ) {
        return;
    }
    $keep = $keep / MONTH_IN_SECONDS;
    $dt = new DateTime( $keep . ' months ago', new DateTimeZone( 'UTC' ) );
    $wpla = WPLA_Logger::get_instance();
    $wpla->delete( array( 'log_time_end' => $dt->format( 'Y-m-d H:i:s' ) ) );
}
add_action( 'wpla_daily_purge', 'wpla_purge', 99 );