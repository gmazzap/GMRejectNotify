<?php
namespace GM\RejectNotify;

class Form {

    use PluginSettable;

    function show() {
        if ( ! $this->plugin->should() ) return 'Error';
        $pid = filter_input( INPUT_POST, 'postid', FILTER_SANITIZE_NUMBER_INT );
        $post = get_post( $pid );
        $author = new \WP_User( $post->post_author );
        if (
            ! $post instanceof \WP_Post
            || ! $post->ID > 0
            || ! $author->exists
            || ! filter_var( $author->user_email, FILTER_VALIDATE_EMAIL )
        ) {
            die( 'Error' );
        }
        $this->template( $post, $author );
    }

    function send() {
        $data = $this->beforeSend();
        $title = ! empty( $data['post_title'] ) ? stripslashes( $data['post_title'] ) : '';
        $reason_format = __( 'Sorry your post %s was rejected.', 'gmrejectnotify' );
        $reason_default = sprintf( $reason_format, esc_html( stripslashes( $title ) ) );
        $reason = ! empty( $data['reason'] ) ? $data['reason'] : $reason_default;
        $sender = wp_get_current_user();
        $sender_mail = $sender->user_email;
        $headers = 'From: ' . esc_attr( $sender->display_name ) . '<' . $sender_mail . '>' . "\r\n";
        $subject_format = __( 'Your post on %s was rejected.', 'gmrejectnotify' );
        $subject = sprintf( $subject_format, esc_html( stripslashes( get_bloginfo( 'name' ) ) ) );
        add_filter( 'wp_mail_content_type', function() {
            return 'text/html';
        }, PHP_INT_MAX );
        $success = wp_mail( $data['recipient'], esc_html( $subject ), $reason, $headers );
        $message_format = $success ?
            __( 'Email sended to %s!', 'gmrejectnotify' ) :
            __( 'Error on sending email to %s', 'gmrejectnotify' );
        $message = sprintf( $message_format, $data['recipient'] );
        $class = $success ? 'updated' : 'error';
        $json = compact( 'message', 'class', 'sender_mail', 'reason', 'recipient', 'subject' );
        wp_send_json( $json );
    }

    private function template( \WP_Post $post, \WP_User $author ) {
        $recipient = $author->user_email;
        $title = esc_html( $post->post_title );
        $send_format = __( 'Send reject mail to %s', 'gmrejectnotify' );
        $sorry_format = __( 'Sorry %s your post%s was rejected.', 'gmrejectnotify' );
        ?>
        <div class="send_rejected_form" id = "send_rejected_form_wrap" style = "padding:20px;">
            <h2><?php printf( $send_format, $author->display_name ); ?></h2>
            <form id="send_rejected_form_form" method="post">
                <?php wp_nonce_field( 'send_rejected_action', 'send_rejected_nonce' ); ?>
                <input type="hidden" name="action" value="<?php echo Plugin::SLUG . '_send_mail' ?>" />
                <input type="hidden" name="post_title" value="<?php echo $title; ?>" />
                <input type="hidden" name="recipient" value="<?php echo $recipient; ?>" />
                <textarea name="reason" style="width:100%" rows="5"><?php
                    printf( $sorry_format, $author->display_name, ' &quot;' . $title . '&quot;' );
                    ?></textarea>
                <?php submit_button( __( 'Send', 'gmrejectnotify', 'send_rejected_submit' ) ); ?>
            </form>
        </div>
        <div id="GMRejectNotifyMessage" style="margin-top:10px;padding:15px;display:none"></div>
        <?php
        die();
    }

    private function beforeSend() {
        error_reporting( 0 );
        $args = [ ];
        $data = filter_input_array( INPUT_POST, $args, TRUE );
        if (
            empty( $data[Plugin::NONCE] )
            || ! check_admin_referer( Plugin::NONCE, Plugin::NONCE )
        ) {
            _e( 'Error on sending email', 'gmrejectnotify' );
            die();
        } elseif (
            empty( $data['recipient'] )
            || ! filter_var( $data['recipient'], FILTER_VALIDATE_EMAIL )
        ) {
            _e( 'Error on sending email', 'gmrejectnotify' );
            die();
        } elseif ( ! $this->plugin->should() ) {
            _e( 'Error on sending email', 'gmrejectnotify' );
        }
        return $data;
    }

}