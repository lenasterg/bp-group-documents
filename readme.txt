===BP Group Documents  ===
Contributors: lenasterg, NTS on cti.gr, sch.gr
Tags:  buddypress, group documents, file, storage, widget
Requires at least: 4.6
Tested up to: 6.7.2
Stable tag: 2.0
License:           GNU General Public License v2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html


BP Group Documents creates a page within each BuddyPress group to upload and any type of file or document.

== Description ==
BP Group Documents creates a page within each BuddyPress group to upload and any type of file or document. This allows members of BuddyPress groups to upload and store files and documents that are relevant to the group.

Documents can be edited and deleted either by the document owner or by the group administrator.
Categories can be used to organize documents.
Activity is logged in the main activity stream, and is also tied to the user and group activity streams.
The site administrator can set filters on file extensions, set display options.
Group members and moderators can receive email notifications at their option.
The group administrator can decide if all members or only admins/moderators can upload documents (Since v0.5)
User verification for Downloads: when a document is downloaded, a redirect page checks is the user is member of the group (in case of a private  or hidden groups) and only then the user can download the file.(Since v0.5)
For private networks, see the FAQ "I have a members only network. How to disable file download for non members?" .

4 Widgets: "User's groups documents", "Recent Uploads" , "Popular Downloads", can be used to show activity at a glance. If the theme support different sidebars for group pages, the  BP_Group_Documents_CurrentGroup_Widget can be used to show current group's documents.

