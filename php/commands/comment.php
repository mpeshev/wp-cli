<?php

/**
 * Manage comments.
 *
 * @package wp-cli
 */
class Comment_Command extends WP_CLI_Command {

	/**
	 * Insert a comment.
	 *
	 * ## OPTIONS
	 *
	 * --<field>=<value>
	 * : Field values for the new comment. See wp_insert_comment().
	 *
	 * --porcelain
	 * : Output just the new comment id.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment create --comment_post_ID=15 --comment_content="hello blog" --comment_author="wp-cli"
	 *
	 * @synopsis --<field>=<value> [--porcelain]
	 */
	public function create( $args, $assoc_args ) {
		$post = get_post( $assoc_args['comment_post_ID'] );
		if ( !$post ) {
			WP_CLI::error( "Cannot find post $comment_post_ID" );
		}

		// We use wp_insert_comment() instead of wp_new_comment() to stay at a low level and avoid wp_die() formatted messages or notifications
		$comment_id = wp_insert_comment( $assoc_args );

		if ( !$comment_id ) {
			WP_CLI::error( "Could not create comment" );
		}

		if ( isset( $assoc_args['porcelain'] ) )
			WP_CLI::line( $comment_id );
		else
			WP_CLI::success( "Inserted comment $comment_id." );
	}

	/**
	 * Delete a comment.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the comment to delete.
	 *
	 * --force
	 * : Skip the trash bin.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment delete 1337 --force
	 *
	 * @synopsis <id> [--force]
	 */
	public function delete( $args, $assoc_args ) {
		list( $comment_id ) = $args;

		if ( wp_delete_comment( $comment_id, isset( $assoc_args['force'] ) ) ) {
			WP_CLI::success( "Deleted comment $comment_id." );
		} else {
			WP_CLI::error( "Failed deleting comment $comment_id" );
		}
	}

	private function call( $args, $status, $success, $failure ) {
		list( $comment_id ) = $args;

		$func = sprintf( 'wp_%s_comment', $status );

		if ( $func( $comment_id ) ) {
			WP_CLI::success( "$success comment $comment_id." );
		} else {
			WP_CLI::error( "$failure comment $comment_id" );
		}
	}

	private function set_status( $args, $status, $success ) {
		list( $comment_id ) = $args;

		$r = wp_set_comment_status( $comment_id, 'approve', true );

		if ( is_wp_error( $r ) ) {
			WP_CLI::error( $r );
		} else {
			WP_CLI::success( "$success comment $comment_id" );
		}
	}

	/**
	 * Trash a comment.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the comment to trash.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment trash 1337
	 *
	 * @synopsis <id>
	 */
	public function trash( $args, $assoc_args ) {
		$this->call( $args, __FUNCTION__, 'Trashed', 'Failed trashing' );
	}

	/**
	 * Untrash a comment.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the comment to untrash.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment untrash 1337
	 *
	 * @synopsis <id>
	 */
	public function untrash( $args, $assoc_args ) {
		$this->call( $args, __FUNCTION__, 'Untrashed', 'Failed untrashing' );
	}

	/**
	 * Spam a comment.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the comment to mark as spam.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment spam 1337
	 *
	 * @synopsis <id>
	 */
	public function spam( $args, $assoc_args ) {
		$this->call( $args, __FUNCTION__, 'Marked as spam', 'Failed marking as spam' );
	}

	/**
	 * Unspam a comment.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the comment to unmark as spam.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment unspam 1337
	 * @synopsis <id>
	 */
	public function unspam( $args, $assoc_args ) {
		$this->call( $args, __FUNCTION__, 'Unspammed', 'Failed unspamming' );
	}

	/**
	 * Approve a comment.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the comment to approve.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment approve 1337
	 *
	 * @synopsis <id>
	 */
	public function approve( $args, $assoc_args ) {
		if( $this->_comment_exist( $args ) ) {
			$this->set_status( $args, 'approve', "Approved" );
		}
	}

	/**
	 * Unapprove a comment.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the comment to unapprove.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment unapprove 1337
	 *
	 * @synopsis <id>
	 */
	public function unapprove( $args, $assoc_args ) {
		if( $this->_comment_exist( $args ) ) {
			$this->set_status( $args, 'hold', "Unapproved" );
		}
	}

	/**
	 * Count comments, on whole blog or on a given post.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the post to count comments in
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment count
	 *     wp comment count 42
	 *
	 * @synopsis [<post-id>]
	 */
	public function count( $args, $assoc_args ) {
		$post_id = isset( $args[0] ) ? $args[0] : 0;

		$count = wp_count_comments( $post_id );

		// Move total_comments to the end of the object
		$total = $count->total_comments;
		unset( $count->total_comments );
		$count->total_comments = $total;

		foreach ( $count as $status => $count ) {
			WP_CLI::line( str_pad( "$status:", 17 ) . $count );
		}
	}

	/**
	 * Get status of a comment.
	 *
	 * ## OPTIONS
	 *
	 * <ID>
	 * : The ID of the comment to check
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment status 1337
	 *
	 * @synopsis <id>
	 */
	public function status( $args, $assoc_args ) {
		list( $comment_id ) = $args;

		$status = wp_get_comment_status( $comment_id );

		if ( false === $status ) {
			WP_CLI::error( "Could not check status of comment $comment_id." );
		} else {
			WP_CLI::line( $status );
		}
	}

	/**
	 * Get last approved comment.
	 *
	 * ## OPTIONS
	 *
	 * --id
	 * : Output just the last comment id.
	 *
	 * --full
	 * : Output complete comment information.
	 *
	 * ## EXAMPLES
	 *
	 *     wp comment last --full
	 *
	 * @synopsis [--id] [--full]
	 */
	function last( $args = array(), $assoc_args = array() ) {
		$last = get_comments( array( 'number' => 1, 'status' => 'approve' ) );

		list( $comment ) = $last;

		if ( isset( $assoc_args['id'] ) ) {
			WP_CLI::line( $comment->comment_ID );
			exit( 1 );
		}

		WP_CLI::line( "%yLast approved comment:%n " );

		if ( isset( $assoc_args['full'] ) ) {
			$keys = array_keys( get_object_vars( $comment ) );
		} else {
			$keys = array( 'comment_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content' );
		}

		foreach ( $keys as $key ) {
			WP_CLI::line( str_pad( "$key:", 23 ) . $comment->$key );
		}
	}
	
	/**
	 * Helper function for checking if a comment exists.
	 * 
	 * @param array $args arguments array with first element - comment ID.
	 * @return boolean true on success, or exit(1) on failure.
	 */
	private function _comment_exist( $args ) {
		$comment_id = (int) $args[0];
		$comment = get_comment( $comment_id );
		
		if( is_null( $comment ) ) {
			WP_CLI::error( "Comment with ID $args[0] does not exist." );
			exit( 1 );
		}

		return true;
	}
}

WP_CLI::add_command( 'comment', 'Comment_Command' );

