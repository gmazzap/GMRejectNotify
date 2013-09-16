<?php 
/**
 * Plugin Name: GM Reject and Notify
 *
 * Description: Add a button to post edit page that allow sending a notify to a contributor whose a post was not approved. It's possible customize the message including the reason for rejected approval.
 * Version: 0.1.0
 * Author: Giuseppe Mazzapica
 * Requires at least: 3.5
 * Tested up to: 3.6
 *
 * Text Domain: gmrejectnotify
 * Domain Path: /lang/
 *
 * @package GMRejectNotify
 * @author Giuseppe Mazzapica
 *
 */
 

add_action('admin_init', 'init_GMRejectNotify', 30);


/**
 * Inizialize plugin
 *
 * @since	0.1.0
 *
 * @access	public
 * @return	null
 *
 */
function init_GMRejectNotify() {
	
	if ( ! defined('ABSPATH') ) die();
	
	define('GMREJECTNOTIFYPATH', plugin_dir_path( __FILE__ ) );
	define('GMREJECTNOTIFYURL', plugins_url( '/' , __FILE__ ) );
	
	require_once( GMREJECTNOTIFYPATH . 'inc/GMRejectNotify.class.php');
	
	if ( apply_filters('gm_reject_notify_enable', true) ) { // allow disabling plugin from another plugin or theme via filter
		load_plugin_textdomain('gmrejectnotify', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
		// init main plugin class
		GMRejectNotify::init();
	}
	
}




/**
 * Add a filter to wp_mail to allow html emails. global namesapece funtion for easily filter removal
 *
 * @since	0.1.0
 *
 * @access	public
 * @return	string
 *
 */
function gmrejectnotify_html_mail() { return 'text/html'; }