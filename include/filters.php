<?php

// Exit if accessed directly
if (! defined('ABSPATH') ) {
    exit;
}

add_filter('bp_group_documents_name_out', 'htmlspecialchars');
add_filter('bp_group_documents_description_out', 'stripslashes');

add_filter('bp_group_documents_filename_in', 'bp_group_documents_prepare_filename');
add_filter('bp_group_documents_featured_in', 'bp_group_documents_prepare_checkbox');
add_filter('bp_group_documents_category_ids_in', 'bp_group_documents_cast_array');

/**
 *
 * @param   string $file
 * @return  string
 * @version 2.0, 11/11/2022
 */
function bp_group_documents_prepare_filename( $file )
{

    $file     = time() . '-' . $file;
    $file_new = preg_replace('/[^0-9a-zA-Z-_.]+/', '', $file);
    return $file_new;
}

//html checkboxes don't send anything if they are not checked
//turn the absence of an explicit "true" into a false
function bp_group_documents_prepare_checkbox( $value )
{
    if (! ( isset($value) && $value ) ) {
        $value = false;
    }

    return $value;
}

//when passing category ids to taxonomy functions, they
//cannot be strings.
function bp_group_documents_cast_array( $array )
{
    if (is_array($array) and count($array) ) {
        foreach ( $array as &$value ) {
            $value = (int) $value;
        }
    }
    return $array;
}

//Code from  http://dev.commons.gc.cuny.edu/2011/02/05/hardening-buddypress-group-documents/--  for Hardening BuddyPress Group Documents security follows  //

/**
 *
 * @param type $doc_url
 * @param type $group_id
 * @param type $file
 * 
 * @return string
 * @since  0.5
 * 
 * @version 2.0 17/2/2025, compatibility BP 12+
 */
function cac_filter_doc_url( $doc_url, $group_id, $file )
{
    $buddypress_version = bp_get_version(); // Get the BuddyPress version
    $compare_version = '12.0.0'; // The version to compare against
    if (version_compare($buddypress_version, $compare_version, '>=') ) {
        $url = bp_get_root_url() . '?get_group_doc=' . $group_id . '/' . $file;
    }
    else {
        $url = bp_get_root_domain() . '?get_group_doc=' . $group_id . '/' . $file;
    }
    return $url;
}

add_filter('bp_group_documents_file_url', 'cac_filter_doc_url', 10, 3);

/**
 * Defines if the user can download the document
 * 
 * @return type
 * @since  0.5
 * 
 * @version 2.0 17/2/2025, compatibility BP 12+
 * 1.9.2, 9/3/2015, fix for download count
 * v1.9.1, 16/1/2015, add bp_group_documents_download_access filter
 */
