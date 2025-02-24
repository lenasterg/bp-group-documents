<?php
/**
 * Plugin Name: BP Group Documents
 * Plugin URI: https://wordpress.org/plugins/bp-group-documents/
 * Description: BP Group Documents creates a page within each BuddyPress group to upload and any type of file or document.
 * Version: 2.0
 * Revision Date: February 17, 2025
 * Requires at least: 4.6
 * Tested up to: 6.7.2, BuddyPress 14.4.1
 * License: GNU General Public License v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: lenasterg
 * Text Domain: bp-group-documents
 * Domain Path: /languages/
 * Requires Plugins: buddypress
 * Network Only: true
 *
 * @todo minor, make a deregister function, 26/4/2013 stergatu
 */

// Exit if accessed directly
defined('ABSPATH') || exit;

/* Only load code that needs BuddyPress to run once BP is loaded and initialized. */

//some constants that can be checked when extending this plugin
define('BP_GROUP_DOCUMENTS_IS_INSTALLED', 1);
define('BP_GROUP_DOCUMENTS_VERSION', '2.0');
define('BP_GROUP_DOCUMENTS_DB_VERSION', '6');
define('BP_GROUP_DOCUMENTS_VALID_FILE_FORMATS', 'doc, docx, gif, gz, jpeg, jpg, ods, odt, pdf, png, pps, ppsx, ppt, pptx, rtf, tar, txt, xls, xlsx, zip');
define('BP_GROUP_DOCUMENTS_ITEMS_PER_PAGE', 20);
define('BP_GROUP_DOCUMENTS_UPLOAD_PERMISSION', 'mods_decide');
define('BP_GROUP_DOCUMENTS_DISPLAY_ICONS', 1);
define('BP_GROUP_DOCUMENTS_DISPLAY_OWNER', 1);
define('BP_GROUP_DOCUMENTS_DISPLAY_DATE', 1);
define('BP_GROUP_DOCUMENTS_USE_CATEGORIES', 1);
define('BP_GROUP_DOCUMENTS_ENABLE_ALL_GROUPS', true);
define('BP_GROUP_DOCUMENTS_PROGRESS_BAR', 1);
define('BP_GROUP_DOCUMENTS_FORUM_ATTACHMENTS', 0);


//allow override of URL slug
if (! defined('BP_GROUP_DOCUMENTS_SLUG') ) {
    define('BP_GROUP_DOCUMENTS_SLUG', 'documents');
}

/**
 * Nifty function to get the name of the directory bp_group_documents plugin is installed in.
 *
 * @author  Stergatu Eleni
 * @version 1, 6/3/2013
 * @since   0.5
 */
function bp_group_documents_dir()
{
    if (stristr(__FILE__, '/') ) {
        $bp_gr_dir = explode('/plugins/', dirname(__FILE__));
    } else {
        $bp_gr_dir = explode('\\plugins\\', dirname(__FILE__));
    }
    return str_replace('\\', '/', end($bp_gr_dir)); //takes care of MS slashes
}

$bp_gr_dir = bp_group_documents_dir();

define('BP_GROUP_DOCUMENTS_DIR', $bp_gr_dir); //the name of the directory that bp_group_documents  files are located.

/**
 * @author  Stergatu Eleni
 * @global  type $wpdb
 * @return  type
 * @since   0.5
 * @version 2.0 rename some file
 * 1.5 4/12/2013 added the bp_group_documents_load_textdomain() call
 */
function bp_group_documents_init()
{
    global $wpdb;
    if (is_multisite() && BP_ROOT_BLOG !== $wpdb->blogid ) {
        return;
    }
    if (! bp_is_active('groups') ) {
        return;
    }
    // Because our loader file uses BP_Component, it requires BP 1.6.5 or greater.
    if (version_compare(BP_VERSION, '1.6.5', '>') ) {
        bp_group_documents_set_constants();
        include dirname(__FILE__) . '/class-bp-group-documents-plugin-extension.php';
        bp_group_documents_include_files();
    }
    bp_group_documents_load_textdomain();
}

add_action('bp_loaded', 'bp_group_documents_init', 50);

/**
 * bp_group_documents_is_installed()
 * Checks to see if the DB tables exist or if we are running an old version
 * of the component. If the value has increased, it will run the installation function.
 *
 * @since   0.5
 * @version 1, 5/3/2013
 */
