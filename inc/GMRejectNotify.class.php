<?php

class GMRejectNotify {
	
	/**
	* Class version
	*
	* @since	0.1.0
	*
	* @access	protected
	*
	* @var	string
	*/
	protected static $version = '0.1.0';
	
	
	
	
	/**
	* A switch variable. It false the plugin does nothing. It is used to allow plugin workflow only on the right admin page and only for allowed users.
	*
	* @since	0.1.0
	*
	* @access	protected
	*
	* @var	bool
	*/
	protected static $initialize = false;
	
	
	
	
	
	/**
	* A switch variable. It false the plugin does nothing on ajax request. It is used to allow plugin workflow only for allowed users.
	*
	* @since	0.1.0
	*
	* @access	protected
	*
	* @var	bool
	*/
	protected static $ajax_initialize = false;
	
	
	
	
	/**
	 * Inizialize for the main plugin class. Run on 'admin_init' hook
	 *
	 * @since	0.1.0
	 *
	 * @access	public
	 * @return	null
	 *
	 */
	static function init() {
		if ( ! current_user_can('edit_others_posts') ) return;
		if ( defined('DOING_AJAX') ) return self::ajax_init();
		global $pagenow;
		if ( ! current_user_can('edit_others_posts') || $pagenow != 'post.php' || ! isset($_GET['post']) ) {
			self::$initialize = false;
			return;
		}
		if ( get_post_field('post_status', $_GET['post']) != 'pending' ) {
			self::$initialize = false;
			return;
		}
		self::$initialize = true;
		add_action('admin_enqueue_scripts', array( __CLASS__, 'assets') );
		add_action('post_submitbox_misc_actions', array( __CLASS__, 'the_button') );
	}
	
	
	
	
	/**
	 * Inizialize the ajax workflow
	 *
	 * @since	0.1.0
	 *
	 * @access	public
	 * @return	null
	 *
	 */
	static function ajax_init() {
		if ( ! defined('DOING_AJAX') || ! current_user_can('edit_others_posts') ) {
			self::$ajax_initialize = false;
			return;
		}
		self::$ajax_initialize = true;
		add_action('wp_ajax_send_rejected_form', array( __CLASS__, 'the_form') );
		add_action('wp_ajax_send_rejected_email', array( __CLASS__, 'send_mail') );
	}
	
	
	
	
	/**
	 * Add the scripts and the style to the admin page
	 *
	 * @since	0.1.0
	 *
	 * @access	public
	 * @return	null
	 *
	 */
	static function assets( $page ) {
		if ( ! self::$initialize ) return;
		wp_enqueue_style('thickbox');
		$data = array(
			'please_wait' => __('Please wait...', 'gmrejectnotify'),
			'def_mail_error' => __('Error on sending email.', 'gmrejectnotify'),
			'debug' => ( defined('WP_DEBUG') && WP_DEBUG ) ? '1' : '',
			'debug_info' => __('Debug info', 'gmrejectnotify'),
			'sender' => __('Sender', 'gmrejectnotify'),
			'recipient' => __('Recipient', 'gmrejectnotify'),
			'email_content' => __('Email Content', 'gmrejectnotify'),
			'email_subject' => __('Email Subject', 'gmrejectnotify'),
			'ajax_wrong_data' => __('Ajax callback return nothing or wrong data', 'gmrejectnotify'),
			'ajax_fails' => __('Ajax call fails', 'gmrejectnotify')
		);
		wp_enqueue_script('send-rejected', GMREJECTNOTIFYURL . '/inc/gm-reject-notify.js', array('jquery', 'thickbox'), null );
		wp_localize_script('send-rejected', 'GMRejectNotifyData', $data);
	}
	
	
	
	
	/**
	 * Print the UI button
	 *
	 * @since	0.1.0
	 *
	 * @access	public
	 * @return	null
	 *
	 */
	static function the_button() {
	  if ( ! self::$initialize ) return;
	  echo '<p style="padding-right:10px;" align="right"><input name="send_reject_mail_box" data-post="' . $_GET['post'];
	  echo '" class="button button-primary button-large" id="send_reject_mail_box" value="';
	  echo __('Reject and Notify', 'gmrejectnotify') . '" type="button"><p>';
	}
	
	
	
	
	/**
	 * Print the form in the modal box
	 *
	 * @since	0.1.0
	 *
	 * @access	public
	 * @return	null
	 *
	 */
	static function the_form() {
		if ( ! self::$ajax_initialize || ! isset($_GET['postid']) ) return 'Error';
	  	$post = get_post($_GET['postid']);
	  	$author = new WP_User($post->post_author);
		if ( ! isset($author->user_email) || ! filter_var($author->user_email, FILTER_VALIDATE_EMAIL) ) return 'Error';
	  	$recipient = $author->user_email;
	  	$recipient_name = ucwords($author->display_name);
	  	?>
	  	<div class="send_rejected_form" id="send_rejected_form_wrap" style="padding:20pz;">
	  	<h2><?php printf( __('Send reject mail to %s', 'gmrejectnotify'), $recipient_name ); ?></h2>
	  	<form id="send_rejected_form_form" method="post">
	  	<?php wp_nonce_field( 'send_rejected_action', 'send_rejected_nonce' ); ?>
	  	<input type="hidden" name="action" value="send_rejected_email" />
	  	<input type="hidden" name="post_title" value="<?php echo esc_html($post->post_title); ?>" />
	  	<input type="hidden" name="recipient" value="<?php echo $recipient; ?>" />
	  	<textarea name="reason" style="width:100%" rows="5"><?php printf(__('Sorry %s your post%s was rejected.', 'gmrejectnotify'), $recipient_name, ' &quot;' . esc_html($post->post_title). '&quot;' ); ?></textarea>
	  	<?php submit_button( __('Send', 'gmrejectnotify', 'send_rejected_submit')); ?>
	  	</form>
	  	</div>
        <div id="GMRejectNotifyMessage" style="margin-top:10px;padding:15px;display:none"></div>
	  	<?php
	  	die();
	}
	
	
	
	
	/**
	 * Send the email via ajax
	 *
	 * @since	0.1.0
	 *
	 * @access	public
	 * @return	null
	 *
	 */
	static function send_mail() {
		// disallow error reporting for unspected ajax outout 
		error_reporting(0);
		$error = true;
		if ( self::$ajax_initialize ) {
			if (
				! isset($_POST['send_rejected_nonce']) ||
				! check_admin_referer( 'send_rejected_action', 'send_rejected_nonce' ) ||
				! isset($_POST['recipient'])
			) {
				$error = true;
			} elseif( ! filter_var($_POST['recipient'], FILTER_VALIDATE_EMAIL) ) {
				$error = true;
			} else {
				$error = false;
			}
		}
		if ( $error ) die (__('Error on sending email', 'gmrejectnotify') );
		$recipient = $_POST['recipient'];
		$post_title = isset($_POST['post_title']) ? ' &quot;' . stripslashes($post_title) . '&quot;' : '';
		$reason = isset($_POST['reason']) ?
			stripslashes($_POST['reason']) :
			sprintf(__('Sorry your post%s was rejected.', 'gmrejectnotify'), '&quot;' . esc_html( stripslashes($post_title) ) . '&quot;' );
		$sender = wp_get_current_user();
		$sender_mail = $sender->user_email;
		$headers = 'From: ' . esc_attr($sender->display_name) . '<' . $sender_mail . '>' . "\r\n";
		$subject = sprintf( __('Your post on %s was rejected', 'gmrejectnotify'), esc_html( stripslashes( get_bloginfo('name') ) ) );
		add_filter( 'wp_mail_content_type', 'gmrejectnotify_html_mail' );
		$sended = wp_mail($recipient, esc_html($subject), $reason, $headers);
		remove_filter( 'wp_mail_content_type', 'gmrejectnotify_html_mail' );
		$message = $sended ?
			__('Email sended to %s!', 'gmrejectnotify') :
			__('Error on sending email to %s', 'gmrejectnotify');
		$message = sprintf($message, $recipient);
		$class = $sended ? 'updated' : 'error';
		$json = compact('message', 'class', 'sender_mail', 'reason', 'recipient', 'subject');
		wp_send_json($json);
	}
	
}