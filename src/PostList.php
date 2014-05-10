<?php namespace GM\RejectNotify;

class PostList {

    use PluginSettable;

    /**
     * Add hooks
     */
    function enable() {
        add_filter( 'manage_posts_columns', [ $this, 'colHead' ] );
        add_action( 'manage_posts_custom_column', [ $this, 'colContent' ], 10, 2 );
    }

    /**
     * Remove hooks
     */
    function disable() {
        remove_filter( 'manage_posts_columns', [ $this, 'colHead' ] );
        remove_action( 'manage_posts_custom_column', [ $this, 'colContent' ], 10, 2 );
    }

    /**
     * Filter the admin post table column header to add the 'Rejected Status' column
     *
     * @param array $columns
     * @return array
     */
    function colHead( $columns = '' ) {
        if ( ! $this->plugin->should() ) return;
        if ( current_filter() !== 'manage_posts_columns' || ! is_array( $columns ) ) {
            return $columns;
        }
        $columns['_custom-status'] = __( 'Rejected Status', 'gmrejectnotify' );
        return $columns;
    }

    /**
     * Print the 'Rejected Status' column according to post meta
     *
     * @param string $column
     * @param string $pid
     */
    function colContent( $column = '', $pid = '' ) {
        if (
            ! $this->plugin->should()
            || current_filter() !== 'manage_posts_custom_column'
            || $column !== '_custom-status'
        ) {
            return;
        }
        if ( (int) $this->plugin->meta()->getMeta( $pid ) === 1 ) {
            _e( 'Rejected', 'gmrejectnotify' );
        } else {
            _e( 'Not Rejected', 'gmrejectnotify' );
        }
    }

}