Contributions by Lena Stergatou, with additional bug fixes and improvements by Keeble Smith (http://keeblesmith.com) and Anton Andreasson work for BP 1.7.

Original plugin author was Peter Anselmo.

PLEASE: If you have any issues or it doesn't work for you, please report in support forum.  It doesn't help anyone to mark "broken" without asking around.  Thanks!



== Installation ==

Make sure Wordpress and BuddyPress are installed and active.

Copy the plugin folder buddypress-group-documents/ into /wp-content/plugins/

Browse to the plugin administration screen and activate the plugin.

There will now be a "Group Documents" menu item under the "Settings" menu.  Here you will find a list of all file extensions allowed for uploaded files along with other settings.

== Upgrade Notice ==
If you upgrade from older version you must also add a  your .htaccess in order
to ensure that requests to the old URLs are redirected to the new, hardened URL
That line is:
RewriteRule ^wp\-content/blogs\.dir/1/files/group\-documents/(.*) /?get_group_doc=$1 [R,L]


== Frequently Asked Questions ==
= I get mb_convert_case error =
If you run a windows server and you get errors about mb_convert_case  function which is a default php function (see http://php.net/manual/en/function.mb-convert-case.php), you must uncomment the line with php_mbstring.dll in your php.ini.
= Can I link to the add file form =
 If you are a plugin developer and want to use the upload file form you can link to /group_slug/bpgroupdocuments_slug/add to access the upload document form
= I have a members only network. How to disable file download for non members? =
Add the following function into your /wp-content/wp-plugins/bp-custom.php file
`
/*
 * Download file only in the user is logged in
 */

function bp_only_logged_in_can_download( $error ) {
// If we have a only logged-in users site
    if ( ! is_user_logged_in() ) {
	$error = array(
	    'message' => esc_html__( 'You must log in to access the page you requested.', 'bp-group-documents' ),
	    'redirect' => bp_root_url()
	);
    }
    return $error;
}

add_filter( 'bp_group_documents_download_access', 'bp_only_logged_in_can_download' );
`
= I want to hide the files icon from the tab =
Add to your Customizer 'Custom CSS' panel the following:
`li#nav-documents-groups-li, li#nav-documents-personal-li a {
    background: none;
}
`


== Screenshots ==
1. Admin settings page
2. Documents settings page on group creation
3. Upload document form
4. Document list tab
5. Ties into site activity stream (for public groups only)
6. Group admin document's settings tab, allow category's edit, addition
7. User options for email notifications
8. Widget Recent Documents from your groups  and Widget Popular Group Documents
9. Message when non member of a private or hidden group tries to access a group document


== Changelog ==
= Version 2.0 (21 February 2025) =
* BuddyPress 12+ compatible, tested up to BuddyPress 14.3.3
* Fix bugs related to category edit and delete
* Translators comments added
* Delete old deprecated functions


= Version 1.25 (1 December 2023) =
* Accessibility fix: Add alt attribute in widgets icons

= Version 1.24 (31 August 2023) =
* Fix a bug in the widgets

= Version 1.23 (31 August 2023) =
* Fix some PHP 8.1+ warnings and speed improvements


= Version 1.22 (28 July 2023) =
* Fix for valid for file extensions, props @boonebgorges 


= Version 1.21 (11 May 2023) =
* New settings options added for 'Display File Owner', 'Display File Date'
* Tested with WordPress 6.2.0 and BuddyPress 11.0

= Version 1.20.1 (3/02/2023) =
* Fix a PHP warning when in a subsite

= Version 1.20 (27/01/2023) =
* New functionality: If Activity Plus Reloaded for BuddyPress plugin is installed, it adds an icon for documents upload next to 'Activity Plus Reloaded for BuddyPress plugin' icons.

= Version 1.14 (11/11/2022) =
* Tested with WordPress 6.1 and BuddyPress 10.6.0
* Code standards fixes
* Changed some strings
* Various bug fixes



= Version 1.13 (3/10/2022) =
* Compatible with PHP 8.x
* Tested with WordPress 6.0.2 and BuddyPress 10.4.0

= Version 1.12.3 (9/4/2019) =
* Fix some PHP 7.x warnings
* Fix BuddyPress warning, thanks to @venutius 
* Fix a css issue reported in wordpress.org support forum

= Version 1.12.2 (29/06/2018) =
* Replace constant WP_PLUGIN_URL with plugins_url() 
* Replace constant WP_CONTENT_URL with content_url()
* Fix some deprecated warnings

= Version 1.12.1 (6/10/2017) =
* Typo fix for template folder

= Version 1.12 (2/10/2017) =
* Allow main plugin markup to be overridden with theme template. Props @boonebgorges

= Version 1.11 (29/3/2017) =
* Security fixes
* Introduce new action: 'bp_group_documents_data_failed_save'

= Version 1.10 (24/3/2017) =
* Fix bug caused from 1.9.7 version
* Security fixes

= Version 1.9.7 (15/3/2017) =
* New feature: Display an "Add new" button on top of the documents list when the documents are more than 4.
* Fix bug on download docx, xlsx
* Fix bug on delete document's category

= Version 1.9.6 (15/3/2017) =
* Fix typos in group documents admin page

= Version 1.9.5 (14/3/2017) =
* Fix some error notices in widgets

= Version 1.9.4 (5/6/2015) =
* Fix for widgets, pros @thebrandonallen
* Add icon for ppsx
* Fix for Strict Standards setup

= Version 1.9.3.1 (21/4/2015) =
* Minor fix

= Version 1.9.3 (6/4/2015) =
* Fix BP_Group_Documents_CurrentGroup_Widget for hidden groups
* Updated Italian language file, thanks to Daniele Mezzetti

= Version 1.9.2 (9/3/2015) =
* Fix download count for non-login users
* Fix warning caused by setcookie
* Fix a typo
* Add ods as default valid file extension

= Version 1.9.1 (16/1/2015) =
* Add link for "Add new document" on BP_Group_Documents_CurrentGroup_Widget
* Add new filter bp_group_documents_download_access. Thanks to @kallekillen for the idea.

= Version 1.9 (8/12/2014) =
* Category link added in documents list.
* Escaping fix. Strip slashes on the way out, so that file titles and descriptions don't have so many unnecessary backslashes. Thanks to @jreeve for patch.


= Version 1.8 (September 1, 2014) =
* Fix Sort - "Order by" & Filter - "Category" which was not working with Pagination. Thanks to @wp4yd for reporting.

= Version 1.7 (April 22, 2014) =
* Add new widget: BP_Group_Documents_CurrentGroup_Widget. If the theme support different sidebars for group pages, it can be used to show current group's documents.
* Fix some minor issues in widgets
* Fix Document upload notification bug, props to @jreeve

= Version 1.6 (March 17, 2014) =
* Language files update

= Version 1.5 (December 4, 2013) =
* New feature: Into the Administration screen of the Activity component, the admin can filter activity for New group files and Edited group files. Based on @imath 's   http://codex.buddypress.org/plugindev/add-custom-filters-to-loops-and-enjoy-them-within-your-plugin/
* Now supports custom tranlation files placed into WP_LANG_DIR . '/bp-group-documents/' . $domain . '-' . $locale . '.mo')


= Version 1.4 (October 31, 2013) =
* Fix a bug which marked some themes as Broken in some installations.

= Version 1.3 (October 25, 2013) =
* Fix a bug which causes Fatal error about the get_home_path() function in some installations.


= Version 1.2.3 (October 18, 2013) =
* Fix a bug on editing categories when the group slug is not the default "groups. Thanks to @jomsky for reporting and patching it.
* Dutch translation, thanks to @sanderbontje

= Version 1.2.2 (October 4, 2013) =
* MAJOR security bug fixes. (Thanks to @tomdxw for reporting and patch them)
* Update bp-group-documents.pot file
* Fix super admin's rights for all group documents
* Fix wrong placed error messagew
* Remove depreceted functions
* Remove administrators FTP ability, in favor of security



= Version 1.2.1 (September 17, 2013) =
* Bug fix: https://wordpress.org/support/topic/bugfix-for-broken-icon-link, thanks to @sanderbontje
* Bug fix: https://wordpress.org/support/topic/error-message-if-you-edit-groups thanks to @valuser for reporting
* Bug fix: https://wordpress.org/support/topic/widget-functionality thanks to @kcurlsjr for reporting

= Version 1.2 (September 3,2013) =
* Added Swedish translation. Thanks goes to nat0n (https://wordpress.org/support/profile/nat0n)

= Version 1.1 (September 3,2013) =
* Fix some broken links by changing the plugin directory name with BP_GROUP_DOCUMENTS_DIR constant

= Version 1.0 (August 28,2013) =
* Update readme.txt
* Add screenshots

See history.txt for older version changelog
Apologies for the frequent updates, this plugin is under active development!

== Notes ==

Roadmap.txt - contains ideas proposed and the (approximate) order of implementation

History.txt - contains all the changes since version .1

License.txt - contains the licensing details for this component.