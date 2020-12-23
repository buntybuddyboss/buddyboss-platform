<?php
/**
 * BuddyBoss Moderation Report admin list table class.
 *
 * Props to WordPress core for the Comments admin screen, and its contextual
 * help text, on which this implementation is heavily based.
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation_Report
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * List table class for the Moderation report component admin page.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Report_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		// Define singular and plural labels, as well as whether we support AJAX.
		parent::__construct(
			array(
				'ajax'     => false,
				'plural'   => 'reports',
				'singular' => 'report',
			)
		);
	}

	/**
	 * Get an array of all the columns on the page.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array Column headers.
	 */
	public function get_column_info() {

		$screen         = get_current_screen();
		$hidden_columns = get_hidden_columns( $screen );
		$hidden_columns = ( ! empty( $hidden_columns ) ) ? $hidden_columns : array();

		$this->_column_headers = array(
			$this->get_columns(),
			$hidden_columns,
			$this->get_sortable_columns(),
			$this->get_default_primary_column_name(),
		);

		return $this->_column_headers;
	}

	/**
	 * Get name of default primary column
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return string
	 */
	protected function get_default_primary_column_name() {
		return 'reporter';
	}

	/**
	 * Display a message on screen when no items are found (e.g. no search matches).
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function no_items() {
		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		if ( ! empty( $tab ) && 'reported-content' === $tab ) {
			esc_html_e( 'Sorry, no reported content report was found.', 'buddyboss' );
		} else {
			esc_html_e( 'Sorry, no blocked members report were found.', 'buddyboss' );
		}
	}

	/**
	 * Set up items for display in the list table.
	 *
	 * Handles filtering of data, sorting, pagination, and any other data
	 * manipulation required prior to rendering.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function prepare_items() {

		$moderation_id           = filter_input( INPUT_GET, 'mid', FILTER_SANITIZE_NUMBER_INT );
		$moderation_content_type = filter_input( INPUT_GET, 'content_type', FILTER_SANITIZE_STRING );
		$moderation_request_data = new BP_Moderation( $moderation_id, $moderation_content_type );

		// Set current page.
		$page = $this->get_pagenum();
		// Set per page from the screen options.
		$per_page = $this->get_items_per_page( str_replace( '-', '_', "{$this->screen->id}_per_page" ) );

		$reporters = BP_Moderation::get_moderation_reporters( $moderation_request_data->id );

		$total_item  = ( ! empty( $reporters ) ) ? count( $reporters ) : 0;
		$total_pages = ceil( $total_item / $per_page );
		$page        = max( $page, 1 );
		$page        = min( $page, $total_pages );
		$offset      = ( $page - 1 ) * $per_page;

		if ( $offset < 0 ) {
			$offset = 0;
		}

		$this->items = array_slice( $reporters, $offset, $per_page );

		// Store information needed for handling table pagination.
		$this->set_pagination_args(
			array(
				'per_page'    => $per_page,
				'total_items' => $total_item,
				'total_pages' => ceil( $total_item / $per_page ),
			)
		);
	}

	/**
	 * Output the Moderation report data table.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function display() {
		$this->display_tablenav( 'top' ); ?>

		<h2 class="screen-reader-text">
			<?php
			/* translators: accessibility text */
			esc_html_e( 'Moderation Request list', 'buddyboss' );
			?>
		</h2>

		<table class="wp-list-table <?php echo esc_attr( implode( ' ', $this->get_table_classes() ) ); ?>">
			<thead>
			<tr>
				<?php $this->print_column_headers(); ?>
			</tr>
			</thead>

			<tbody id="the-moderation-report-list">
			<?php $this->display_rows_or_placeholder(); ?>
			</tbody>

			<tfoot>
			<tr>
				<?php $this->print_column_headers( false ); ?>
			</tr>
			</tfoot>
		</table>
		<?php

		$this->display_tablenav( 'bottom' );
	}

	/**
	 * Get the table column titles.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @return array Array of column titles.
	 * @see   WP_List_Table::single_row_columns()
	 */
	public function get_columns() {

		$tab = filter_input( INPUT_GET, 'tab', FILTER_SANITIZE_STRING );
		if ( ! empty( $tab ) && 'reported-content' === $tab ) {
			$columns = array(
				'reporter' => esc_html__( 'Reporter', 'buddyboss' ),
				'category' => esc_html__( 'Category', 'buddyboss' ),
				'date'     => esc_html__( 'Date', 'buddyboss' ),
			);
		} else {
			$columns = array(
				'reporter' => esc_html__( 'Blocking Member', 'buddyboss' ),
				'date'     => esc_html__( 'Date', 'buddyboss' ),
			);
		}

		/**
		 * Filters the titles for the columns for the moderation report list table.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param array $value Array of slugs and titles for the columns.
		 */
		return apply_filters( 'bp_moderation_report_list_table_get_columns', $columns );
	}

	/**
	 * Generate content for a single row of the table.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param object $item The current item.
	 */
	public function single_row( $item ) {
		$item = (array) $item;
		echo '<tr>';
		wp_kses_post( $this->single_row_columns( $item ) );
		echo '</tr>';
	}

	/**
	 * Function to item reporter.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_reporter( $item = array() ) {
		printf( '<strong>%s</strong>', wp_kses_post( bp_core_get_userlink( $item['user_id'] ) ) );
	}

	/**
	 * Function to item category.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item loop item.
	 */
	public function column_category( $item = array() ) {
		$term_data        = get_term( $item['category_id'] );
		$term_name        = ( ! is_wp_error( $term_data ) && ! empty( $term_data->name ) ) ? $term_data->name : esc_html__( 'Other', 'buddyboss' );
		$term_description = ( ! is_wp_error( $term_data ) && ! empty( $term_data->description ) ) ? $term_data->description : $item['content'];
		printf( '<strong class="bp-cat-name">%s</strong><p class="description">%s</p>', esc_html( $term_name ), wp_kses_post( $term_description ) );
	}

	/**
	 * Function to show the item date.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $item item data.
	 */
	public function column_date( $item = array() ) {
		echo esc_html(
			date_i18n(
				get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
				strtotime( $item['date_created'] )
			)
		);
	}

	/**
	 * Allow plugins to add their custom column.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array  $item        Information about the current row.
	 * @param string $column_name The column name.
	 *
	 * @return string
	 */
	public function column_default( $item = array(), $column_name = '' ) {

		/**
		 * Filters a string to allow plugins to add custom column content.
		 *
		 * @since BuddyBoss 2.0.0
		 *
		 * @param string $value       Empty string.
		 * @param string $column_name Name of the column being rendered.
		 * @param array  $item        The current moderation report item in the loop.
		 */
		return apply_filters( 'bp_moderation_admin_get_custom_column', '', $column_name, $item );
	}
}