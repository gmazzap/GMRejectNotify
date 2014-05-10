<?php namespace GM\RejectNotify;

class Meta {

    private $blog_id;

    /**
     * Add hooks
     */
    function enable() {
        $this->blog_id = get_current_blog_id();
        add_action( 'post_updated', [ $this, 'maybeDeleteMeta' ], 10, 3 );
        add_action( Plugin::SLUG . '_mail_sended_updated', [ $this, 'addMeta' ] );
    }

    /**
     * Remove hooks
     */
    function disable() {
        remove_action( 'post_updated', [ $this, 'maybeDeleteMeta' ], 10, 3 );
        remove_action( Plugin::SLUG . '_mail_sended_updated', [ $this, 'addMeta' ] );
    }

    /**
     * Return the post meta to check if post was already rejected.
     *
     * @param string|int $post_id
     * @return string
     */
    function getMeta( $post_id ) {
        return get_post_meta( $post_id, Plugin::META, TRUE );
    }

    /**
     * Add the post meta
     *
     * @param array $data
     */
    function addMeta( $data ) {
        if ( isset( $data['postid'] ) && (int) $data['postid'] > 0 ) {
            update_post_meta( $data['postid'], Plugin::META, 1 );
        }
    }

    /**
     * Remove post meta when post is updated
     *
     * @param string|int $post_id
     * @param \WP_Post $post_after
     * @param \WP_Post $post_before
     */
    function maybeDeleteMeta( $post_id, $post_after, $post_before ) {
        if (
            current_filter() === 'post_updated'
            && $post_before->post_status === 'pending'
            && get_current_blog_id() === $this->blog_id
            && $this->comparePosts( $post_after, $post_before )
            && (int) $this->getMeta( $post_id ) !== 0
        ) {
            $this->deleteMeta( $post_id );
        }
    }

    /**
     * Remove post meta
     *
     * @param string|int $post_id
     */
    function deleteMeta( $post_id = '' ) {
        delete_post_meta( $post_id, Plugin::META );
    }

    private function comparePosts( \WP_Post $post_a, \WP_Post $post_b ) {
        foreach ( [ 'post_status', 'post_title', 'post_content', 'post_excerpt' ] as $var ) {
            if ( $post_a->$var !== $post_b->$var ) return TRUE;
        }
        return FALSE;
    }

}