function bp_group_documents_is_installed()
{
    bp_group_documents_set_constants();
    if (get_site_option('bp-group-documents-db-version') < BP_GROUP_DOCUMENTS_DB_VERSION ) {
        bp_group_documents_install_upgrade();
    }
}

register_activation_hook(__FILE__, 'bp_group_documents_is_installed');

/**
 * bp_group_documents_install_upgrade()
 *
 * Installs and/or upgrades the database tables
 * This will only run if the database version constant is
 * greater than the stored database version value or no database version found
 *
 * @author  Stergatu Eleni <stergatu@cti.gr>
 * @version 1.0, 7/3/2013 now uses add_site_option instead of add_option
 * @since   0.5
 */
function bp_group_documents_install_upgrade()
{
    global $wpdb;

    $charset_collate = '';
    if (! empty($wpdb->charset) ) {
        $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
    }

    //If there is a previous version installed then move the variables to the sitemeta table
    if (( get_site_option('bp-group-documents-db-version') ) && ( get_site_option('bp-group-documents-db-version') < 5 ) ) {
        $sql[] = bp_group_documents_table_create($charset_collate);
    }
    if (! get_site_option('bp-group-documents-db-version') ) {
        $sql[] = bp_group_documents_table_create($charset_collate);
        add_option('bp_group_documents_nav_page_name', esc_html__('Documents', 'bp-group-documents'));
        add_option('bp_group_documents_valid_file_formats', BP_GROUP_DOCUMENTS_VALID_FILE_FORMATS);
        add_option('bp_group_documents_items_per_page', BP_GROUP_DOCUMENTS_ITEMS_PER_PAGE);
        add_option('bp_group_documents_upload_permission', BP_GROUP_DOCUMENTS_UPLOAD_PERMISSION);
        add_option('bp_group_documents_display_icons', BP_GROUP_DOCUMENTS_DISPLAY_ICONS);
        add_option('bp_group_documents_use_categories', BP_GROUP_DOCUMENTS_USE_CATEGORIES);
        add_option('bp_group_documents_enable_all_groups', BP_GROUP_DOCUMENTS_ENABLE_ALL_GROUPS);
        add_option('bp_group_documents_progress_bar', BP_GROUP_DOCUMENTS_PROGRESS_BAR);
        add_option('bp_group_documents_forum_attachments', BP_GROUP_DOCUMENTS_FORUM_ATTACHMENTS);
        add_option('bp-group-documents-db-version', BP_GROUP_DOCUMENTS_DB_VERSION);
    }
    if (( get_site_option('bp-group-documents-db-version') ) && ( get_site_option('bp-group-documents-db-version') < 6 ) ) {

        add_option('bp_group_documents_display_owner', BP_GROUP_DOCUMENTS_DISPLAY_OWNER);
        add_option('bp_group_documents_display_date', BP_GROUP_DOCUMENTS_DISPLAY_DATE);
    }
    update_site_option('bp-group-documents-db-version', BP_GROUP_DOCUMENTS_DB_VERSION);

    include_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta($sql);
}

/**
 * SQL create command for BP_GROUP_DOCUMENTS_TABLE
 *
 * @since   version 0.5
 * @author  stergatu
 * @version 1.0, 25/4/2013
 * @param   type $charset_collate
 * @return  string
 */
function bp_group_documents_table_create( $charset_collate )
{
    $to_sql = $sql[] = 'CREATE TABLE ' . BP_GROUP_DOCUMENTS_TABLE . " (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		user_id bigint(20) NOT NULL,
		  		group_id bigint(20) NOT NULL,
		  		created_ts int NOT NULL,
				modified_ts int NOT NULL,
				file VARCHAR(255) NOT NULL,
				name VARCHAR(255) NULL,
				description TEXT NULL,
				featured BOOL DEFAULT FALSE,
				download_count bigint(20) NOT NULL DEFAULT 0,
                                KEY user_id (user_id),
                                KEY group_id (group_id),
                                KEY created_ts (created_ts),
				KEY modified_ts (modified_ts),
				KEY download_count (download_count)
		 	   ) {$charset_collate};";
    return $to_sql;
}

/**
 * @author  Stergatu Eleni
 * @since   0.5
 * @version 1.2.2, remove constants related to admin uploads
 */
