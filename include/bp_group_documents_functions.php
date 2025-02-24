<?php
// Exit if accessed directly
if (! defined('ABSPATH') ) {
    exit;
}

/**
 * @since version 0.5
 * contains functions previous on index.php
 */

/**
 * Registers the plugin's template directory.
 *
 * @since 1.12
 */
function bp_group_documents_register_template_stack()
{
    bp_register_template_stack('bp_group_documents_template_directory', 20);
}
add_action('bp_actions', 'bp_group_documents_register_template_stack', 0);

/**
 * Returns the directory containing the default templates for the plugin.
 *
 * @since 1.12
 *
 * @return string
 */
function bp_group_documents_template_directory()
{
    return WP_PLUGIN_DIR . '/' . BP_GROUP_DOCUMENTS_DIR . '/templates';
}

/**
 * bp_group_documents_display()
 *
 * Loads the template part for the primary group display.
 *
 * version 2.0 7/3/2013 lenasterg
 */
function bp_group_documents_display()
{
    bp_get_template_part('groups/single/documents');
}

/**
 *
 * @version 2.0, 13/5/2013, lenasterg
 */
function bp_group_documents_display_header()
{
    $nav_page_name = get_option('bp_group_documents_nav_page_name');

    $name = ! empty($nav_page_name) ? $nav_page_name : esc_html__('Documents', 'bp-group-documents');
    esc_html_e('Group', 'bp-group-documents') . ' ' . $name;
}

/**
 *
 */
function bp_group_documents_display_title()
{
    echo esc_html(get_option('bp_group_documents_nav_page_name')) . ' ' . esc_html__('List', 'bp-group-documents');
}

/*     * ***********************************************************************
 * **********************EVERYTHING ELSE************************************
 * *********************************************************************** */

/**
 * bp_group_documents_delete()
 *
 * After performing several validation checks, deletes both the uploaded
 * file and the reference in the database
 *
 * @param  int $id Document ID
 * @return bool True if deletion was successful, false otherwise
 */
function bp_group_documents_delete( $id )
{
    // Check nonce to ensure the request is valid and not coming from an external source
    if (! isset($_REQUEST['_wpnonce']) || ! wp_verify_nonce($_REQUEST['_wpnonce'], 'group-documents-delete-link') ) {
        bp_core_add_message(esc_html__('There was a security problem', 'bp-group-documents'), 'error');
        return false;
    }

    // Ensure the ID is a valid positive integer
    if (! ctype_digit($id) ) {
        bp_core_add_message(esc_html__('The item to delete could not be found', 'bp-group-documents'), 'error');
        return false;
    }

    // Get the document object
    $document = new BP_Group_Documents($id);

    // Check if the current user has permission to delete the document
    if (! $document->current_user_can('delete') ) {
        bp_core_add_message(esc_html__('You do not have permission to delete this document.', 'bp-group-documents'), 'error');
        return false;
    }

    // Attempt to delete the document
    if ($document->delete() ) {
        // Delete the actual file from the server (if it's not handled in the delete method)
        if (! empty($document->file_path) && file_exists($document->file_path) ) {
            //@unlink( $document->file_path ); // Silently fail if the file can't be deleted
            wp_delete_file($document->file_path); // Silently fail if the file can't be deleted
        }

        // Fire an action hook after successful deletion
        do_action('bp_group_documents_delete_success', $document);
        
        // Add a success message
        bp_core_add_message(esc_html__('Document deleted successfully', 'bp-group-documents'), 'success');
        
        return true;
    }

    // If deletion failed, return false
    bp_core_add_message(esc_html__('There was a problem deleting the document', 'bp-group-documents'), 'error');
    return false;
}


/**
 * bp_group_documents_check_ext()
 *
 * checks whether the passed filename ends in an extension
 * that is allowed by the site admin
 *
 * @version 2.0
 */
function bp_group_documents_check_ext( $filename )
{
    if (! $filename ) {
        return false;
    }

    // Get the list of valid file formats from the options
    $valid_formats_string = get_option('bp_group_documents_valid_file_formats');

    // Early return if no valid formats are specified
    if (empty($valid_formats_string) ) {
        return false;
    }

     // Convert the comma-separated string to an array and trim whitespace
    $valid_formats_array = array_map('trim', explode(',', $valid_formats_string));

    // Extract the file extension using pathinfo() which handles various edge cases
    $file_extension = pathinfo($filename, PATHINFO_EXTENSION);

    // Convert extension to lowercase for case-insensitive comparison
    $file_extension = strtolower($file_extension);

    // Check if the extension is in the list of allowed formats
    return in_array($file_extension, $valid_formats_array);
}

