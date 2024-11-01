=== WP Log Action ===
Contributors: webheadllc
Donate link: http://webheadcoder.com/donate-wp-log-action
Tags: debug, log, developer tool, activity, warning, WP_DEBUG
Requires at least: 5.3
Tested up to: 6.4
Stable tag: 0.51
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add error or debug logging in your code and leave it there.  Logs will only be recorded with this plugin, otherwise will be ignored.

== Description ==

This plugin uses hooks in the opposite way most plugins do.  You add `do_action` where you want to do some logging and this plugin will save it to the database only when active.

= Log Activity =
This plugin now logs plugin activity (when activated, deactivated, deleted, updated, installed), when wordpress is updated, and when functions are used wrong or deprecated.


Example:  
`
do_action( 'wp_log_info', 'So far ok', 'Details of what is ok.' );
if ( $something_bad_happened ) {
    do_action( 'wp_log_error', 'This Happened!', 'Details of what happened.' );
    ...
}
`

See Tools->Logs to view, delete, and export the logs on the admin side.  Only users with the manage_options capability will have access.  

This plugin automatically logs deprecated and doing_it_wrong errors.  The rest is what you add to your code.  

You can log what functions will be run for a specific action or filter.  For example if you want to see what runs in the 'init' hook:

`function check_init_hook() {
    do_action( 'wp_log_debug_hook', 'init' );
}
add_filter( 'init', 'check_init_hook', 0 );`



The following are the different levels of logging to add to your code.  You can use any level how you see fit, the descriptions of each level are just guidelines.

= Emergency =
System is unusable
`do_action( 'wp_log_emergency', $label, $message );`

= Alert =
Action must be taken immediately.
`do_action( 'wp_log_alert', $label, $message );`

= Critical =
Critical conditions.
`do_action( 'wp_log_critical', $label, $message );`

= Error =
Runtime errors that do not require immediate action but should typically be logged and monitored.
`do_action( 'wp_log_error', $label, $message );`

= Warning =
Exceptional occurrences that are not errors.
`do_action( 'wp_log_warning', $label, $message );`

= Notice =
Normal but significant events.
`do_action( 'wp_log_notice', $label, $message );`

= Info =
Interesting events.
`do_action( 'wp_log_info', $label, $message );`

= Debug =
Detailed debug information.
`do_action( 'wp_log_debug', $label, $message );`




== Changelog ==

= 0.51 =
Fixed timezone error.  
Fixed PHP warnings.  

= 0.50 =
Added automatic deleting of logs based on options.  
By default, logs will be deleted 12 months after its created.  Existing users need to re-save their settings or reactivate the plugin.  

= 0.40 =
Added logging when plugin and core are updated.  

= 0.34 =
Fixed silly microsoft bug by making the ID column name lower case in export.  Thanks to @clratliff for letting me know!  

= 0.33 =
Fixed exporting of logs.  

= 0.32 =
Updated delete to use POST instead of GET.  
Fixed pages displaying all rows instead of just 25 rows.  

= 0.31 =
Fixed search losing parameters when going to the next page on view log screen.  

= 0.3 =
Added Export Results option in Bulk Actions.  

= 0.22 =
Fix option name not defined.  

= 0.21 =
Fix css and js not loading.  
Add options page.  

= 0.2 =
Change default order of table to descending.  
Print arrays so it's readable even though it's ugly.  
Update readme.

= 0.1 =
Initial release.

