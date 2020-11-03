<?php
/**
 * BuddyBoss Moderation items abstract Classes
 *
 * @package BuddyBoss\Moderation
 * @since   BuddyBoss 1.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation items.
 *
 * @since BuddyBoss 1.5.4
 */
abstract class BP_Moderation_Abstract {

	/**
	 * Moderation classes
	 *
	 * @var array
	 */
	public static $Moderation;

	/**
	 * Item type
	 *
	 * @var string
	 */
	public $item_type;

	/**
	 * Item type
	 *
	 * @var string
	 */
	public $alias = 'mo';

	/**
	 * Prepare Join sql for exclude Blocked items
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $item_id_field Items ID field name with alias of table.
	 *
	 * @return string|void
	 */
	protected function exclude_joint_query( $item_id_field ) {
		global $wpdb;
		$bp = buddypress();

		return ' ' . $wpdb->prepare( "LEFT JOIN {$bp->moderation->table_name} {$this->alias} ON ( {$this->alias}.item_id = $item_id_field AND {$this->alias}.item_type = %s )", $this->item_type ); // phpcs:ignore
	}

	/**
	 * Prepare Where sql for exclude Blocked items
	 *
	 * @return string|void
	 *
	 * @since BuddyBoss 1.5.4
	 */
	protected function exclude_where_query() {
		return "( {$this->alias}.hide_sitewide = 0 OR {$this->alias}.hide_sitewide IS NULL )";
	}

	/**
	 * Retrieve sitewide hidden items ids of particular item type.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $type Moderation items type.
	 *
	 * @return array $moderation See BP_Moderation::get() for description.
	 */
	public static function get_sitewide_hidden_item_ids( $type ) {
		$hidden_ids  = array();
		$moderations = bp_moderation_get_sitewide_hidden_item_ids( $type );

		if ( ! empty( $moderations ) && ! empty( $moderations['moderations'] ) ) {
			$hidden_ids = wp_list_pluck( $moderations['moderations'], 'item_id' );
		}

		return $hidden_ids;
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param integer $item_id Content item id
	 */
	abstract public static function get_content_owner_id( $item_id );

	/**
	 * Get class from content type.
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param string $type Content type
	 *
	 * @return string
	 */
	public static function get_class( $type = '' ) {
		$class = self::class;
		if ( ! empty( $type ) && ! empty( self::$Moderation ) && isset( self::$Moderation[ $type ] ) ) {
			if ( class_exists( self::$Moderation[ $type ] ) ) {
				$class = self::$Moderation[ $type ];
			}
		}

		return $class;
	}

	/**
	 * Report content
	 *
	 * @since BuddyBoss 1.5.4
	 *
	 * @param integer $item_id Content ID
	 * @param string  $type    Content type
	 *
	 * @return string
	 */
	public static function report( $item_id, $type ) {
		$moderation         = new BP_Moderation( $item_id, $type );
		$threshold          = false;
		$email_notification = false;

		// Get Moderation settings
		if ( BP_Moderation_Members::$moderation_type === $type ) {
			$is_allow = bp_is_moderation_member_blocking_enable();
			if ( bp_is_moderation_auto_suspend_enable() ) {
				$threshold          = bp_moderation_get_setting( 'bpm_blocking_auto_suspend_threshold', '5' );
				$email_notification = bp_is_moderation_blocking_email_notification_enable();
			}
		} else {
			$is_allow = bp_is_moderation_content_reporting_enable( 0, $type );
			if ( bp_is_moderation_auto_hide_enable() ) {
				$threshold          = bp_moderation_get_setting( 'bpm_reporting_auto_hide_threshold', '5' );
				$email_notification = bp_is_moderation_reporting_email_notification_enable();
			}
		}

		// Return error is moderation setting not enabled
		if ( empty( $is_allow ) ) {
			return new WP_Error( 'moderation_not_enable', __( 'Moderation not enabled.', 'buddyboss' ) );
		}

		if ( empty( $moderation->id ) ) {
			$moderation->item_id   = $item_id;
			$moderation->item_type = $type;
		}

		$moderation->updated_by   = get_current_user_id();
		$moderation->date_updated = current_time( 'mysql' );

		if ( ! empty( $threshold ) ) {

			// Todo: Check Threshold and auto-suspend/auto-hide item.

			if ( $email_notification ) {
				// Todo: Send email notification
			}
		}

		$moderation->save();

		return $moderation;
	}
}
