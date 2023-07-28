<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * bp_group_documents_record_add()
 *
 * Records the creation of a new document: [user] uploaded the file [name] to [group]
 *
 * @param type $document
 */
function bp_group_documents_record_add( $document ) {
	$bp = buddypress();

	$params = array(
		'action'            => sprintf( __( '%1$s uploaded the file: %2$s to %3$s', 'bp-group-documents' ), bp_core_get_userlink( $bp->loggedin_user->id ), '<a href="' . $document->get_url() . '">' . esc_attr( $document->name ) . '</a>', '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_attr( $bp->groups->current_group->name ) . '</a>' ),
		'content'           => $document->description,
		'component_action'  => 'added_group_document',
		'secondary_item_id' => $document->id,
	);

	bp_group_documents_record_activity( $params );

	do_action( 'bp_group_documents_record_add', $document );
}

add_action( 'bp_group_documents_add_success', 'bp_group_documents_record_add', 15, 1 );

/**
 * bp_group_documents_record_edit()
 *
 * records the modification of a document: "[user] edited the file [name] in [group]"
 *
 * @param type $document
 */
function bp_group_documents_record_edit( $document ) {
	$bp = buddypress();

	$params = array(
		'action'            => sprintf( __( '%1$s edited the file: %2$s in %3$s', 'bp-group-documents' ), bp_core_get_userlink( $bp->loggedin_user->id ), '<a href="' . $document->get_url() . '">' . esc_attr( $document->name ) . '</a>', '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_attr( $bp->groups->current_group->name ) . '</a>' ),
		'component_action'  => 'edited_group_document',
		'secondary_item_id' => $document->id,
	);
	bp_group_documents_record_activity( $params );
	do_action( 'bp_group_documents_record_edit', $document );
}

add_action( 'bp_group_documents_edit_success', 'bp_group_documents_record_edit', 15, 1 );


/**
 * bp_group_documents_record_delete()
 *
 * records the deletion of a document: "[user] deleted the file [name] from [group]"
 *
 * @param type $document
 */
function bp_group_documents_record_delete( $document ) {
	$bp = buddypress();

	$params = array(
		'action'            => sprintf( __( '%1$s deleted the file: %2$s from %3$s', 'bp-group-documents' ), bp_core_get_userlink( $bp->loggedin_user->id ), $document->name, '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_attr( $bp->groups->current_group->name ) . '</a>' ),
		'component_action'  => 'deleted_group_document',
		'secondary_item_id' => $document->id,
	);
	bp_group_documents_record_activity( $params );
	do_action( 'bp_group_documents_record_delete', $document );
}

add_action( 'bp_group_documents_delete_success', 'bp_group_documents_record_delete', 15, 1 );

/**
 * bp_group_documents_record_activity()
 *
 * If the activity stream component is installed, this function will record upload
 * and edit activity items.
 *
 * @param type $args
 * @return boolean
 */
function bp_group_documents_record_activity( $args = '' ) {
	$bp = buddypress();

	if ( ! function_exists( 'bp_activity_add' ) ) {
		return false;
	}

	$defaults = array(
		'primary_link'      => bp_get_group_permalink( $bp->groups->current_group ),
		'component_name'    => 'groups',
		'component_action'  => false,
		'hide_sitewide'     => false, // Optional
		'user_id'           => $bp->loggedin_user->id, // Optional
		'item_id'           => $bp->groups->current_group->id, // Optional
		'secondary_item_id' => false, // Optional
		'content'           => null, //Optional
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// If the group is not public, don't broadcast updates.
	if ( 'public' !== $bp->groups->current_group->status ) {
		$hide_sitewide = 1;
	}

	return bp_activity_add(
		array(
			'content'           => $content,
			'primary_link'      => $primary_link,
			'component_name'    => $component_name,
			'component_action'  => $component_action,
			'user_id'           => $user_id,
			'item_id'           => $item_id,
			'secondary_item_id' => $secondary_item_id,
			'hide_sitewide'     => $hide_sitewide,
			'action'            => $action,
		)
	);
}

/**
 * bp_group_documents_delete_activity_by_document()
 *
 * Deletes all previous activity for the document passed
 *
 * @param type $document
 */
function bp_group_documents_delete_activity_by_document( $document ) {

	$params = array(
		'item_id'           => $document->group_id,
		'secondary_item_id' => $document->id,
	);

	bp_group_documents_delete_activity( $params );
	do_action( 'bp_group_documents_delete_activity_by_document', $document );
}

add_action( 'bp_group_documents_delete_success', 'bp_group_documents_delete_activity_by_document', 14, 1 );
add_action( 'bp_group_documents_delete_with_group', 'bp_group_documents_delete_activity_by_document' );

/**
 * bp_group_documents_delete_activity()
 *
 * Deletes a previously recorded activity - useful for making sure there are no broken links
 * if something is deleted.
 *
 * @param type $args
 */
function bp_group_documents_delete_activity( $args = true ) {
	if ( function_exists( 'bp_activity_delete_by_item_id' ) ) {
		$defaults = array(
			'item_id'           => false,
			'component_name'    => 'groups',
			'component_action'  => false,
			'user_id'           => false,
			'secondary_item_id' => false,
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		bp_activity_delete_by_item_id(
			array(
				'item_id'           => $item_id,
				'component_name'    => $component_name,
				'component_action'  => $component_action, // optional
				'user_id'           => $user_id, // optional
				'secondary_item_id' => $secondary_item_id, // optional
			)
		);
	}
}

/**
 *
 *  Add Buddypress Groups Documents activity types to the activity filter dropdown
 *  @since 0.4.3
 * @version 1.5, 4/12/2013, stergatu, chanced name in order to avoid conficts with other plugins
 */
function bp_group_documents_activity_filter_options() {
	$nav_page_name = get_option( 'bp_group_documents_nav_page_name' );
	$name          = ! empty( $nav_page_name ) ? $nav_page_name : __( 'Documents', 'bp-group-documents' );
	?>
	<option value="added_group_document"><?php printf( __( 'Show New Group %s', 'bp-group-documents' ), $name ); ?>
	</option>
	<option value="edited_group_document"><?php printf( __( 'Show Group %s Edits', 'bp-group-documents' ), $name ); ?></option>
	<?php
}

$dropdowns = apply_filters(
	'bp_group_documents_activity_filter_locations',
	array(
		'bp_activity_filter_options',
		'bp_group_activity_filter_options',
		'bp_member_activity_filter_options',
	)
);
foreach ( $dropdowns as $hook ) {
	add_action( $hook, 'bp_group_documents_activity_filter_options' );
}
