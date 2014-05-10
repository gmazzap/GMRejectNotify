<?php
namespace GM\RejectNotify;

class Form {

    use PluginSettable;

    /**
     * Print the html markup for form
     *
     * @return void
     * @uses GM\RejectNotify\Form::template()
     */
    function show() {
        if ( ! $this->plugin->should() ) return 'Error';
        $pid = filter_input( INPUT_GET, 'postid', FILTER_SANITIZE_NUMBER_INT );
        $post = get_post( $pid );
        $author = new \WP_User( $post->post_author );
        if (
            ! $post instanceof \WP_Post
            || ! $post->ID > 0
            || ! $author->exists()
            || ! filter_var( $author->user_email, FILTER_VALIDATE_EMAIL )
        ) {
            die( 'Error' );
        }
        $this->template( $post, $author );
    }

    /**
     * Send email to post author using form informations
     *
     * @return void
     * @uses GM\RejectNotify\Form::beforeSend()
     * @uses GM\RejectNotify\Form::afterSend()
     */
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
        $postid = $data['postid'];
        add_filter( 'wp_mail_content_type', function() {
            return 'text/html';
        }, PHP_INT_MAX );
        $vars = compact( 'sender_mail', 'reason', 'recipient', 'subject', 'postid' );
        $success = wp_mail( $data['recipient'], esc_html( $subject ), $reason, $headers );
        $this->afterSend( $success, $vars );
    }

    private function template( \WP_Post $post, \WP_User $author ) {
        $recipient = $author->user_email;
        $title = esc_html( $post->post_title );
        $send_format = __( 'Send reject mail to %s', 'gmrejectnotify' );
        $sorry_format = __( 'Sorry %s, your post%s was rejected.', 'gmrejectnotify' );
        ?>
        <div class="send_rejected_form" id = "send_rejected_form_wrap" style = "padding:20px;">
            <h2><?php printf( $send_format, $author->display_name ); ?></h2>
            <form id="send_rejected_form_form" method="post">
                <?php wp_nonce_field( Plugin::NONCE . get_current_blog_id(), Plugin::NONCE ); ?>
                <input type="hidden" name="action" value="<?php echo Plugin::SLUG . '_send_mail' ?>" />
                <input type="hidden" name="postid" value="<?php echo $post->ID; ?>" />
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
        $args = [
            'post_title'  => FILTER_SANITIZE_STRING,
            'reason'      => FILTER_SANITIZE_STRING,
            'postid'      => FILTER_SANITIZE_NUMBER_INT,
            'recipient'   => FILTER_VALIDATE_EMAIL,
            Plugin::NONCE => FILTER_SANITIZE_STRING
        ];
        $data = filter_input_array( INPUT_POST, $args, TRUE );
        $error = FALSE;
        if (
            empty( $data[Plugin::NONCE] )
            || ! check_admin_referer( Plugin::NONCE . get_current_blog_id(), Plugin::NONCE )
        ) {
            $error = __( 'Error on validating nonce.', 'gmrejectnotify' );
        } elseif ( empty( $data['recipient'] ) ) {
            $error = __( 'Invalid recipent mail', 'gmrejectnotify' );
        } elseif ( ! $this->plugin->should() ) {
            $error = __( 'Error on validating authorization.', 'gmrejectnotify' );
        }
        if ( $error !== FALSE ) {
            $json = array_merge( $data, [ 'message' => $error, 'class' => 'error' ] );
            wp_send_json( $json );
        }
        return $data;
    }

    private function afterSend( $success = FALSE, $data = [ ] ) {
        $message_format = $success ?
            __( 'Email sended to %s!', 'gmrejectnotify' ) :
            __( 'Error on sending email to %s', 'gmrejectnotify' );
        $message = sprintf( $message_format, $data['sender_mail'] );
        $class = $success ? 'updated' : 'error';
        $json = array_merge( $data, ['message' => $message, 'class' => $class ] );
        do_action( Plugin::SLUG . '_mail_sended_' . $class, $json );
        wp_send_json( $json );
    }

}