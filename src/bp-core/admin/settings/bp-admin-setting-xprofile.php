<?php

class BP_Admin_Setting_Xprofile extends BP_Admin_Setting_tab {

	public function initialize() {
		$this->tab_label = __( 'Profiles', 'buddyboss' );
		$this->tab_name  = 'bp-xprofile';
		$this->tab_order = 10;
	}

	public function settings_save() {
		parent::settings_save();

        /**
         * sync bp-enable-member-dashboard with cutomizer settings.
         * @since BuddyBoss 3.1.1
         */
        $bp_nouveau_appearance = bp_get_option( 'bp_nouveau_appearance', array() );
        $bp_nouveau_appearance[ 'user_front_page' ] = isset( $_POST[ 'bp-enable-member-dashboard' ] ) ? $_POST[ 'bp-enable-member-dashboard' ] : 0;
        bp_update_option( 'bp_nouveau_appearance', $bp_nouveau_appearance );
	}

	public function register_fields() {
		$this->add_section( 'bp_xprofile', __( 'Profile Settings', 'buddyboss' ) );

		// Display name format.
		$this->add_field(
			'bp-display-name-format',
			__( 'Display Name Format', 'buddyboss' ),
			[ $this, 'callback_display_name_format']
		);

		// Avatars.
		$this->add_field( 'bp-disable-avatar-uploads', __( 'Profile Photo Uploads', 'buddyboss' ), 'bp_admin_setting_callback_avatar_uploads', 'intval' );

		// Cover images.
		if ( bp_is_active( 'xprofile', 'cover_image' ) ) {
			$this->add_field( 'bp-disable-cover-image-uploads', __( 'Cover Image Uploads', 'buddyboss' ), 'bp_admin_setting_callback_cover_image_uploads', 'intval' );
		}

		// Enable/Disable profile dashboard.
		$this->add_field( 'bp-enable-member-dashboard', __( 'Profile Dashboard', 'buddyboss' ), [$this, 'bp_admin_setting_callback_member_dashboard'], 'intval' );

        // Enable/Disable profile search.
		$this->add_field( 'bp-enable-profile-search', __( 'Profile Search', 'buddyboss' ), [$this, 'bp_admin_setting_callback_profile_search'], 'intval' );

		// new section for the profile type.
		$this->add_section( 'bp_profile_type', __( 'Profile Types', 'buddyboss' ) );

		// Enable/Disable Hide from Registration
		$this->add_field( 'bp-profile-type-hide-from-registration', __( 'Hide from Registration', 'buddyboss' ), [$this, 'bp_admin_setting_callback_profile_type_registration'], 'intval' );

		// default profile type.
		$this->add_field(
			'bp-default-profile-type',
			__( 'Default Profile Type', 'buddyboss' ),
			[$this, 'bp_admin_setting_callback_default_profile_type']
		);

		// Enable/Disable Require on Registration.
		$this->add_field( 'bp-profile-type-require-on-registration', __( 'Require on Registration', 'buddyboss' ), [$this, 'bp_admin_setting_callback_profile_type_require_on_registration'], 'intval' );

		// new section for import profile type.
		$this->add_section( 'bp_profile_type_import', __( 'Import Profile Types', 'buddyboss' ), [$this, 'bp_admin_settings_callback_import_profile_type_description'] );
	}

	/**
	 * Enable profile dashboard/front-page template.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_member_dashboard() {
		?>
			<input id="bp-enable-member-dashboard" name="bp-enable-member-dashboard" type="checkbox" value="1" <?php checked( bp_nouveau_get_appearance_settings( 'user_front_page' ) ); ?> />
			<label for="bp-enable-member-dashboard"><?php _e( 'Enable Dashboard for member profiles', 'buddyboss' ); ?></label>
		<?php
	}

	public function callback_display_name_format() {
		$options = [
			'first_name'      => __( 'First Name', 'buddyboss' ),
			'first_last_name' => __( 'First Name &amp; Last Name', 'buddyboss' ),
			'nickname'        => __( 'Nickname', 'buddyboss' ),
		];

		$current_value = bp_get_option( 'bp-display-name-format' );

		printf( '<select name="%1$s" for="%1$s">', 'bp-display-name-format' );
			foreach ( $options as $key => $value ) {
				printf(
					'<option value="%s" %s>%s</option>',
					$key,
					$key == $current_value? 'selected' : '',
					$value
				);
			}
		printf( '</select>' );

		printf(
			'<p class="description">%s</p>',
			__( '', 'buddyboss' )
		);
	}

	/**
	 * Enable member profile search.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_profile_search() {
		?>
			<input id="bp-enable-profile-search" name="bp-enable-profile-search" type="checkbox" value="1" <?php checked( ! bp_disable_advanced_profile_search() ); ?> />
			<label for="bp-enable-profile-search"><?php _e( 'Enable advanced profile search on the members directory.', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Remove profile type selection from Registration Form.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_profile_type_registration() {
		?>
		<input id="bp-profile-type-hide-from-registration" name="bp-profile-type-hide-from-registration" type="checkbox" value="1" <?php checked( ! bp_disable_profile_type_selection_from_registration_from() ); ?> />
		<label for="bp-profile-type-hide-from-registration"><?php _e( 'Remove profile type selection from Registration Form.', 'buddyboss' ); ?></label>
		<?php
	}

	/**
	 * Select Member type.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_default_profile_type() {

		$bp_member_type_selected    = bp_profile_type_default_profile_type();
		$post_ids                   = bp_get_active_profile_type_types();

		echo '<select id="enabled_default_member_type" name="bp-default-profile-type">';
		echo '<option value="">-- None --</option>';
		foreach ($post_ids as $pid) {

			$enable_register = get_post_meta($pid, '_bp_member_type_enable_registration', true);

			if ( $enable_register ) {

				//Member type label
				$bp_member_type_label = sanitize_title( get_post_meta( $pid, '_bp_member_type_label_singular_name', true) );

				?>
				<option value="<?php echo $bp_member_type_label ?>" <?php selected( $bp_member_type_selected, $bp_member_type_label ) ?>><?php echo get_the_title($pid); ?></option>
				<?php
			}
		}

		echo '</select>';

		printf(
			'<p class="description">%s</p>',
			__( 'Set default profile type in Registration Form.', 'buddyboss' )
		);
	}

	/**
	 * Require profile type selection in Registration Form.
	 *
	 * @since BuddyBoss 3.1.1
	 *
	 */
	public function bp_admin_setting_callback_profile_type_require_on_registration() {
		?>
		<input id="bp-profile-type-require-on-registration" name="bp-profile-type-require-on-registration" type="checkbox" value="1" <?php checked( ! bp_profile_type_require_on_registration() ); ?> />
		<label for="bp-profile-type-require-on-registration"><?php _e( 'Require profile type selection in Registration Form.', 'buddyboss' ); ?></label>
		<?php
	}

	public function bp_admin_settings_callback_import_profile_type_description() {
		$import_url = admin_url().'users.php?page=bp-profile-type-import';
		//echo '<a href="'. esc_url( $import_url ).'">Click here to go import page.</a>';
		printf(
			__( '<a href="%s">Click here to go to import page.</a>', 'buddyboss' ),
			esc_url( $import_url )
		);
	}
}

return new BP_Admin_Setting_Xprofile;
