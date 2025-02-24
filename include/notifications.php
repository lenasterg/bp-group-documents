<?php
// Exit if accessed directly
if (! defined('ABSPATH') ) {
    exit;
}

/**
 * bp_group_documents_screen_notification_settings()
 *
 * Adds notification settings for the component, so that a user can turn off email
 * notifications set on specific component actions.  These will be added to the
 * bottom of the existing "Group" settings
 *
 * @version 3, Code standards
 *  2, 21/6/2013, stergatu, fix a bug which prevented the notifications setting to be saved
 */
function bp_group_documents_screen_notification_settings()
{

    if (! $notification_group_documents_upload_member = bp_get_user_meta(bp_displayed_user_id(), 'notification_group_documents_upload_member', true) ) {
        $notification_group_documents_upload_member = 'yes';
    }
    if (! $notification_group_documents_upload_mod = bp_get_user_meta(bp_displayed_user_id(), 'notification_group_documents_upload_mod', true) ) {
        $notification_group_documents_upload_mod = 'yes';
    }
    ?>
    <tr id="groups-notification-settings-user-upload-file">
        <td></td>
        <td><?php esc_html_e('A member uploads a document to a group you belong to', 'bp-group-documents'); ?></td>
        <td class="yes"><input type="radio" name="notifications[notification_group_documents_upload_member]" value="yes" <?php checked($notification_group_documents_upload_member, 'yes', true); ?>/></td>
        <td class="no"><input type="radio" name="notifications[notification_group_documents_upload_member]" value="no" <?php checked($notification_group_documents_upload_member, 'no', true); ?>/></td>
    </tr>
    <tr>
        <td></td>
        <td><?php esc_html_e('A member uploads a document to a group for which you are an moderator/admin', 'bp-group-documents'); ?></td>
        <td class="yes"><input type="radio" name="notifications[notification_group_documents_upload_mod]" value="yes" <?php checked($notification_group_documents_upload_mod, 'yes', true); ?>/></td>
        <td class="no"><input type="radio" name="notifications[notification_group_documents_upload_mod]" value="no" <?php checked($notification_group_documents_upload_mod, 'no', true); ?>/></td>
    </tr>

    <?php do_action('bp_group_documents_notification_settings'); ?>
    <?php
}

add_action('groups_screen_notification_settings', 'bp_group_documents_screen_notification_settings');

/**
 * bp_group_documents_email_notificiation()
 *
 * This function will send email notifications to users on successful document upload.
 * For each group member, it will check to see the users notification settings first,
 * if the user has the notifications turned on, they will be sent a formatted email notification.
 *
 * @version 3.0, 17/2/2025, compatibility BP 12+
 * 2, include @jreeve fix http://wordpress.org/support/topic/document-upload-notification?replies=6#post-5464069
 */
function bp_group_documents_email_notification( $document )
{
    $bp = buddypress();
    $buddypress_version = bp_get_version(); // Get the BuddyPress version
    $compare_version = '12.0.0'; // The version to compare against

    if (version_compare($buddypress_version, $compare_version, '>=') ) {
        $group_link        = bp_get_group_url($bp->groups->current_group);
    } else {
        $group_link        = bp_get_group_permalink($bp->groups->current_group);
    }
    
    $user_name         = bp_core_get_userlink($bp->loggedin_user->id, true);
    $user_profile_link = bp_core_get_userlink($bp->loggedin_user->id, false, true);
    $document_name     = $document->name;
    $document_link     = $document->get_url();
    $group_name        = $bp->groups->current_group->name;
        
   
    
    $subject = '[' . get_blog_option(1, 'blogname') . '] ' . 
    sprintf(
    // Translators: %s will be replaced with the name of the group where the document was uploaded.
        esc_html__('A document was uploaded to %s', 'bp-group-documents'), $bp->groups->current_group->name
    );

    //these will be all the emails getting the update
    //'user_id' => 'user_email
    $emails = array();

    
    //first get the admin & moderator emails
    if (count($bp->groups->current_group->admins) ) {
        foreach ( $bp->groups->current_group->admins as $user ) {
            $mod_notif_prefs = get_user_meta($user->user_id, 'notification_group_documents_upload_mod');
            if (in_array('no', $mod_notif_prefs) ) {
                continue;
            }
            $emails[ $user->user_id ] = $user->user_email;
        }
    }
    if (count($bp->groups->current_group->mods) ) {
        foreach ( $bp->groups->current_group->mods as $user ) {
            $mod_notif_prefs = get_user_meta($user->user_id, 'notification_group_documents_upload_mod');
            if (in_array('no', $mod_notif_prefs) ) {
                continue;
            }
            if (! in_array($user->user_email, $emails) ) {
                $emails[ $user->user_id ] = $user->user_email;
            }
        }
    }

    //now get all member emails, checking to make sure not to send any emails twice
    $user_ids = BP_Groups_Member::get_group_member_ids($bp->groups->current_group->id);
    foreach ( (array) $user_ids as $user_id ) {
        $member_notif_prefs = get_user_meta($user_id, 'notification_group_documents_upload_member');
        if (in_array('no', $member_notif_prefs) ) {
            continue;
        }
        $ud = bp_core_get_core_userdata($user_id);
        if (! in_array($ud->user_email, $emails) ) {
            $emails[ $user_id ] = $ud->user_email;
        }
    }
    

    foreach ( $emails as $current_id => $current_email ) {
        $message = sprintf(
        // Translators:  %1$s - The name of the user who uploaded the file.  %2$s - The name of the uploaded file.  %3$s - The name of the group where the file was uploaded. %4$s - The name of the user (repeated for profile link). %5$s - The URL to the user's profile. %6$s - The name of the group (repeated for group homepage link). %7$s - The URL to the group's homepage. %8$s - The direct download link for the uploaded file.
            esc_html__(
                '%1$s uploaded a new file: %2$s to the group: %3$s.
To see %4$s\'s profile: %5$s
To see the group %6$s\'s homepage: %7$s
To download the new document directly: %8$s
',
                'bp-group-documents'
            ),
            esc_html($user_name),
            esc_html($document_name),
            esc_html($group_name),
            esc_html($user_name),
            esc_url($user_profile_link),
            esc_html($group_name),
            esc_url($group_link),
            esc_html($document_link)
        );

        // Compare the versions
        if (version_compare($buddypress_version, $compare_version, '>=') ) {
             $settings_link = bp_members_get_user_url($current_id) . $bp->settings->slug . '/notifications/';
        } else {
             $settings_link = bp_core_get_user_domain($current_id) . $bp->settings->slug . '/notifications/';
        }
    
        $message.= sprintf(
        // Translators: %s is a URL where users can modify notification settings.
            esc_html__('To disable these notifications please log in and go to: %s', 'bp-group-documents'), esc_url($settings_link)
        );

        // Set up and send the message
        $to = $current_email;
        wp_mail($to, $subject, $message);
        unset($to, $message);
    } //end foreach
}

add_action('bp_group_documents_add_success', 'bp_group_documents_email_notification', 10);
