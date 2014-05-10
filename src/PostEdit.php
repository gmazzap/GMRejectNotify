<?php namespace GM\RejectNotify;

class PostEdit {

    use PluginSettable;

    /**
     * Add hooks
     */
    function enable() {
        if ( ! $this->plugin->should() ) return;
        add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        add_action( 'post_submitbox_misc_actions', [ $this, 'button' ] );
    }

    /**
     * Remove hooks
     */
    function disable() {
        remove_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );
        remove_action( 'post_submitbox_misc_actions', [ $this, 'button' ] );
    }

    /**
     * Enqueue and localize the scripts
     */
    function assets() {
        global $post;
        if ( ! $this->plugin->should() || (int) $this->plugin->meta()->getMeta( $post->ID ) === 1 ) {
            return;
        }
        wp_enqueue_style( 'thickbox' );
        $data = [
            'action'           => Plugin::SLUG . '_show_form',
            'please_wait'      => esc_html__( 'Please wait...', 'gmrejectnotify' ),
            'def_mail_error'   => esc_html__( 'Error on sending email.', 'gmrejectnotify' ),
            'debug'            => defined( 'WP_DEBUG' ) && WP_DEBUG ? '1' : '',
            'debug_info'       => esc_html__( 'Debug info', 'gmrejectnotify' ),
            'sender'           => esc_html__( 'Sender', 'gmrejectnotify' ),
            'recipient'        => esc_html__( 'Recipient', 'gmrejectnotify' ),
            'email_content'    => esc_html__( 'Email Content', 'gmrejectnotify' ),
            'email_subject'    => esc_html__( 'Email Subject', 'gmrejectnotify' ),
            'already_rejected' => esc_html__( 'Already rejected and notified.', 'gmrejectnotify' ),
            'ajax_wrong_data'  => esc_html__( 'Ajax callback return nothing or wrong data', 'gmrejectnotify' ),
            'ajax_fails'       => esc_html__( 'Ajax call fails', 'gmrejectnotify' )
        ];
        $rel = 'js/gm-reject-notify.js';
        $url = plugins_url( $rel, Plugin::path() );
        $path = str_replace( 'plugin.php', $rel, Plugin::path() );
        $ver = @filemtime( $path ) ? : NULL;
        wp_enqueue_script( Plugin::SLUG, $url, [ 'jquery', 'thickbox' ], $ver );
        wp_localize_script( Plugin::SLUG, 'gm_reject_notify_data', $data );
    }

    /**
     * Print the Reject button or the 'already notified' notice.
     */
    function button() {
        if ( ! $this->plugin->should() ) return;
        global $post;
        $html = '<div class="misc-pub-section" style="text-align:right;">';
        if ( (int) $this->plugin->meta()->getMeta( $post->ID ) === 1 ) {
            $already = esc_html__( 'Already rejected and notified.', 'gmrejectnotify' );
            return printf( $html . '<strong>%s</strong></div>', $already );
        }
        $label = esc_html__( 'Reject and Notify', 'gmrejectnotify' );
        $html .= '<input name="send_reject_mail_box" data-post="'
            . '%s'
            . '" class="button button-primary button-large" id="send_reject_mail_box" value="'
            . '%s'
            . '" type="button"></div>';
        printf( $html, $GLOBALS['post']->ID, $label );
    }

}