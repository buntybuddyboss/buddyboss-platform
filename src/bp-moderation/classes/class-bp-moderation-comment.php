<?php
/**
 * BuddyBoss Moderation Comment Class
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Comment.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Comment extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'comment';

	/**
	 * BP_Moderation_Comment constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {
		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		// Register moderation data.
		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ), 11 );

		add_filter( 'comment_text', array( $this, 'blocked_comment_text' ), 10, 2 );
		add_filter( 'get_comment_author_link', array( $this, 'blocked_get_comment_author_link' ), 10, 3 );
		add_filter( 'get_comment_author', array( $this, 'blocked_get_comment_author' ), 10, 2 );
		add_filter( 'get_comment_link', array( $this, 'blocked_get_comment_link' ), 10, 2 );
		add_filter( 'get_comment_date', array( $this, 'blocked_get_comment_date' ), 10, 3 );
		add_filter( 'get_comment_time', array( $this, 'blocked_get_comment_time' ), 10, 5 );
		add_filter( 'comment_reply_link', array( $this, 'blocked_comment_reply_link' ), 10, 3 );
		add_filter( 'edit_comment_link', array( $this, 'blocked_edit_comment_link' ), 10, 2 );

		/**
		 * Moderation code should not add for WordPress backend or IF component is not active or Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() || ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// button class.
		add_filter( "bp_moderation_{$this->item_type}_button_args", array( $this, 'update_button_args' ), 10, 2 );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $comment_id comment id.
	 *
	 * @return string
	 */
	public static function get_permalink( $comment_id ) {
		$url = get_comment_link( $comment_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Blog Comments', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Update comment text for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string          $comment_text Text of the current comment.
	 * @param WP_Comment|null $comment      The comment object. Null if not found.
	 *
	 * @return string
	 */
	public function blocked_comment_text( $comment_text, $comment ) {
		if ( ! $comment instanceof WP_Comment ) {
			return $comment_text;
		}

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
			$comment_author_id = ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
			$is_user_blocked   = bp_moderation_is_user_blocked( $comment_author_id );

			if ( $is_user_blocked ) {
				$comment_text = esc_html__( 'This content has been hidden as you have blocked this member.', 'buddyboss' );
			} else {
				$comment_text = esc_html__( 'This content has been hidden as the member is suspended.', 'buddyboss' );
			}
		}

		return $comment_text;
	}

	/**
	 * Update comment author link for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $return     The HTML-formatted comment author link.
	 *                           Empty for an invalid URL.
	 * @param string $author     The comment author's username.
	 * @param int    $comment_id The comment ID.
	 *
	 * @return string
	 */
	public function blocked_get_comment_author_link( $return, $author, $comment_id ) {

		if ( $this->is_content_hidden( $comment_id ) ) {
			$return = esc_html__( 'Blocked Member', 'buddyboss' );
		}

		return $return;
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $comment_id Comment id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $comment_id ) {
		$comment = get_comment( $comment_id );

		return ( ! empty( $comment->user_id ) ) ? $comment->user_id : 0;
	}

	/**
	 * Update comment author for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $author     The comment author's username.
	 * @param int    $comment_id The comment ID.
	 *
	 * @return string
	 */
	public function blocked_get_comment_author( $author, $comment_id ) {

		if ( $this->is_content_hidden( $comment_id ) ) {
			$author = '';
		}

		return $author;
	}

	/**
	 * Update comment link for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string     $link    The comment permalink with '#comment-$id' appended.
	 * @param WP_Comment $comment The current comment object.
	 *
	 * @return string
	 */
	public function blocked_get_comment_link( $link, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $link;
		}

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Update comment date for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string|int $date    Formatted date string or Unix timestamp.
	 * @param string     $format  The format of the date.
	 * @param WP_Comment $comment The comment object.
	 *
	 * @return string
	 */
	public function blocked_get_comment_date( $date, $format, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $date;
		}

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
			$date = '';
		}

		return $date;
	}

	/**
	 * Update comment time for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string|int $date      The comment time, formatted as a date string or Unix timestamp.
	 * @param string     $format    Date format.
	 * @param bool       $gmt       Whether the GMT date is in use.
	 * @param bool       $translate Whether the time is translated.
	 * @param WP_Comment $comment   The comment object.
	 *
	 * @return string
	 */
	public function blocked_get_comment_time( $date, $format, $gmt, $translate, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $date;
		}

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
			$date = '';
		}

		return $date;
	}

	/**
	 * Update comment reply link for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string     $link    The HTML markup for the comment reply link.
	 * @param array      $args    An array of arguments overriding the defaults.
	 * @param WP_Comment $comment The object of the comment being replied.
	 *
	 * @return string
	 */
	public function blocked_comment_reply_link( $link, $args, $comment ) {

		if ( ! $comment instanceof WP_Comment ) {
			return $link;
		}

		if ( $this->is_content_hidden( $comment->comment_ID ) ) {
			$link = '';
		} else if ( ! empty( $link ) ) {
			$link .= bp_moderation_get_report_button(
				array(
					'id'                => 'comment_report',
					'component'         => 'moderation',
					'must_be_logged_in' => true,
					'button_attr'       => array(
						'data-bp-content-id'   => $comment->comment_ID,
						'data-bp-content-type' => self::$moderation_type,
					),
				)
			);
		}

		return $link;
	}

	/**
	 * Update comment edit link for blocked comment.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $link       Anchor tag for the edit link.
	 * @param int    $comment_id Comment ID.
	 *
	 * @return string
	 */
	public function blocked_edit_comment_link( $link, $comment_id ) {

		if ( $this->is_content_hidden( $comment_id ) ) {
			$link = '';
		}

		return $link;
	}

	/**
	 * Function to modify the button args
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $args    Button args.
	 * @param int   $item_id Item id.
	 *
	 * @return array
	 */
	public function update_button_args( $args, $item_id ) {
		$args['button_attr']['class'] = 'report-content';

		return $args;
	}

	/**
	 * Check content is hidden or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	protected function is_content_hidden( $item_id ) {

		$author_id = self::get_content_owner_id( $item_id );

		if ( ( $this->is_member_blocking_enabled() && ! empty( $author_id ) && ! bp_moderation_is_user_suspended( $author_id ) && bp_moderation_is_user_blocked( $author_id ) ) ||
		     ( $this->is_reporting_enabled() && BP_Core_Suspend::check_hidden_content( $item_id, $this->item_type ) ) ) {
			return true;
		}

		return false;
	}
}
