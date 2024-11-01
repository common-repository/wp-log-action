<?php

/**
 * Log when a plugin is activated.
 */
function wpla_log_plugin_activated( $plugin, $network_deactivating ) {
    $plugin_data = wpla_get_plugin_data( $plugin );
    do_action( 'wp_log_info', 'Plugin Activated', $plugin_data['Name'] );
}

/**
 * Log when a plugin is deactivated.
 */
function wpla_log_plugin_deactivated( $plugin, $network_deactivating ) {
    $plugin_data = wpla_get_plugin_data( $plugin, false );
    do_action( 'wp_log_info', 'Plugin Deactivated', $plugin_data['Name'] );
}

/**
 * Log when a plugin is deleted.
 */
function wpla_log_plugin_delete( $plugin ) {
    global $wpla_plugins_deleting;
    if ( empty( $wpla_plugins_deleting ) ) {
        $wpla_plugins_deleting = array();
    }
    $plugin_data = wpla_get_plugin_data( $plugin );
    $wpla_plugins_deleting[$plugin] = $plugin_data['Name'];
}
function wpla_log_plugin_deleted( $plugin, $deleted ) {
    global $wpla_plugins_deleting;

    $plugin_name = !empty( $wpla_plugins_deleting[$plugin] ) ? $wpla_plugins_deleting[$plugin] : '';
    do_action( 'wp_log_info', 'Plugin Deleted', $plugin_name );
}

/**
 * Log when a plugin is installed or updated.
 */
function wpla_log_installed_or_updated( $upgrader, $extra ) {
    if ( ! isset( $extra['type'] ) || 'plugin' !== $extra['type'] ) {
        return;
    }

    if ( 'install' === $extra['action'] ) {
        $path = $upgrader->plugin_info();
        if ( ! $path ) {
            return;
        }
        
        $plugin_data = get_plugin_data( trailingslashit( $upgrader->skin->result['local_destination'] ) . $path, false );

        do_action( 'wp_log_info', 'Plugin Installed', $plugin_data['Name'] . ' (' . $plugin_data['Version'] . ')');
    }

    elseif ( 'update' === $extra['action'] ) {
        $slugs = [];
        if ( isset( $extra['bulk'] ) && true == $extra['bulk'] ) {
            $slugs = $extra['plugins'] ?? [];
        }
        else {
            if ( ! isset( $upgrader->skin->plugin ) ) {
                return;
            }
            
            $slugs = array( $upgrader->skin->plugin );
        }
        
        $plugins_dir = trailingslashit( dirname( plugin_dir_path( __FILE__ ) ) );

        foreach ( $slugs as $slug ) {
            $plugin_data = get_plugin_data( $plugins_dir . $slug, true, false );

            do_action( 'wp_log_info', 'Plugin Updated', $plugin_data['Name'] . ' (' . $plugin_data['Version'] . ')');
        }
    }
}

/**
 * Log when core updated successfully
 */
function wpla_log_core_updated( $wp_version ) {
    global $pagenow;

    // Auto updated
    if ( 'update-core.php' !== $pagenow ) {
        $activity = 'WordPress Auto Updated';
    }
    else {
        $activity = 'WordPress Updated';
    }

    do_action( 'wp_log_info', $activity, $wp_version );
}

/**
 * Log doing it wrong runs.
 */
function wpla_doing_it_wrong_run( $function, $message, $version ) {
    $version = is_null( $version ) ? '' : sprintf( __( '(This message was added in version %s.)' ), $version );
    /* translators: %s: Codex URL */
    $message .= ' ' . sprintf( __( 'Please see <a href="%s">Debugging in WordPress</a> for more information.' ),
        __( 'https://codex.wordpress.org/Debugging_in_WordPress' )
    );
    $log_message = sprintf( __( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s' ), $function, $message, $version );

    do_action( 'wp_log_error', __( sprintf( 'Doing it wrong: %s ', $function ) ), $log_message );
}

/**
 * Log deprecated function runs.
 */
function wpla_deprecated_function_run( $function, $replacement, $version ) {
    $log_message = '';
    if ( ! is_null( $replacement ) )
        $log_message = sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.'), $function, $version, $replacement );
    else
        $log_message = sprintf( __('%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.'), $function, $version );

    do_action( 'wp_log_warning', __( sprintf( 'Deprectated: %s ', $function ) ), $log_message );
}

/**
 * Get the plugin name from any file.
 */
function wpla_get_plugin_data( $plugin ) {
    // Get plugin name if it's a path
    if ( false !== strpos( $plugin, '/' ) ) {
        $plugin_dir  = explode( '/', $plugin );
        $plugin_data = array_values( get_plugins( '/' . $plugin_dir[0] ) );
        $plugin_data = array_shift( $plugin_data );
        return $plugin_data;
    }
    return false;
}