<?php
// Exit if accessed directly
if (! defined('ABSPATH') ) {
    exit;
}

/**
 * bp_group_documents_record_add()
 *
 * Records the creation of a new document: [user] uploaded the file [name] to [group]
 *
 * @version 2.0 17/2/2025, compatibility BP 12+
 *
 * @param type $document
 */
function bp_group_documents_record_add( $document )
{
    $bp = buddypress();
    $buddypress_version = bp_get_version(); // Get the BuddyPress version
    $compare_version = '12.0.0'; // The version to compare against
            // Compare the versions
    if (version_compare($buddypress_version, $compare_version, '>=') ) {
        
        $params = array(
        'action'            => sprintf(
        /* translators: %1$s: User who uploaded the file, %2$s: File name with a link, %3$s: Group name with a link */
            esc_html__('%1$s uploaded the file: %2$s to %3$s', 'bp-group-documents'),
            wp_kses_post(bp_core_get_userlink($bp->loggedin_user->id)), 
            '<a href="' . esc_url($document->get_url()) . '">' . esc_attr($document->name) . '</a>', 
            '<a href="' . esc_url(bp_get_group_url($bp->groups->current_group)) . '">' . esc_attr($bp->groups->current_group->name) . '</a>'
        ),
        'content'           => $document->description,
        'component_action'  => 'added_group_document',
        'secondary_item_id' => $document->id,
        );
    } else {
        
        $params = array(
        'action'            => sprintf(
        /* translators: %1$s: User who uploaded the file, %2$s: File name with a link, %3$s: Group name with a link */
            esc_html__('%1$s uploaded the file: %2$s to %3$s', 'bp-group-documents'),
            wp_kses_post(bp_core_get_userlink($bp->loggedin_user->id)), 
            '<a href="' . esc_url($document->get_url()). '">' . esc_attr($document->name) . '</a>', 
            '<a href="' . esc_url(bp_get_group_permalink($bp->groups->current_group)) . '">' . esc_attr($bp->groups->current_group->name) . '</a>' 
        ),
        'content'           => $document->description,
        'component_action'  => 'added_group_document',
        'secondary_item_id' => $document->id,
        );
    }
    bp_group_documents_record_activity($params);
    do_action('bp_group_documents_record_add', $document);
}

add_action('bp_group_documents_add_success', 'bp_group_documents_record_add', 15, 1);


/**
 * bp_group_documents_record_edit()
 *
 * records the modification of a document: "[user] edited the file [name] in [group]"
 *
 * @version 2.0 17/2/2025, compatibility BP 12+
 * 
 * @param type $document
 */
function bp_group_documents_record_edit( $document )
{
    $bp = buddypress();
    $buddypress_version = bp_get_version(); // Get the BuddyPress version
    $compare_version = '12.0.0'; // The version to compare against
            // Compare the versions
    if (version_compare($buddypress_version, $compare_version, '>=') ) {
        $params = array(
        'action'            => sprintf(
        // Translators: %1$s: User link, %2$s: Document link, %3$s: Group link
            __('%1$s edited the file: %2$s in %3$s', 'bp-group-documents'),
           wp_kses_post(bp_core_get_userlink($bp->loggedin_user->id)), 
            '<a href="' . esc_url($document->get_url()) . '">' . esc_attr($document->name) . '</a>', 
            '<a href="' .  esc_url(bp_get_group_url($bp->groups->current_group)) . '">' . esc_attr($bp->groups->current_group->name) . '</a>'  
        ),
        'component_action'  => 'edited_group_document',
        'secondary_item_id' => $document->id,
        );
    } else {
        $params = array(
        'action'            => sprintf(
        // Translators: %1$s: User link, %2$s: Document link, %3$s: Group link
            __('%1$s edited the file: %2$s in  %3$s', 'bp-group-documents'),
            wp_kses_post(bp_core_get_userlink($bp->loggedin_user->id)), 
            '<a href="' . esc_url($document->get_url()) . '">' . esc_attr($document->name). '</a>', 
            '<a href="' .  esc_url(bp_get_group_permalink($bp->groups->current_group)) . '">' . esc_attr($bp->groups->current_group->name) . '</a>'         
        ),
        'component_action'  => 'edited_group_document',
        'secondary_item_id' => $document->id,
        );
    }
    
    bp_group_documents_record_activity($params);
    do_action('bp_group_documents_record_edit', $document);
}

add_action('bp_group_documents_edit_success', 'bp_group_documents_record_edit', 15, 1);


/**
 * bp_group_documents_record_delete()
 *
 * records the deletion of a document: "[user] deleted the file [name] from [group]"
 *
 * @param type $document
 * 
 * @version 2.0 17/2/2025, compatibility BP 12+
 */
