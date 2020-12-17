<?php
/**
 * BuddyBoss Moderation Activity Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Activity extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'activity';

	/**
	 * BP_Moderation_Activity constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		// Register moderation data.
		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend and if Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bp_suspend_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
		add_filter( 'bp_activity_activity_pre_validate', array( $this, 'restrict_single_item' ), 10, 2 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button_sub_items", array( $this, 'update_button_sub_items' ) );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $activity_id activity id.
	 *
	 * @return string
	 */
	public static function get_permalink( $activity_id ) {
		$url = bp_activity_get_permalink( $activity_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $activity_id Activity id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $activity_id ) {
		$activity = new BP_Activity_Activity( $activity_id );

		return ( ! empty( $activity->user_id ) ) ? $activity->user_id : 0;
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Activity', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Update where query Remove hidden/blocked user's Activities
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where   Activity Where sql.
	 * @param object $suspend Suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias = $suspend->alias;

		$sql = $this->exclude_where_query();
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		return $where;
	}

	/**
	 * Validate the activity is valid or not.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param boolean $restrict Check the item is valid or not.
	 * @param object  $activity Current activity object.
	 *
	 * @return false
	 */
	public function restrict_single_item( $restrict, $activity ) {

		if ( 'activity_comment' !== $activity->type && $this->is_content_hidden( (int) $activity->id ) ) {
			return false;
		}

		return $restrict;
	}

	/**
	 * Function to modify button sub item
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $item_id Item id.
	 *
	 * @return array
	 */
	public function update_button_sub_items( $item_id ) {

		$activity = new BP_Activity_Activity( $item_id );

		if ( empty( $activity->id ) ) {
			return array();
		}

		/**
		 * Restricted Report link for Auto-created activity. Like Group create, Group join, Reply create etc.
		 */
		if ( in_array( $activity->type, array( 'new_member', 'new_avatar', 'updated_profile', 'created_group', 'joined_group', 'group_details_updated', 'friendship_created', 'friendship_accepted', 'friends_register_activity_action', 'new_blog_post', 'new_blog' ), true ) ) {
			return array(
				'id'   => false,
				'type' => false,
			);
		}

		$sub_items = array();
		switch ( $activity->type ) {
			case 'bbp_forum_create':
				$forum_id = $activity->item_id;
				if ( function_exists( 'bbp_is_forum_group_forum' ) && bbp_is_forum_group_forum( $forum_id ) ) {
					$sub_items['id']   = current( bbp_get_forum_group_ids( $forum_id ) );
					$sub_items['type'] = BP_Moderation_Groups::$moderation_type;
				} else {
					$sub_items['id']   = $activity->item_id;
					$sub_items['type'] = BP_Moderation_Forums::$moderation_type;
				}
				break;
			case 'bbp_topic_create':
				$sub_items['id']   = ( 'groups' === $activity->component ) ? $activity->secondary_item_id : $activity->item_id;
				$sub_items['type'] = BP_Moderation_Forum_Topics::$moderation_type;
				break;
			case 'bbp_reply_create':
				$sub_items['id']   = ( 'groups' === $activity->component ) ? $activity->secondary_item_id : $activity->item_id;
				$sub_items['type'] = BP_Moderation_Forum_Replies::$moderation_type;
				break;
		}

		return $sub_items;
	}
}