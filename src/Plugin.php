<?php namespace GM\RejectNotify;

/*
 * Main Plugin class, manage all the other classes.
 */
class Plugin {

    const SLUG = 'gmrejectnotify';

    const META = '_gmrejectnotify';

    const NONCE = 'gmrejectnotify_nonce';

    private $post_edit;

    private $post_list;

    private $form;

    private $screen;

    private $action;

    private $capability;

    /**
     * Return plugin main path
     *
     * @return string
     */
    static function path() {
        return dirname( dirname( __FILE__ ) ) . '/plugin.php';
    }

    /**
     * Constructor
     *
     * @param \GM\RejectNotify\PostEdit $post_edit
     * @param \GM\RejectNotify\PostList $post_list
     * @param \GM\RejectNotify\Form $form
     * @param \GM\RejectNotify\Meta $meta
     */
    function __construct( PostEdit $post_edit, PostList $post_list, Form $form, Meta $meta ) {
        $this->post_edit = $post_edit->setPlugin( $this );
        $this->post_list = $post_list->setPlugin( $this );
        $this->form = $form->setPlugin( $this );
        $this->meta = $meta;
    }

    /**
     * Return Meta class instance
     */
    function meta() {
        return $this->meta;
    }

    /**
     * Check if the current is an ajax request
     *
     * @return boolean
     */
    function isAjax() {
        return defined( 'DOING_AJAX' ) && DOING_AJAX;
    }

    /**
     * Load plugin text domain
     */
    function loadTextDomain() {
        $file = plugin_basename( self::path() );
        load_plugin_textdomain( self::SLUG, FALSE, dirname( $file ) . '/lang/' );
    }

    /**
     * Check if plugin should continue to initialize
     *
     * @return boolean
     */
    function should() {
        $should = FALSE;
        if ( is_admin() && current_user_can( $this->capability ) ) {
            $should = $this->isAjax() ? $this->ajaxShould() : $this->regularShould();
        }
        return $should;
    }

    /**
     * Inizialize plugin: load text domain, and add hooks. When current is not an ajax request,
     * defers initialization on 'admin_enqueue_scripts' hook.
     */
    function init( $capability ) {
        $this->capability = $capability;
        $admin = get_role( 'administrator' );
        if ( ! in_array( $this->capability, $admin->capabilities, TRUE ) ) {
            $this->capability = 'edit_others_posts';
        }
        $this->enable();
    }

    /**
     * Initialize plugin for non-ajax requests on 'admin_enqueue_scripts' hooks.
     */
    function initLater() {
        if ( current_filter() !== 'admin_enqueue_scripts' || ! $this->should() ) return;
        if ( $this->screen === 'edit-post' ) {
            $this->post_list->enable();
        } elseif ( $this->screen === 'post' ) {
            $this->post_edit->enable();
        }
    }

    /**
     * Remove all the hooks ()
     *
     */
    function enable() {
        $this->meta->enable();
        if ( $this->isAjax() && $this->should() ) {
            $this->loadTextDomain();
            $this->ajaxInit();
        } elseif ( ! $this->isAjax() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'initLater' ], 0 );
        }
    }

    /**
     * Remove all the hooks added via init()
     *
     * @see GM\RejectNotify\Plugin::init()
     */
    function disable() {
        $this->meta->disable();
        $this->post_list->disable();
        $this->post_edit->disable();
    }

    private function ajaxInit() {
        $base = 'wp_ajax_' . self::SLUG;
        add_action( "{$base}_send_mail", [ $this->form, 'send' ] );
        add_action( "{$base}_show_form", [ $this->form, 'show' ] );
    }

    private function regularShould() {
        $screen = get_current_screen();
        $this->screen = $screen->id;
        if ( $this->screen === 'post' ) {
            global $post;
            if ( ! $post instanceof \WP_Post ) return FALSE;
            $status = get_post_status( $post->ID );
            $has_meta = $this->meta()->getMeta( $post->ID );
            if ( ! in_array( $status, [ 'pending', 'draft' ], TRUE ) && ! empty( $has_meta ) ) {
                $this->deleteMeta( $post->ID );
            } elseif ( $status === 'pending' ) {
                $not_the_author = (int) $post->post_author !== (int) get_current_user_id();
                return $not_the_author && ! user_can( $post->post_author, $this->capability );
            }
        } elseif ( $this->screen === 'edit-post' ) {
            global $wp_query;
            return $wp_query instanceof \WP_Query && $wp_query->get( 'post_status' ) === 'pending';
        } else {
            $this->screen = NULL;
            return FALSE;
        }
    }

    private function ajaxShould() {
        $method = filter_input( INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING );
        $type = strtoupper( $method ) === 'GET' ? INPUT_GET : INPUT_POST;
        $pid = filter_input( $type, 'postid', FILTER_SANITIZE_STRING );
        $this->action = filter_input( $type, 'action', FILTER_SANITIZE_STRING );
        $meta = $this->meta()->getMeta( $pid );
        if ( ! empty( $meta ) ) return FALSE;
        if ( $type === INPUT_GET ) {
            $this->action = filter_input( $type, 'action', FILTER_SANITIZE_STRING );
            return $this->action === self::SLUG . '_show_form';
        } else {
            $this->action = filter_input( $type, 'action', FILTER_SANITIZE_STRING );
            return $this->action === self::SLUG . '_send_mail';
        }
    }

}