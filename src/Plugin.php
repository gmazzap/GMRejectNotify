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
     * @param \GM\RejectNotify\Meta $meta
     * @param \GM\RejectNotify\PostEdit $post_edit
     * @param \GM\RejectNotify\PostList $post_list
     * @param \GM\RejectNotify\Form $form
     */
    function __construct( Meta $meta, PostEdit $post_edit, PostList $post_list, Form $form ) {
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
        load_plugin_textdomain( 'gmrejectnotify', FALSE, dirname( $file ) . '/lang/' );
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
     * Inizialize plugin: set capability, load textdomain, and add hooks.
     * When current is not an ajax request, defers initialization on 'admin_enqueue_scripts' hook.
     *
     * @uses GM\RejectNotify\Plugin::enable() load textdomain, and add hooks
     */
    function init( $capability ) {
        $this->capability = $capability;
        $admin = get_role( 'administrator' );
        if ( ! in_array( $this->capability, $admin->capabilities, TRUE ) ) {
            $this->capability = 'edit_others_posts';
        }
        $this->meta->enable();
        if ( ! $this->isAjax() ) {
            add_action( 'admin_enqueue_scripts', [ $this, 'enableLater' ], 0 );
        } elseif ( $this->should() ) {
            $this->loadTextDomain();
            $this->ajaxInit();
        }
    }

    /**
     * Add hooks for non-ajax requests.
     */
    function enableLater() {
        if ( current_filter() !== 'admin_enqueue_scripts' || ! $this->should() ) return;
        $this->loadTextDomain();
        $to_enable = $this->screen === 'post' ? $this->post_edit : $this->post_list;
        $to_enable->enable();
    }

    /**
     * Remove all the hooks added via init(), unset plugin classes and textdomain
     *
     * @see GM\RejectNotify\Plugin::init()
     */
    function disable( $save_meta = FALSE ) {
        if ( ! $save_meta ) $this->meta->disable();
        $this->post_list->disable();
        $this->post_edit->disable();
        if ( isset( $GLOBALS['l10n']['gmrejectnotify'] ) ) {
            unset( $GLOBALS['l10n']['gmrejectnotify'] );
        }
        if ( ! $save_meta ) unset( $this->meta );
        unset( $this->post_list );
        unset( $this->post_edit );
        unset( $this->form );
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
            $this->disable( TRUE );
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
            $should = $this->action === self::SLUG . '_show_form';
        } else {
            $this->action = filter_input( $type, 'action', FILTER_SANITIZE_STRING );
            $should = $this->action === self::SLUG . '_send_mail';
        }
        if ( ! $should ) {
            $this->disable( TRUE );
        }
        return $should;
    }

}