function bp_group_documents_set_constants()
{
    global $wpdb;
    if (! defined('BP_GROUP_DOCUMENTS_TABLE') ) {
        define('BP_GROUP_DOCUMENTS_TABLE', $wpdb->base_prefix . 'bp_group_documents');
    }

    //Widgets can be set to only show documents in certain (site-admin specified) groups
    if (! defined('BP_GROUP_DOCUMENTS_WIDGET_GROUP_FILTER') ) {
        define('BP_GROUP_DOCUMENTS_WIDGET_GROUP_FILTER', true);
    }

    //if enabled, documents can be flagged as "featured"
    //widget will have an option to only show featured docs
    if (! defined('BP_GROUP_DOCUMENTS_FEATURED') ) {
        define('BP_GROUP_DOCUMENTS_FEATURED', true);
    }

    //longer text descriptions to go with the documents can be toggled on or off.
    //this will toggle both the textarea input, and the display;
    if (! defined('BP_GROUP_DOCUMENTS_SHOW_DESCRIPTIONS') ) {
        define('BP_GROUP_DOCUMENTS_SHOW_DESCRIPTIONS', true);
    }

    //if enabled, and wp_editor exists, it will be used for the document comment editor
    if (! defined('BP_GROUP_DOCUMENTS_ALLOW_WP_EDITOR') ) {
        define('BP_GROUP_DOCUMENTS_ALLOW_WP_EDITOR', false);
    }

    switch ( substr(BP_VERSION, 0, 3) ) {
    case '1.1':
        define('BP_GROUP_DOCUMENTS_THEME_VERSION', '1.1');
        break;
    case '1.2':
    default:
        if ('BuddyPress Classic' === wp_get_theme() ) {
            define('BP_GROUP_DOCUMENTS_THEME_VERSION', '1.1');
        } else {
            define('BP_GROUP_DOCUMENTS_THEME_VERSION', '1.2');
        }
        break;
    }
    do_action('bp_group_documents_globals_loaded');
}

/**
 * @author  Stergatu Eleni
 * @since   1.5
 * @version 1, 4/12/2013
 */
