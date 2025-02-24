<?php
/**
 * 
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * @author lenasterg
 * @since  0.5
 */
if (class_exists('BP_Group_Extension')) : // Recommended, to prevent problems during upgrade or when Groups are disabled
    class BP_Group_Documents_Plugin_Extension extends BP_Group_Extension
    {
        var $visibility = 'private';
        var $format_notification_function;
        var $enable_edit_item = true;
        var $admin_metabox_context = 'side'; // The context of your admin metabox. See add_meta_box()
        var $admin_metabox_priority = 'default'; // The priority of your admin metabox. See add_meta_box()

        public function __construct()
        {
            $bp = buddypress();

            $nav_page_name = get_option('bp_group_documents_nav_page_name');

            $this->name = !empty($nav_page_name) ? $nav_page_name : esc_html__('Documents', 'bp-group-documents');
            $this->slug = BP_GROUP_DOCUMENTS_SLUG;

            /* For internal identification */
            $this->id = 'group_documents';
            $this->format_notification_function = 'bp_group_documents_format_notifications';

            if ($bp->groups->current_group) {
                $this->nav_item_name = esc_html($this->name) . ' <span>' . BP_Group_Documents::get_total($bp->groups->current_group->id) . '</span>';
                $this->nav_item_position = 51;
            }

            $this->admin_name = !empty($nav_page_name) ? $nav_page_name : esc_html__('Documents', 'bp-group-documents');
            $this->admin_slug = BP_GROUP_DOCUMENTS_SLUG;

            if ('mods_decide' !== get_option('bp_group_documents_upload_permission')) {
                $this->enable_create_step = false;
            } else {
                $this->create_step_position = 21;
            }
        }

        /**
         * The content of the BP group documents tab of the group creation process
         *
         * @param  int $group_id
         * @return boolean
         */
        function create_screen($group_id = null)
        {
            $bp = buddypress();
            if (!bp_is_group_creation_step($this->slug)) {
                return false;
            }
            $this->edit_create_markup($bp->groups->new_group_id);
            wp_nonce_field('groups_create_save_' . $this->slug);
        }

        /**
         * The routine run after the user clicks Continue from the creation step
         *
         * @version 1, 29/4/2013, lenasterg
         * @since   0.5
         */
        function create_screen_save($group_id = null)
        {
            $bp = buddypress();

            check_admin_referer('groups_create_save_' . $this->slug);

            do_action('bp_group_documents_group_create_save');
            $success = false;

            //Update permissions
            $valid_permissions = array('members', 'mods_only');
            if (isset($_POST['bp_group_documents_upload_permission']) && in_array($_POST['bp_group_documents_upload_permission'], $valid_permissions)) {
                $upload_permission = sanitize_text_field(wp_unslash($_POST['bp_group_documents_upload_permission']));
                $success = groups_update_groupmeta($bp->groups->new_group_id, 'bp_group_documents_upload_permission', $upload_permission);
            }

            // Show success or error message
            if (!$success) {
                bp_core_add_message(esc_html__('There was an error saving, please try again', 'bp-group-documents'), 'error');
            } else {
                bp_core_add_message(esc_html__('Settings Saved.', 'bp-group-documents'));
            }
            do_action('bp_group_documents_group_after_create_save');
        }

        /**
         * The content of the Group Documents page of the group admin
         *
         * @since   0.5
         * @version 4 18/10/2013, fix the $action_link
         * v3, 21/5/2013, fix the edit category
         */
        function edit_screen($group_id = null)
        {
            $bp = buddypress();
            if (!bp_is_group_admin_screen($this->slug)) {
                return false;
            }
            //useful ur for submits & links
            $action_link = esc_url(get_bloginfo('url') . '/' . bp_get_groups_root_slug() . '/' . $bp->current_item . '/' . $bp->current_action . '/' . $this->slug);
            $this->edit_create_markup($bp->groups->current_group->id);
            //only show categories if site admin chooses to
            if (get_option('bp_group_documents_use_categories')) {
                $parent_id = BP_Group_Documents_Template::get_parent_category_id();
                $group_categories = get_terms(
                    array(
                    'taxonomy'   => 'group-documents-category',
                    'parent'   => $parent_id, 
                    'hide_empty' => false,
                    )
                );
                ?>
                <!-- #group-documents-group-admin-categories -->
                <div id="group-documents-group-admin-categories">
                    <label>
                        <?php
                        esc_html_e('Category List for', 'bp-group-documents');
                        echo ' ' . esc_html($this->name);                        ?>
                        :</label>
                    <div>
                        <ul>
                            <?php
                
                            foreach ($group_categories as $category) {     
                                if (!empty($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT) !== false && (int) $_GET['edit'] === (int) $category->term_id) {
                                    ?>
                                    <li id="category-<?php echo esc_attr($category->term_id); ?>">
                    <input type="text" name="group_documents_category_edit" value="<?php echo esc_attr($category->name); ?>" />
                                        <input type="hidden" name="group_documents_category_edit_id" value="<?php echo esc_attr($category->term_id); ?>" />
                                        <input type="submit" id="editCat" name="editCat" class="button" value="<?php esc_attr_e('Update', 'bp-group-documents'); ?>" />
                                    </li>
                                    <?php
                 
                                } elseif (!empty($_GET['delete']) && filter_var($_GET['delete'], FILTER_VALIDATE_INT) !== false && (int) $_GET['delete'] === (int) $category->term_id) {
                                    ?>
                                    <div class="bp_group_documents_question">
                                    <?php
                                    printf(
                                        wp_kses(
                                        /* translators: %s: category name */
                                            __('Are you sure you want to delete category <strong>%s</strong>?', 'bp-group-documents'),
                                            ['strong' => []] // Allow <strong> tag
                                        ),
                                        esc_html($category->name)
                                    );
                                    ?>
                                        <br/>
                                        <?php
                                        /* translators: %s: document name */
                                        printf(esc_html__('Any %s in the category will be left with no category.', 'bp-group-documents'), esc_html(mb_strtolower($this->name)));
                                        ?>
                                        <br/>
                                        <?php esc_html_e('You can later assign them to another  category.', 'bp-group-documents');
                                        ?>
                                        <input type="hidden" name="group_documents_category_del_id" value="<?php echo esc_attr($category->term_id); ?>" />
                                        <input type="submit" value="<?php esc_attr_e('Delete', 'bp-group-documents'); ?>" id="delCat" name="delCat"/>
                                    </div>
                                                                    <?php
                                } else {
                                    $edit_link = wp_nonce_url($action_link . '?edit=' . $category->term_id, 'group_documents_category_edit');
                                    $delete_link = wp_nonce_url($action_link . '?delete=' . $category->term_id, 'group_documents_category_delete');
                                    ?>
                                    <li id="category-<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?>
                                        <div>&nbsp;
                        <a class="group-documents-category-edit button" href="<?php echo esc_url($edit_link); ?>"><?php esc_html_e('Edit', 'bp-group-documents'); ?></a>
                                            <a class="group-documents-category-delete button" href="<?php echo esc_url($delete_link); ?>"><?php esc_html_e('Delete', 'bp-group-documents'); ?></a></div>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                        <input type="text" name="bp_group_documents_new_category" class="bp-group-documents-new-category" />
                    </div>
                </div><!-- end #group-documents-group-admin-categories -->
                <?php
            }
            do_action('bp_group_documents_group_admin_edit');
            ?>
            &nbsp;<p>
                <input type="submit" value="<?php esc_attr_e('Save Changes', 'bp-group-documents'); ?> &rarr;" id="save" name="save" />
                <input type="hidden" name="delCat" value="" />
            </p>
            <?php
            wp_nonce_field('groups_edit_save_' . $this->slug);
        }

        /**
         * Markup for upload permissions and category management
         *
         * @param int $gid group id
         */
        function edit_create_markup($gid)
        {
            //only show the upload persmissions if the site admin allows this to be changed at group-level
            ?>
            <div><label><?php esc_html_e('Upload Permissions:', 'bp-group-documents'); ?></label></div>
            <p>
                <?php
                $netadmin_decision = get_option('bp_group_documents_upload_permission');
                switch ($netadmin_decision) {
                case 'mods_decide':
                    $upload_permission = groups_get_groupmeta($gid, 'bp_group_documents_upload_permission');
                    ?>
                        <input type="radio" name="bp_group_documents_upload_permission" value="members"
                        <?php
                        if ('members' === $upload_permission) {
                            echo 'checked="checked"';
                        }
                        ?>
                        />
                        <?php esc_html_e('All Group Members', 'bp-group-documents'); ?><br />
                        <input type="radio" name="bp_group_documents_upload_permission" value="mods_only"
                            <?php
                            if (!('members' === $upload_permission)) {
                                echo 'checked="checked"';
                            }
                            ?>
                        />
                        <?php
                        esc_html_e("Only Group's Administrators and Moderators", 'bp-group-documents');
                    break;
                case 'members':
                     esc_html_e('All Group Members', 'bp-group-documents');
                    break;
                case 'mods_only':
                default:
                    esc_html_e("Only Group's Administrators and Moderators", 'bp-group-documents');
                    break;
                }
                ?>
            </p>
            <?php
        }

        /**
         * The routine run after the user clicks Save from your admin tab
         *
         * @version 2.0 17/2/2025, compatibility BP 12+
         *  v1.4, 31/10/2013, fix some notices
         * v3,  27/8/2013, fix the messages
         * v2, 21/5/2013, fix the edit and delete category bug, lenasterg
         * @since   0.5
         */
        function edit_screen_save($group_id = null)
        {
            $bp = buddypress();
            do_action('bp_group_documents_group_admin_save');
            $message = '';
            $type = '';

            $parent_id = BP_Group_Documents_Template::get_parent_category_id();
            if ((!isset($_POST['save'])) && (!isset($_POST['addCat'])) && (!isset($_POST['editCat'])) && (!isset($_POST['delCat']))) {
                return false;
            }

            check_admin_referer('groups_edit_save_' . $this->slug);
            //check if category was deleted
            if (isset($_POST['group_documents_category_del_id'])
                && ctype_digit($_POST['group_documents_category_del_id'])
                && term_exists((int) $_POST['group_documents_category_del_id'], 'group-documents-category')
            ) {
                $term_id = (int) $_POST['group_documents_category_del_id']; // Sanitize input
                if (true === wp_delete_term($term_id, 'group-documents-category')) {
                    // Translators: %s is replaced with the lowercase name of the group (e.g., "group1").
                    $message = sprintf(esc_html__('Group %s category deleted successfully', 'bp-group-documents'), esc_html(mb_strtolower($this->name)));
                }
            }
            //check if category was updatedsuccessfully
            elseif ((array_key_exists('group_documents_category_edit', $_POST)) && (ctype_digit($_POST['group_documents_category_edit_id'])) && (term_exists((int) $_POST['group_documents_category_edit_id'], 'group-documents-category'))) {
                // Sanitize inputs
                $term_id = (int) $_POST['group_documents_category_edit_id'];
                $term_name = sanitize_text_field(wp_unslash($_POST['group_documents_category_edit']));

                if (term_exists($term_name, 'group-documents-category', $parent_id)) {
                    // Translators: %s is replaced with the lowercase name of the group (e.g., "group1").
                    $message = sprintf(esc_html__('No changes were made. This %s category name is used already', 'bp-group-documents'), esc_html(mb_strtolower($this->name)));
                    $type = 'error';
                } else {
                    // Update the term
                    $updated_term = wp_update_term(
                        $term_id,
                        'group-documents-category',
                        array('name' => $term_name)
                    );
                    if (!is_wp_error($updated_term)) {
                        // Translators: %s is replaced with the lowercase name of the group (e.g., "group1").
                        $message = sprintf(esc_html__('Group %s category renamed successfully', 'bp-group-documents'), esc_html(mb_strtolower($this->name)));
                    }
                    else {
                           // Handle update failure
                           $message = esc_html__('Failed to rename the category.', 'bp-group-documents');
                           $type = 'error';
                    }
                }
            }    
            // Check if new category was added, if so, append to current list
            elseif (!empty($_POST['bp_group_documents_new_category'])) {
                $new_category = sanitize_text_field(wp_unslash($_POST['bp_group_documents_new_category']));
                if (!term_exists($new_category, 'group-documents-category', $parent_id)) {
                    $result = wp_insert_term($new_category, 'group-documents-category', array('parent' => $parent_id));
                    if (is_wp_error($result)) {
                                $message = sprintf(
                            // Translators: %s is replaced with the lowercase name of the group (e.g., "group1").
                                    esc_html__('No changes were made. This %s category name is used already', 'bp-group-documents'), 
                                    esc_html(mb_strtolower($this->name)) // Escape the dynamic group name.
                                );
                                            $type = 'error';
                    }
                    else {
                        $message = esc_html($new_category) . ': ' . sprintf(
                        // Translators: %s is replaced with the lowercase name of the group (e.g., "group1").
                            esc_html__('New group %s category created', 'bp-group-documents'), 
                            esc_html(mb_strtolower($this->name))
                        );
                    }
                }
            }

            $valid_permissions = array('members', 'mods_only');
            //check if group upload permision has chanced
            if (isset($_POST['bp_group_documents_upload_permission']) && in_array($_POST['bp_group_documents_upload_permission'], $valid_permissions)) {
                if (true === groups_update_groupmeta($bp->groups->current_group->id, 'bp_group_documents_upload_permission', $_POST['bp_group_documents_upload_permission'])) {
                    if ('' !== $message) {
                        $message .= '.     ';
                    }
                    $message .= esc_html__('Upload Permissions changed successfully', 'bp-group-documents') . '.';
                }
            }

            /* Post an error/success message to the screen */

            if ('' === $message) {
                bp_core_add_message(esc_html__('No changes were made. Either error or you didn\'t change anything', 'bp-group-documents'), 'error');
            } else {
                bp_core_add_message($message, $type);
            }

            do_action('bp_group_documents_group_admin_after_save');

            $buddypress_version = bp_get_version(); // Get the BuddyPress version
            $compare_version = '12.0.0'; // The version to compare against

            if (version_compare($buddypress_version, $compare_version, '>=')) {
                bp_core_redirect(esc_url(bp_get_group_url($bp->groups->current_group) . 'admin/' . $this->slug));
            } else {
                bp_core_redirect(esc_url(bp_get_group_permalink($bp->groups->current_group) . 'admin/' . $this->slug));
            }
        }
    
        /**
         * @version 1, 25/4/2013
         * @since   version 0.5
         * @author  Stergatu
         */
        function display( $group_id = null )
        {
            do_action('bp_group_documents_display');
            add_action('bp_template_content_header', 'bp_group_documents_display_header');
            add_action('bp_template_title', 'bp_group_documents_display_title');
            bp_group_documents_display();
        }

        /**
         * Add a metabox to the admin Edit group screen
         *
         * @since 0.5
         */
        function admin_screen( $group_id = null )
        {
            $this->edit_create_markup($group_id);
        }

        /**
         * The routine run after the group is saved on the Dashboard group admin screen
         *
         * @param type $group_id
         */
        function admin_screen_save( $group_id = null )
        {
    
    
            // Grab your data out of the $_POST global and save as necessary
            //Update permissions
            $valid_permissions = array( 'members', 'mods_only' );
            if (isset($_POST['bp_group_documents_upload_permission']) ) {
                // Remove slashes first, then sanitize
                $new_upload_permission = sanitize_text_field(wp_unslash($_POST['bp_group_documents_upload_permission']));
    
                if (in_array($new_upload_permission, $valid_permissions) ) {
                    $previous_upload_permission = groups_get_groupmeta($group_id, 'bp_group_documents_upload_permission');
        
                    if ($new_upload_permission!== $previous_upload_permission ) {
                        // Update group meta with sanitized value
                        groups_update_groupmeta($group_id, 'bp_group_documents_upload_permission', $new_upload_permission);
                    }
                }
            }
        }

        /**
         * @todo
         */
        function widget_display()
        {

        }

    }
    bp_register_group_extension('BP_Group_Documents_Plugin_Extension');
endif; // class_exists( 'BP_Group_Documents_Extension' )
