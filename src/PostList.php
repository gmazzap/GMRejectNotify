<?php namespace GM\RejectNotify;

class PostList {

    use PluginSettable;

    function enable() {
        add_action( 'publish_post', [ $this, 'deleteMeta' ] );
        add_filter( 'manage_posts_columns', [ $this, 'colHead' ] );
        add_action( 'manage_posts_custom_column', [ $this, 'colContent' ], 10, 2 );
    }

    function disable() {
        remove_action( 'publish_post', [ $this, 'deleteMeta' ] );
        remove_filter( 'manage_posts_columns', [ $this, 'colHead' ] );
        remove_action( 'manage_posts_custom_column', [ $this, 'colContent' ], 10, 2 );
    }

    function deleteMeta( $post_id = '', $blog_id = NULL ) {
        $switched = FALSE;
        if ( is_multisite() && (int) get_current_blog_id() !== (int) $blog_id ) {
            switch_to_blog( $switched );
            $switched = TRUE;
        }
        delete_post_meta( $post_id, Plugin::SLUG );
        if ( is_multisite() && $switched ) {
            restore_current_blog();
        }
    }

    function colHead( $defaults = '' ) {
        if ( ! $this->plugin->should() ) return;
        if ( current_filter() !== 'manage_posts_columns' || ! is_array( $defaults ) ) {
            return $defaults;
        }
        $defaults['_custom-status'] = __( 'Reject Status', 'gmrejectnotify' );
        return $defaults;
    }

    function colContent( $column = '', $pid = '' ) {
        if ( ! $this->plugin->should() ) return;
        if (
            current_filter() !== 'manage_posts_custom_column' || $column !== '_custom-status'
        ) {
            return;
        }
        if ( get_post_meta( $pid, Plugin::SLUG, TRUE ) === 'rejected' ) {
            _e( 'Rejected', 'gmrejectnotify' );
        } else {
            _e( '--', 'gmrejectnotify' );
        }
    }

}