/**
 * get_file_size()
 *
 * returns a human-readable file-size for the passed file
 * adapted from a function in the PHP manual comments
 */
function get_file_size( $document, $precision = 1 )
{
    // Check if the document has a valid get_path method and file path
    if (! is_object($document) || ! method_exists($document, 'get_path') ) {
        bp_core_add_message(esc_html__('Invalid document object', 'bp-group-documents'), 'error');
        return false; // Return false if the document is invalid
    }
    $file_path = $document->get_path(1); // Get the file path from the document
    if (! file_exists($file_path) ) {
        bp_core_add_message(esc_html__('File not found', 'bp-group-documents'), 'error');
        return false; // Return false if the file doesn't exist
    }
    // Get the file size
    $bytes = filesize($file_path);
    if ($bytes === false ) {
        bp_core_add_message(esc_html__('Unable to retrieve file size', 'bp-group-documents'), 'error');
        return false; // Return false if filesize retrieval fails
    }

    // Define units for file size representation
    $units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

    // Calculate the unit of the file size (in KB, MB, etc.)
    $unit_index = floor(( $bytes ? log($bytes) : 0 ) / log(1024));
    $unit_index = min($unit_index, count($units) - 1); // Ensure the index doesn't exceed the array length

    // Convert bytes to the appropriate unit
    $bytes /= pow(1024, $unit_index);

    // Return rounded value with the appropriate unit
    return round($bytes, $precision) . ' ' . $units[ $unit_index ];
}

    /**
     * return_bytes()
     *
     * taken from the PHP manual examples.  Returns the number of bites
     * when given an abrevition (eg, max_upload_size)
     *
     * @version 2.0 fix 7.2 error
     */
function return_bytes( $val )
{
    $val  = trim($val);
    $last = strtolower($val[ strlen($val) - 1 ]);
    switch ( $last ) {
     // The 'G' modifier is available since PHP 5.1.0
    case 'g':
        $val = 1024;
    case 'm':
        $val = (float) $val * 1024;
    case 'k':
        $val = (float) $val * 1024;
    }

    return $val;
}

    /**
     * bp_group_documents_remove_data()
     *
     * Cleans out both the files and the database records when a group is deleted
     */
function bp_group_documents_remove_data( $group_id )
{

    $results = BP_Group_Documents::get_list_by_group($group_id);
    if (count($results) >= 1 ) {
        foreach ( $results as $document_params ) {
            $document = new BP_Group_Documents($document_params['id'], $document_params);
            $document->delete();
            do_action('bp_group_documents_delete_with_group', $document);
        }
    }
}

add_action('groups_group_deleted', 'bp_group_documents_remove_data');

    /**
     * bp_group_documents_register_taxonomies()
     *
     * registers the taxonomies to use with the WordPress Custom Taxonomy API
     */
function bp_group_documents_register_taxonomies()
{
    register_taxonomy(
        'group-documents-category',
        'group-document',
        array(
        'hierarchical' => true,
        'label'        => esc_html__('Group Document Categories', 'bp-group-documents'),
        'query_var'    => false,
        )
    );
}

    add_action('init', 'bp_group_documents_register_taxonomies');

    /**
     * bp_group_document_set_cookies()
     *
     * Set any cookies for our component.  This will usually be for list filtering and sorting.
     * We must create a dedicated function for this, to fire before the headers are sent
     * (doing this in the template object with the rest of the filtering/sorting is too late)
     * 
     * @version 2.0, cookie last only in current session, change where function is hooked
     */
function bp_group_documents_set_cookies()
{
    if (isset($_GET['bpgrd-order']) ) {
        $order = sanitize_text_field(wp_unslash($_GET['bpgrd-order'])); // Sanitize order parameter
        setcookie('bp-group-documents-order', $order, 0, '/');
    
    }
    if (isset($_GET['bpgrd-category']) ) {
        $category =sanitize_text_field(wp_unslash($_GET['bpgrd-category'])); // Sanitize category parameter
        setcookie('bp-group-documents-category', $category, 0, '/');
    }
}

add_action('bp_actions', 'bp_group_documents_set_cookies');