function bp_group_documents_record_delete( $document )
{
    $bp = buddypress();
    $buddypress_version = bp_get_version(); // Get the BuddyPress version
    $compare_version = '12.0.0'; // The version to compare against
            // Compare the versions
    if (version_compare($buddypress_version, $compare_version, '>=') ) {
        $params = array(
        'action'            => sprintf(
        // Translators: %1$s: User link, %2$s: Document name, %3$s: Group link
            esc_html__('%1$s deleted the file: %2$s from %3$s', 'bp-group-documents'), 
            wp_kses_post(bp_core_get_userlink($bp->loggedin_user->id)), esc_html($document->name), 
            '<a href="' .esc_url(bp_get_group_url($bp->groups->current_group)) . '">' . 
            esc_attr($bp->groups->current_group->name) . '</a>' 
        ),
        'component_action'  => 'deleted_group_document',
        'secondary_item_id' => $document->id,
        );
    }
    else {
        $params = array(
        'action'            => sprintf(
        // Translators: %1$s: User link, %2$s: Document name, %3$s: Group link
            esc_html__('%1$s deleted the file: %2$s from %3$s', 'bp-group-documents'), 
          wp_kses_post( bp_core_get_userlink($bp->loggedin_user->id)), esc_html($document->name), 
            '<a href="' .  esc_url(bp_get_group_permalink($bp->groups->current_group)) . '">' . 
            esc_attr($bp->groups->current_group->name) . '</a>' 
        ),
        'component_action'  => 'deleted_group_document',
        'secondary_item_id' => $document->id,
        );    
    }
    bp_group_documents_record_activity($params);
    do_action('bp_group_documents_record_delete', $document);
}

add_action('bp_group_documents_delete_success', 'bp_group_documents_record_delete', 15, 1);

/**
 * bp_group_documents_record_activity()
 *
 * If the activity stream component is installed, this function will record upload
 * and edit activity items.
 *
 * @param  type $args
 * @return boolean
 * 
 * @version 2.0 17/2/2025, compatibility BP 12+
 */
function bp_group_documents_record_activity( $args = '' )
{
    $bp = buddypress();

    if (! function_exists('bp_activity_add') ) {
        return false;
    }
    $buddypress_version = bp_get_version(); // Get the BuddyPress version
    $compare_version = '12.0.0'; // The version to compare against
            // Compare the versions
    if (version_compare($buddypress_version, $compare_version, '>=') ) {
        $defaults = array(
        'primary_link'      => bp_get_group_url($bp->groups->current_group),
        'component_name'    => 'groups',
        'component_action'  => false,
        'hide_sitewide'     => false, // Optional
        'user_id'           => $bp->loggedin_user->id, // Optional
        'item_id'           => $bp->groups->current_group->id, // Optional
        'secondary_item_id' => false, // Optional
        'content'           => null, //Optional
        );
    } else {
        $defaults = array(
        'primary_link'      => bp_get_group_permalink($bp->groups->current_group),
        'component_name'    => 'groups',
        'component_action'  => false,
        'hide_sitewide'     => false, // Optional
        'user_id'           => $bp->loggedin_user->id, // Optional
        'item_id'           => $bp->groups->current_group->id, // Optional
        'secondary_item_id' => false, // Optional
        'content'           => null, //Optional
        );
    }
    $r = wp_parse_args($args, $defaults);
    

    // If the group is not public, don't broadcast updates.
    if ('public' !== $bp->groups->current_group->status ) {
        $r['hide_sitewide'] = 1;
    }

    return bp_activity_add(
        array(
        'content'           => $r['content'],
        'primary_link'      => $r['primary_link'],
        'component_name'    => $r['component_name'],
        'component_action'  => $r['component_action'],
        'user_id'           => $r['user_id'],
        'item_id'           => $r['item_id'],
        'secondary_item_id' => $r['secondary_item_id'],
        'hide_sitewide'     => $r['hide_sitewide'],
        'action'            => $r['action'],
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
function bp_group_documents_delete_activity_by_document( $document )
{
    $params = array(
    'item_id'           => $document->group_id,
    'secondary_item_id' => $document->id,
    );

    bp_group_documents_delete_activity($params);
    do_action('bp_group_documents_delete_activity_by_document', $document);
}

add_action('bp_group_documents_delete_success', 'bp_group_documents_delete_activity_by_document', 14, 1);
add_action('bp_group_documents_delete_with_group', 'bp_group_documents_delete_activity_by_document');

/**
 * bp_group_documents_delete_activity()
 *
 * Deletes a previously recorded activity - useful for making sure there are no broken links
 * if something is deleted.
 *
 * @param type $args
 */
function bp_group_documents_delete_activity( $args = true )
{
    if (function_exists('bp_activity_delete_by_item_id') ) {
        $defaults = array(
        'item_id'           => false,
        'component_name'    => 'groups',
        'component_action'  => false,
        'user_id'           => false,
        'secondary_item_id' => false,
        );

        $r = wp_parse_args($args, $defaults);
        
        bp_activity_delete_by_item_id(
            array(
            'item_id'           => $r['item_id'],
            'component_name'    => $r['component_name'],
            'component_action'  => $r['component_action'], // optional
            'user_id'           => $r['user_id'], // optional
            'secondary_item_id' => $r['secondary_item_id'], // optional
            )
        );
    }
}

/**
 *  Add BuddyPress Groups Documents activity types to the activity filter dropdown
 *
 * @since   0.4.3
 * @version 1.5, 4/12/2013, lenasterg, chanced name in order to avoid conficts with other plugins
 */
function bp_group_documents_activity_filter_options()
{
    $nav_page_name = get_option('bp_group_documents_nav_page_name');
    $name          = ! empty($nav_page_name) ? $nav_page_name : esc_html__('Documents', 'bp-group-documents');
    ?>
    <option value="added_group_document"><?php printf(
     // Translators: %s: Documents 
        esc_html__('Show New Group %s', 'bp-group-documents'), esc_html($name)
                                         ); ?></option>
    <option value="edited_group_document"><?php printf(
     // Translators: %s: Documents 
        esc_html__('Show Group %s Edits', 'bp-group-documents'), esc_html($name)
                                          ); ?></option>
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
    add_action($hook, 'bp_group_documents_activity_filter_options');
}