function bp_group_documents_load_textdomain()
{
    $domain = 'bp-group-documents';
    // The "plugin_locale" filter is also used in load_plugin_textdomain()
    $locale = apply_filters('plugin_locale', get_locale(), $domain);
    load_textdomain($domain, WP_LANG_DIR . '/bp-group-documents/' . $domain . '-' . $locale . '.mo');

    if (file_exists(dirname(__FILE__) . '/languages/bp-group-documents-' . get_locale() . '.mo') ) {
        load_plugin_textdomain($domain, false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}


/**
 * @author  Stergatu Eleni
 * @since   0.5
 * @version 1.4, 11/11/2022, Changed a file name
 * 1.3, 25/10/2013 Makes sure the get_home_path function is defined before trying to use it
 * v1.2.2 remove admin-uploads.php file
 * v1, 5/3/2013
 */
function bp_group_documents_include_files()
{

    // Makes sure the get_home_path function is defined before trying to use it
    if (! function_exists('get_home_path') ) {
        include_once ABSPATH . '/wp-admin/includes/file.php';
    }
    include_once ABSPATH . 'wp-admin/includes/misc.php';
    include_once dirname(__FILE__) . '/include/bp_group_documents_functions.php';
    include_once dirname(__FILE__) . '/include/admin.php';
    include_once dirname(__FILE__) . '/include/class-bp-group-documents.php';
    include_once dirname(__FILE__) . '/include/cssjs.php';
    include_once dirname(__FILE__) . '/include/widgets.php';
    include_once dirname(__FILE__) . '/include/notifications.php';
    include_once dirname(__FILE__) . '/include/activity.php';
    include_once dirname(__FILE__) . '/include/class-bp-group-documents-template.php';
    include_once dirname(__FILE__) . '/include/filters.php';

    //only get the forum extension if it's been specified by the admin
    if (get_option('bp_group_documents_forum_attachments') ) {
        include_once dirname(__FILE__) . '/include/group-forum-attachments.php';
    }
}


/**
 * Functions for intergrating with Activity Plus Reloaded for BuddyPress plugin
*/

if (( is_main_site() ) && ( class_exists('BPAPR_Activity_Plus_Reloaded') ) ) {
    $bpapr_version = bpapr_activity_plus_reloaded()->version;
    if ('1.0.8' <= $bpapr_version ) {


        /**
         * Function for Activity Plus Reloaded for BuddyPress plugin
         *
         * @version 2.0
         *
         * @return boolean
         * @since  1.20
         */
        function ls_bpfb_documents_add_cssjs_hooks_handler()
        {
            $bp = buddypress();
            //    remove_action( 'bpfb_add_cssjs_hooks', array('BPAPR_Documents','add_cssjs_hooks_handler') );
            $document = new BP_Group_Documents();
            if (( ( isset($bp->groups->current_group->id) ) && ( $document->current_user_can('add') ) ) || ( ! $bp->groups->current_group ) ) {
                wp_enqueue_script('ls_bpfb_group_documents', plugin_dir_url(__FILE__) . '/js/ls-bpfb-group-documents.js', array( 'bp-activity-plus-reloaded' ));
                wp_localize_script(
                    'ls_bpfb_group_documents',
                    'l10nBpfbDocs',
                    array(
                    'add_documents'     => esc_html__('Add documents', 'bp-group-documents'),
                    'no_group_selected' => esc_html__('Please select a group to upload to', 'bp-group-documents'),
                    'group_id'          => bp_is_group() ? bp_get_current_group_id() : 0,
                    )
                );
            }
        }

        add_action('bpfb_add_cssjs_hooks', 'ls_bpfb_documents_add_cssjs_hooks_handler');

        /**
         * @version 2.0 17/2/2025, compatibility BP 12+
         */
        function ls_bpfd_goto_documents_add_page()
        {
            if (isset($_POST['data']) ) {
                  //    remove_action('wp_ajax_bpfb_documents_add_page',array('BPAPR_Documents','bpfd_goto_documents_add_page'));
        
                 // Unslash and sanitize the input
                 $group_id = sanitize_text_field(wp_unslash($_POST['data']));
    
                        $document = new BP_Group_Documents();
                        $group    = groups_get_group(array( 'group_id' => $group_id ));

                        $ret = $document->current_user_can('add', $group_id);

                        $buddypress_version = bp_get_version(); // Get the BuddyPress version
                        $compare_version = '12.0.0'; // The version to compare against
                        // Compare the versions
                if (version_compare($buddypress_version, $compare_version, '>=') ) {
                    $url     = bp_get_group_url($group) . '/' . BP_GROUP_DOCUMENTS_SLUG . '/add';
                } else {
                    $url     = bp_get_group_permalink($group) . '/' . BP_GROUP_DOCUMENTS_SLUG . '/add';
                }
        
                $warning = sprintf(
                // Translators: %s is the name of the group where the user lacks permission to upload files.
                    esc_html__("You don't have permission to upload files on group %s", 'bp-group-documents'), $group->name
                );

                        header('Content-type: application/json');
                        echo ( $ret ? json_encode(
                            array(
                                'url'  => $url,
                                'dfsd' => $document->current_user_can(
                                    'add',
                                    $group_id
                                ),
                            )
                        ) :
                        json_encode(
                            array(
                            'warning' => $warning,
                            )
                        ) );

                        exit();
            }
        }

        /**
         * Registers required AJAX handlers.
         */
        function ls_bpfb_documents_simple_add_ajax_hooks_handler()
        {
            //    remove_action('bpfb_add_ajax_hooks',array('BPAPR_Documents','add_ajax_hooks_handler'));
            add_action('wp_ajax_bpfb_documents_add_page', 'ls_bpfd_goto_documents_add_page');
        }

        add_action('bpfb_add_ajax_hooks', 'ls_bpfb_documents_simple_add_ajax_hooks_handler');


        function ls_bpfb_documents_code_before_save()
        {
            remove_action('bpfb_code_before_save', array( 'BPAPR_Documents', 'code_before_save_handler' ));
        }

        //add_action('bpfb_code_before_save','ls_bpfb_documents_code_before_save');

        function ls_bpfb_documents_create_core_defines()
        {
            remove_action('bpapr_loaded', array( 'BPAPR_Documents', 'create_core_defines' ));
        }

        //add_action('bpapr_loaded','ls_bpfb_documents_create_core_defines',10);
    }
}
