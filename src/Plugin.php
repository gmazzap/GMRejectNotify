<?php namespace GM\RejectNotify;

/*
 * Main Plugin class
 */
class Plugin {

    const SLUG = 'gmrejectnotify';

    const NONCE = 'gmrejectnotify_nonce';

    private $post_edit;

    private $post_list;

    private $form;

    private $screen;

    private $action;

    static function path() {
        return dirname( dirname( __FILE__ ) ) . '/plugin.php';
    }

    function __construct( PostEdit $post_edit, PostList $post_list, Form $form ) {
        $this->post_edit = $post_edit->setPlugin( $this );
        $this->post_list = $post_list->setPlugin( $this );
        $this->form->setPlugin( $this ) = $form;
    }

    function isAjax() {
        return defined( 'DOING_AJAX' ) && DOING_AJAX;
    }

    function loadTextDomain() {
        $file = plugin_basename( self::path() );
        load_plugin_textdomain( self::SLUG, FALSE, dirname( $file ) . '/lang/' );
    }

    function should() {
        $should = FALSE;
        if ( is_admin() && current_user_can( 'edit_others_posts' ) ) {
            $should = $this->isAjax() ? $this->ajaxShould() : $this->regularShould();
        }
        return $should;
    }

    function init() {
        if ( ! $this->should() ) return;
        $this->loadTextDomain();
        if ( $this->isAjax() ) {
            $this->ajaxInit();
        } else {
            if ( $this->screen === 'edit-post' ) {
                $this->post_list->enable();
            } elseif ( $this->screen === 'post' ) {
                $this->post_edit->enable();
            }
        }
    }

    function disable() {
        $this->post_list->disable();
        $this->post_edit->disable();
    }

    private function ajaxInit() {
        if ( $this->action === 'send_mail' ) {
            $this->form->send();
        } elseif ( $this->action === 'show_form' ) {
            $this->form->show();
        }
    }

    private function regularShould() {
        $screen = get_current_screen();
        $this->screen = $screen->id;
        if ( $this->screen === 'post' ) {
            global $post;
            return $post instanceof \WP_Post && get_post_status( $post->ID ) === 'pending';
        } elseif ( $this->screen === 'edit-post' ) {
            return TRUE;
        } else {
            $this->screen = NULL;
            return FALSE;
        }
    }

    private function ajaxShould() {
        $method = filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING );
        if ( strtoupper( $method ) === 'GET' ) {
            $this->action = filter_input( INPUT_GET, 'action', FILTER_SANITIZE_STRING );
            return $this->action === self::SLUG . '_show_form';
        } elseif ( strtoupper( $method ) === 'POST' ) {
            $this->action = filter_input( INPUT_POST, 'action', FILTER_SANITIZE_STRING );
            $pid = filter_input( INPUT_POST, 'postid', FILTER_SANITIZE_NUMBER_INT );
            return $this->action === self::SLUG . '_send_mail' && (int) $pid > 0;
        }
    }

}