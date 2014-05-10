<?php namespace GM\RejectNotify;

class PostEdit {

    use PluginSettable;

    function enable() {
        if ( ! $this->plugin->should() ) return;
        add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        add_action( 'post_submitbox_misc_actions', [ $this, 'button' ] );
    }

    function disable() {
        remove_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        remove_action( 'post_submitbox_misc_actions', [ $this, 'button' ] );
    }

    function assets() {
        if ( ! $this->plugin->should() ) return;
        wp_enqueue_style( 'thickbox' );
        $data = [
            'action'          => Plugin::SLUG . '_send_mail',
            'please_wait'     => __( 'Please wait...', 'gmrejectnotify' ),
            'def_mail_error'  => __( 'Error on sending email.', 'gmrejectnotify' ),
            'debug'           => defined( 'WP_DEBUG' ) && WP_DEBUG ? '1' : '',
            'debug_info'      => __( 'Debug info', 'gmrejectnotify' ),
            'sender'          => __( 'Sender', 'gmrejectnotify' ),
            'recipient'       => __( 'Recipient', 'gmrejectnotify' ),
            'email_content'   => __( 'Email Content', 'gmrejectnotify' ),
            'email_subject'   => __( 'Email Subject', 'gmrejectnotify' ),
            'ajax_wrong_data' => __( 'Ajax callback return nothing or wrong data', 'gmrejectnotify' ),
            'ajax_fails'      => __( 'Ajax call fails', 'gmrejectnotify' )
        ];
        $rel = 'js/gm-reject-notify.js';
        $url = plugins_url( $rel, Plugin::path() );
        $path = str_replace( 'plugin.php', $rel, Plugin::path() );
        $ver = @filemtime( $path ) ? : NULL;
        wp_enqueue_script( Plugin::SLUG, $url, [ 'jquery', 'thickbox' ], $ver );
        wp_localize_script( Plugin::SLUG, 'gm_reject_notify_data', $data );
    }

    function button() {
        if ( ! $this->plugin->should() ) return;
        $label = __( 'Reject and Notify', 'gmrejectnotify' );
        $format = '<p style="padding-right:10px;" align="right">'
            . '<input name="send_reject_mail_box" data-post="'
            . '%s'
            . '" class="button button-primary button-large" id="send_reject_mail_box" value="'
            . '%s'
            . '" type="button"><p>';
        printf( $format, $GLOBALS['post']->ID, esc_html( $label ) );
    }

}