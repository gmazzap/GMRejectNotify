<?php namespace GM\RejectNotify;

/**
 * Plugin Name: GM Reject and Notify
 * Plugin URI: https://github.com/Giuseppe-Mazzapica/GMRejectNotify
 * Description: Add a button to post edit page that allow sending a notify to a contributor whose a post was not approved. It's possible customize the message including the reason for rejected approval.
 * Version: 1.0
 * Author: Giuseppe Mazzapica
 * Requires at least: 3.9
 * Tested up to: 3.9
 *
 * Text Domain: gmrejectnotify
 * Domain Path: /lang/
 *
 * @author Giuseppe Mazzapica
 *
 */
if ( ! defined( 'ABSPATH' ) ) die();


add_action( 'admin_init', function () {
    // Filter: 'gm_reject_notify_enable' Allow to completely disable plugin
    if ( ! apply_filters( 'gm_reject_notify_enable', TRUE ) ) return;
    $autoload = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
    if ( ! class_exists( 'GM\RejectNotify\Plugin' ) && is_file( $autoload ) ) {
        require_once $autoload;
    }
    $plugin = new Plugin( new PostEdit, new PostList, new Form, new Meta );
    // Filter: 'gm_reject_notify_instance' Allow to get plugin instance
    add_filter( 'gm_reject_notify_instance', function() use( $plugin ) {
        return $plugin;
    } );
    // Filter: 'gm_reject_notify_admin_cap' Allow to minumum capability required to reject posts
    $capability = apply_filters( 'gm_reject_notify_admin_cap', 'edit_others_posts' );
    $plugin->init( $capability );
} );
