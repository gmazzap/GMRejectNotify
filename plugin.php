<?php namespace GM\RejectNotify;

/**
 * Plugin Name: GM Reject and Notify
 *
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

add_action( 'admin_init', function() {
    $plugin = new Plugin( new PostEdit, new PostList, new Form );
    add_filter( 'GM_RejectNotify', function() use( $plugin ) {
        return $plugin;
    } );
    $plugin->init();
}, 30 );
