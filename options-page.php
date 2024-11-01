<?php
/*********************************
 * Options page
 *********************************/

// don't load directly
if ( !defined('ABSPATH') )
    die('-1');

/**
 *  Add menu page
 */
function wpla_options_add_page() {
    $wpla_hook = add_options_page( 'WP Log Action', // Page title
                      'WP Log Action', // Label in sub-menu
                      'manage_options', // capability
                      WPLA_OPTIONS_PAGE_ID, // page identifier 
                      'wpla_options_do_page' ); // call back function name
                      
    add_action( "admin_enqueue_scripts-" . $wpla_hook, 'wpla_admin_scripts' );
}
add_action('admin_menu', 'wpla_options_add_page');

/**
 * Init plugin options to white list our options
 */
function wpla_options_init() {
    register_setting( 'wpla_options_options', WPLA_OPTIONS_NAME, 'wpla_options_validate' );
}
add_action('admin_init', 'wpla_options_init' );


/**
 * Draw the menu page itself
 */
function wpla_options_do_page() {
    if ( !current_user_can( 'manage_options' ) ) { 
     wp_die( __( 'You do not have sufficient permissions to access this page.' ) ); 
    } 
    ?>
    <div class="wrap">
            <div class="wpla-header">
                <div class="wpla-description">
                <h2>WP Log Action</h2>
                    <p class="intro">
                        WP Log Action logs a few things by default.  You can turn them off here.
                    </p>
                </div>
                <div class="wpla-donate">
                    <p>If this plugin has helped you, please consider giving back.</p>
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
                    <input type="hidden" name="cmd" value="_s-xclick">
                    <input type="hidden" name="hosted_button_id" value="HRNDMLP4A5UQ8">
                    <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
                    <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                    </form>    
                </div>
            </div>
            <div class="clear"></div>
            <hr>
            <form method="post" action="options.php">
                <?php settings_fields( 'wpla_options_options' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">Default Logging</th>
                        <td>
                            <fieldset>
                                <p>
                                    <label for="wpla_log_doing_it_wrong">
                                        <input type="checkbox" id="wpla_log_doing_it_wrong" name="<?php echo WPLA_OPTIONS_NAME;?>[log_doing_it_wrong]" value="1" <?php checked( 1, wpla_option( 'log_doing_it_wrong', true ) ) ?> /> Log "Doing it Wrong" errors.</label> 
                                </p>
                                <p>
                                    <label for="wpla_log_deprecated">
                                        <input type="checkbox" id="wpla_log_deprecated" name="<?php echo WPLA_OPTIONS_NAME;?>[log_deprecated]" value="1" <?php checked( 1, wpla_option( 'log_deprecated', true ) ) ?> /> Log deprecated warnings.</label> 
                                </p>
                                <p>
                                    <label for="wpla_log_plugins">
                                        <input type="checkbox" id="wpla_log_plugins" name="<?php echo WPLA_OPTIONS_NAME;?>[log_plugins]" value="1" <?php checked( 1, wpla_option( 'log_plugins', true ) ) ?> /> Log plugin activity.</label> 
                                </p>
                                <p>
                                    <label for="wpla_log_core">
                                        <input type="checkbox" id="wpla_log_core" name="<?php echo WPLA_OPTIONS_NAME;?>[log_core]" value="1" <?php checked( 1, wpla_option( 'log_core', true ) ) ?> /> Log WordPress core activity.</label> 
                                </p>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Keep Logs for</th>
                        <td>
                            <?php 
                            $wpla = WPLA_Logger::get_instance();
                            $keep = wpla_option( 'keep', MONTH_IN_SECONDS * 12 );
                            ?>
                            <select name="<?php echo WPLA_OPTIONS_NAME;?>[keep]">
                                <option value="0" <?php selected( 0, $keep ); ?>>Forever</option>
                                <option value="<?php echo MONTH_IN_SECONDS*1; ?>" <?php selected( MONTH_IN_SECONDS*1, $keep ); ?>>1 month</option>
                                <option value="<?php echo MONTH_IN_SECONDS*3; ?>" <?php selected( MONTH_IN_SECONDS*3, $keep ); ?>>3 months</option>
                                <option value="<?php echo MONTH_IN_SECONDS*6; ?>" <?php selected( MONTH_IN_SECONDS*6, $keep ); ?>>6 months</option>
                                <option value="<?php echo MONTH_IN_SECONDS*12; ?>" <?php selected( MONTH_IN_SECONDS*12, $keep ); ?>>12 months</option>
                                <option value="<?php echo MONTH_IN_SECONDS*24; ?>" <?php selected( MONTH_IN_SECONDS*24, $keep ); ?>>24 months</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save All') ?>" />
                </p>
        </form>
    </div>
    <?php 
}

/**
 * Sanitize and validate input. Accepts an array, return a sanitized array.
 */
function wpla_options_validate($input) {
    global $wp_settings_errors;
    $setting_names = array( 
        'log_doing_it_wrong', 
        'log_deprecated'
    );
    foreach( $setting_names as $name ) {
        if ( !isset( $input[$name] ) ) {
            $input[$name] = 0;
        }   
    }
    
    wpla_schedule_events();

    return $input;
}

/**
 * Enqueue Scripts
 */
function wpla_admin_scripts() {
    do_action ('wpla_admin_scripts');
}

/**
 * Enqueue scripts for the admin side.
 */
function wpla_enqueue_scripts($hook) {
    if( 'settings_page_' . WPLA_OPTIONS_PAGE_ID != $hook )
        return;

    wp_enqueue_style( 'wpla-options',
        plugins_url( '/css/options.css', WPLA_PLUGIN ),
        array( ),
        WPLA_VERSION );
}
add_action( 'admin_enqueue_scripts', 'wpla_enqueue_scripts' );