function cac_catch_group_doc_request()
{
    $buddypress_version = bp_get_version(); // Get the BuddyPress version
    $compare_version = '12.0.0'; // The version to compare against
    
    $error = false;
    if (empty($_GET['get_group_doc']) ) {
        return;
    }

    $doc_id =  $_GET['get_group_doc'];

    // Check to see whether the current user has access to the doc in question
    $file_deets = explode('/', $doc_id);

    $group_id = $file_deets[0];
    $group    = new BP_Groups_Group($group_id);
    $doc_name = $file_deets[1];

    if (empty($group->id) ) {
        if (version_compare($buddypress_version, $compare_version, '>=') ) {
            $error = array(
            'message'  => esc_html__('That group does not exist.', 'bp-group-documents'),
            'redirect' => bp_get_root_url(),
             );
        } else {
            $error = array(
            'message'  => esc_html__('That group does not exist.', 'bp-group-documents'),
            'redirect' => bp_get_root_domain(),
             );
        }
    } else {
        if ('public' !== $group->status ) { // If the group is not public,
            if (! ( is_super_admin() ) ) { //then the user must be logged in and
                // a member of the group to download the document
                if (! is_user_logged_in() || ! groups_is_user_member(bp_loggedin_user_id(), $group_id) ) {
                    // Compare the versions
                    if (version_compare($buddypress_version, $compare_version, '>=') ) {
                        $error = array(
                        'message'  => sprintf(
                        // Translators: %s will be replaced with the name of the group.
                            esc_html__('You must be a logged-in member of the group %s to access this document. If you are a member of the group, please log into the site and try again.', 'bp-group-documents'),
                            esc_html($group->name)
                        ),
                        'redirect' => esc_url(bp_get_group_url($group)),
                        );
                    } else {
                        $error = array(
                        'message'  => sprintf(
                        // Translators: %s will be replaced with the name of the group.
                            esc_html__(
                                'You must be a logged-in member of the group %s to access this document. If you are a member of the group, please log into the site and try again.',
                                'bp-group-documents'
                            ),
                            esc_html($group->name)
                        ),
                        'redirect' => esc_url(bp_get_group_permalink($group)),
                        );
                    }
                }
            }
        }
        /**
         * Filter the error.
         *
         * @since 1.9.1
         *
         * @param array $error A compacted array of $error arguments, including the "message" and
         *                    "redirect" values.
         */
        $error = apply_filters('bp_group_documents_download_access', $error);
        //
        // If we have gotten this far without an error, then the download can go through
        if (! $error ) {
            $document = new BP_Group_Documents();
            $document->populate_by_file($doc_name);
            $doc_path = $document->get_path();
            clearstatcache();

            if (file_exists($doc_path) ) {
                $document->increment_download_count();
                $mime_type = mime_content_type($doc_path);
                $doc_size  = filesize($doc_path);

                header('Cache-Control: public, must-revalidate, post-check=0, pre-check=0');
                header('Pragma: hack');

                header("Content-Type: $mime_type; name='" . $file_deets[1] . "'");
                header('Content-Length: ' . $doc_size);

                header('Content-Disposition: inline; filename="' . $file_deets[1] . '"');
                header('Content-Transfer-Encoding: binary');
                while ( ob_get_level() > 0 ) {
                    ob_end_clean();
                }
                flush();
                readfile($doc_path);
                die();
            } else {
                // Compare the versions
                if (version_compare($buddypress_version, $compare_version, '>=') ) {
                    // File does not exist
                    $error = array(
                    'message'  => esc_html_e('The file could not be found.', 'bp-group-documents'),
                    'redirect' => bp_get_group_url($group) . 'documents',
                    );
                } else {
                    // File does not exist
                    $error = array(
                    'message'  => esc_html_e('The file could not be found.', 'bp-group-documents'),
                    'redirect' => bp_get_group_permalink($group) . 'documents',
                    );
                }
            }
        }
    }

    // If we have gotten this far, there was an error. Add a message and redirect
    bp_core_add_message($error['message'], 'error');
    bp_core_redirect($error['redirect']);
}

add_filter('wp', 'cac_catch_group_doc_request', 1);

// http://www.php.net/manual/en/function.mime-content-type.php#87856
if (! function_exists('mime_content_type') ) {

    /**
     *
     * @param  string $filename
     * @return string
     */
    function mime_content_type( $filename )
    {
        $mime_types = array(
        'txt'  => 'text/plain',
        'htm'  => 'text/html',
        'html' => 'text/html',
        'php'  => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'xml'  => 'application/xml',
        'swf'  => 'application/x-shockwave-flash',
        'flv'  => 'video/x-flv',
        // images
        'png'  => 'image/png',
        'jpe'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg'  => 'image/jpeg',
        'gif'  => 'image/gif',
        'bmp'  => 'image/bmp',
        'ico'  => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif'  => 'image/tiff',
        'svg'  => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        // archives
        'zip'  => 'application/zip',
        'rar'  => 'application/x-rar-compressed',
        'exe'  => 'application/x-msdownload',
        'msi'  => 'application/x-msdownload',
        'cab'  => 'application/vnd.ms-cab-compressed',
        // audio/video
        'mp3'  => 'audio/mpeg',
        'qt'   => 'video/quicktime',
        'mov'  => 'video/quicktime',
        // adobe
        'pdf'  => 'application/pdf',
        'psd'  => 'image/vnd.adobe.photoshop',
        'ai'   => 'application/postscript',
        'eps'  => 'application/postscript',
        'ps'   => 'application/postscript',
        // ms office
        'doc'  => 'application/msword',
        'rtf'  => 'application/rtf',
        'xls'  => 'application/vnd.ms-excel',
        'ppt'  => 'application/vnd.ms-powerpoint',
        // open office
        'odt'  => 'application/vnd.oasis.opendocument.text',
        'ods'  => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types) ) {
            return $mime_types[ $ext ];
        } elseif (function_exists('finfo_open') ) {
            $finfo    = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }
}
