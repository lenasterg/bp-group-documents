<?php
/**
 * last edit 1.24
 */
// Exit if accessed directly
if (! defined('ABSPATH') ) {
    exit;
}
class BP_Group_Documents
{
    public $id; // int
    public $user_id; // int
    public $group_id; // int
    public $created_ts; // unix timestamp
    public $modified_ts; // unix timestamp
    public $file; // varchar
    public $name; // varchar
    public $description; // text
    public $featured; // bool
    public $download_count; // int

    /**
     * @var   string
     * @since version 1.25
     */
    public $file_extension;

    /**
     * Constructor
     *
     * The constructor will either create a new empty object if no ID is set, 
     * or fill the object with a row from the table, or the passed parameters, 
     * if an ID is provided.
     *
     * @param int|null $id     ID of the document.
     * @param mixed    $params Parameters to populate the object (optional).
     */
    public function __construct( $id = null, $params = false )
    {
        if ($id && ctype_digit($id) ) {
            $this->id = (int) $id;
            if ($params ) {
                $this->populate_passed($params);
            } else {
                $this->populate($this->id);
            }
        }
    }

    /**
     * Populate object with data from the database.
     *
     * This method will populate the object with a row from the database, 
     * based on the ID passed to the constructor.
     */
    private function populate()
    {
        global $wpdb;       
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . BP_GROUP_DOCUMENTS_TABLE . ' WHERE id = %d', $this->id));
        if (isset($row) ) {
            foreach ( $this as $field => $value ) {
                if (isset($row->$field) ) {
                    $this->$field = $row->$field;
                }
            }

        }
    }

    /**
     * Populate object with passed parameters.
     *
     * This method will populate the object with the passed parameters, 
     * saving a call to the database.
     *
     * @param array $params Parameters to populate the object.
     */
    private function populate_passed( $params )
    {
        // If checkbox is unchecked, nothing will be present
        // Turn absence of "true" into a "false"
        if (! isset($params['featured']) ) {
            $params['featured'] = false;
        }

        foreach ( $this as $key => $value ) {
            if (isset($params[ $key ]) ) {
                $this->$key = $params[ $key ];
            }
        }
    }

    /**
     * Populate object by file name.
     *
     * This will populate the object's properties based on the passed file name. 
     * It will return false if the name is not found.
     *
     * @param  string $file File name to search for.
     * @return bool True if file is found and populated, otherwise false.
     */
    public function populate_by_file( $file )
    {
        global $wpdb;

        // Escape the file to prevent SQL injection
        $file =  sanitize_text_field(wp_unslash($file));
        if ($row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . BP_GROUP_DOCUMENTS_TABLE . " WHERE file LIKE '%s'", $file)) ) {
            foreach ( $this as $field => $value ) {
                if (isset($row->$field) ) {
                    $this->$field = $row->$field;
                }
            }

             return true;
        }
        return false;
    }

    /**
     * Clear cache after update.
     * Clears the cache for all transients related to group documents
     * in both single-site and multisite installations.
     *
     * This includes the following prefixes:
     * - 'bp_group_docs_usergroups_'
     * - 'bp_group_documents_list_'
     * - 'bp_group_documents_newest_widget_'
     * - 'bp_group_documents_popular_widget_'
     * This method should be called whenever the document is updated or deleted
     * to invalidate the cached version of the document.
     * 
     * @since 2.0
     */
    public function clear_cache()
    {
        global $wpdb;
        // Check if the 'bp-group-documents-category' cookie is set
        if (isset($_COOKIE['bp-group-documents-category']) ) {
            // Remove the cookie by setting its expiration time to one hour ago
            // COOKIEPATH and COOKIE_DOMAIN ensure the cookie is correctly removed based on WordPress settings
            setcookie('bp-group-documents-category', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
            unset($_COOKIE['bp-group-documents-category']);
        }
    
        // Check if the 'bp-group-documents-order' cookie is set
        if (isset($_COOKIE['bp-group-documents-order']) ) {
                 // Remove the cookie by setting its expiration time to one hour ago
               // COOKIEPATH and COOKIE_DOMAIN ensure the cookie is correctly removed based on WordPress settings

            setcookie('bp-group-documents-order', '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
            unset($_COOKIE['bp-group-documents-order']);
        }
    
        // Clear cache for the document by ID and file name
        if ($this->id ) {
            wp_cache_delete('bp_group_document_' . $this->id, 'bp-group-documents');
        }

        if ($this->file ) {
            wp_cache_delete('bp_group_document_file_' . md5($this->file), 'bp-group-documents');
        }
    

        // List of prefixes for transients related to group documents
        $prefixes = [
        '_transient_bp_group_docs_usergroups_',
        '_transient_bp_group_documents_list_',
        '_transient_bp_group_documents_newest_widget_',
        '_transient_bp_group_documents_popular_widget_'
        ];

        // Loop through each prefix to delete transients in single-site installations
        foreach ($prefixes as $prefix) {
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                    $prefix . '%'
                )
            );
        }

        // If multisite, also delete transients from the sitemeta table
        if (is_multisite()) {
            $site_prefixes = array_map(
                function ($prefix) {
                    return str_replace('_transient_', '_site_transient_', $prefix);
                }, $prefixes
            );

            foreach ($site_prefixes as $site_prefix) {
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s",
                        $site_prefix . '%'
                    )
                );
            }
        }
    }

   
    /**
     * save()
     *
     * This method will save an object to the database. It will dynamically switch between
     * INSERT and UPDATE depending on whether or not the object already exists in the database.
     *
     * @global type $wpdb
     * @param  boolean $check_file_upload
     * @return boolean
     */
    public function save( $check_file_upload = true )
    {
        global $wpdb;

        // Trigger action before saving
        do_action('bp_group_documents_data_before_save', $this);
    
        // Clear cache before saving
        $this->clear_cache();
    
        //    echo '<pre>';
        ////var_dump($this);
        //    echo '</pre>';
        if ($this->id ) {
            // Update existing record
            $result = $wpdb->query(
                $wpdb->prepare(
                    'UPDATE ' . BP_GROUP_DOCUMENTS_TABLE . ' SET
                        modified_ts = %d,
                        name = %s,
                        description = %s,
                        featured = %d
                    WHERE id = %d',
                    time(),
                    $this->name,
                    $this->description,
                    $this->featured,
                    $this->id
                )
            );
        } else {
            // Insert new record
            if ($check_file_upload ) {
                if (! $this->upload_file() ) {
                    return false;
                }
            }

            $result = $wpdb->query(
                $wpdb->prepare(
                    'INSERT INTO ' . BP_GROUP_DOCUMENTS_TABLE . ' (
                        user_id,
                        group_id,
                        created_ts,
                        modified_ts,
                        file,
                        name,
                        description,
                        featured
                    ) VALUES (
                        %d, %d, %d, %d, %s, %s, %s, %d
                    )',
                    $this->user_id,
                    $this->group_id,
                    time(),
                    time(),
                    $this->file,
                    $this->name,
                    $this->description,
                    $this->featured
                )
            );
        }

        if (! $result ) {
            // Trigger action when save fails
            do_action('bp_group_documents_data_failed_save', $this);
            return false;
        }

        // Set ID if it's a new document
        if (! $this->id ) {
            $this->id = $wpdb->insert_id;
        }

        

        // Trigger action after successful save
        do_action('bp_group_documents_data_after_save', $this);

        return $result;
    }

    /**
     * increment_download_count()
     *
     * Adds one to the download count for the current document
     */
    public function increment_download_count()
    {
        global $wpdb;

        // Increment download count in the database
        $result = $wpdb->query(
            $wpdb->prepare(
                'UPDATE ' . BP_GROUP_DOCUMENTS_TABLE . ' SET download_count = (download_count + 1) WHERE id = %d',
                $this->id
            )
        );

        if ($result ) {
            // Clear cache after incrementing the download count
            $this->clear_cache();
        }

        return $result;
    }

    /**
     * delete()
     *
     * This method will delete the corresponding row for an object from the database.
     */
    public function delete()
    {
        global $wpdb;

        if ($this->current_user_can('delete') ) {
            if ($this->file && file_exists($this->get_path(1)) ) {
                //@unlink($this->get_path(1));
                wp_delete_file($this->get_path(1));
            }

            $result = $wpdb->query(
                $wpdb->prepare(
                    'DELETE FROM ' . BP_GROUP_DOCUMENTS_TABLE . ' WHERE id = %d',
                    $this->id
                )
            );

            if ($result ) {
                // Clear cache after deletion
                $this->clear_cache();
            }

            return $result;
        }

        return false;
    }

    /**
     * UploadFile()
     *
     * Handles file upload, checking for errors, and moving the file to the desired location.
     *
     * @return boolean
     */
    private function upload_file()
    {
        // Check that a file exists
        if (empty($_FILES['bp_group_documents_file']['name']) ) {
            bp_core_add_message(esc_html__('Whoops! There was no file selected for upload.', 'bp-group-documents'), 'error');
            return false;
        }

        // Check that the file has an allowed extension
        if (! bp_group_documents_check_ext($_FILES['bp_group_documents_file']['name']) ) {
            bp_core_add_message(esc_html__('The type of document submitted is not allowed', 'bp-group-documents'), 'error');
            return false;
        }

        // Handle any upload errors
        if ($_FILES['bp_group_documents_file']['error'] ) {
            switch ( $_FILES['bp_group_documents_file']['error'] ) {
            case UPLOAD_ERR_INI_SIZE:
                bp_core_add_message(esc_html__('There was a problem; your file is larger than is allowed by the site administrator.', 'bp-group-documents'), 'error');
                break;
            case UPLOAD_ERR_PARTIAL:
                bp_core_add_message(esc_html__('There was a problem; the file was only partially uploaded.', 'bp-group-documents'), 'error');
                break;
            case UPLOAD_ERR_NO_FILE:
                bp_core_add_message(esc_html__('There was a problem; no file was found for the upload.', 'bp-group-documents'), 'error');
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                bp_core_add_message(esc_html__('There was a problem; the temporary folder for the file is missing.', 'bp-group-documents'), 'error');
                break;
            case UPLOAD_ERR_CANT_WRITE:
                bp_core_add_message(esc_html__('There was a problem; the file could not be saved.', 'bp-group-documents'), 'error');
                break;
            }
            return false;
        }

        // If the user didn't specify a display name, use the file name (before the timestamp)
        if (! $this->name ) {
            $this->name = basename($_FILES['bp_group_documents_file']['name']);
        }

        // Filter the file name through a WordPress filter
        $this->file = apply_filters('bp_group_documents_filename_in', basename($_FILES['bp_group_documents_file']['name']));

        // Get the file path and move the uploaded file
        $file_path = $this->get_path(0, 1);

        if (move_uploaded_file($_FILES['bp_group_documents_file']['tmp_name'], $file_path) ) {
            $this->clear_cache();
            return true;
        } else {
            bp_core_add_message(esc_html__('There was a problem saving your file, please try again.', 'bp-group-documents'), 'error');
            return false;
        }
    }

    /**
     * current_user_can()
     *
     * Determines if the current user has permission to perform the specified action.
     *
     * @param  string $action   (add, edit, delete)
     * @param  int    $group_id (optional, defaults to current group if none given)
     * @return boolean
     */
    public function current_user_can( $action, $group_id = false )
    {
        $bp = buddypress();

        // If no group ID is passed, use the current group
        if (! $group_id ) {
            $group_id = $bp->groups->current_group->id;
        }

        $user_id = get_current_user_id();

        // Super admins can always perform any action
        if (is_super_admin($user_id) ) {
            return true;
        }

        // Check if the user is a group admin
        if (groups_is_user_admin($user_id, $group_id) ) {
            return true;
        }

        switch ( $action ) {
        case 'add':
            // Check the group document upload permission setting
            switch ( get_option('bp_group_documents_upload_permission') ) {
            case 'mods_decide':
                switch ( groups_get_groupmeta($group_id, 'bp_group_documents_upload_permission') ) {
                case 'mods_only':
                    if (groups_is_user_mod($user_id, $group_id) ) {
                            return true;
                    }
                    break;
                case 'members':
                default:
                    if (groups_is_user_member($user_id, $group_id) ) {
                        return true;
                    }
                    break;
                }
                break;
            case 'mods_only':
                if (groups_is_user_mod($user_id, $group_id) ) {
                    return true;
                }
                break;
            case 'members':
            default:
                if (groups_is_user_member($user_id, $group_id) ) {
                    return true;
                }
                break;
            }
            break;
        case 'edit':
            $user_is_owner = ( $this->user_id === $user_id );
            if (groups_is_user_mod($user_id, $group_id) || ( groups_is_user_member($user_id, $group_id) && $user_is_owner ) ) {
                return true;
            }
            break;
        case 'delete':
            $user_is_owner = ( $this->user_id === $user_id );
            if (groups_is_user_mod($user_id, $group_id) || ( groups_is_user_member($user_id, $group_id) && $user_is_owner ) ) {
                return true;
            }
            break;
        }

        return false;
    }
   
    /**
     * url()
     *
     * Returns the full URL of the document.
     * If $legacy_check is true (default), the function
     * will check past locations if the file is not found.
     *
     * @param int $legacy_check
     */
    public function url( $legacy_check = 1 )
    {
        echo esc_url($this->get_url($legacy_check));
    }

    /**
     * get_url()
     *
     * Returns the full URL of the document.
     * If $legacy_check is true, it will check past locations if the file is not found.
     *
     * @param  int $legacy_check
     * @return string $document_url
     */
    public function get_url( $legacy_check = 1 )
    {
        // Preferred place for documents - in the upload folder, sorted by group
        if (function_exists('bp_core_avatar_upload_path') ) {
            $document_url = str_replace(WP_CONTENT_DIR, content_url(), bp_core_avatar_upload_path()) . '/group-documents/' . $this->group_id . '/' . $this->file;
        } else {
            $path = get_blog_option(BP_ROOT_BLOG, 'upload_path'); // wp-content/blogs.dir/1/files
            $document_url = content_url() . str_replace('wp-content', '', $path);
            $document_url .= '/group-documents/' . $this->group_id . '/' . $this->file;
        }

        // Check if the URL is relative and append the base domain if needed
        if ('wp-content' == substr($document_url, 0, 10) ) {
            $document_url = get_bloginfo('home') . '/' . $document_url;
        }

        // If legacy_check is true, attempt to check legacy locations if the file doesn't exist
        if ($legacy_check ) {
            // This is the server path of the $document_url above
            $document_path = $this->get_path();
            if (! file_exists($document_path) ) {
                // Check legacy override
                if (defined('BP_GROUP_DOCUMENTS_FILE_URL') ) {
                    $document_url = BP_GROUP_DOCUMENTS_FILE_URL . $this->file;
                } else {
                    // If not there, check the legacy location
                    $document_url = plugins_url() . '/' . BP_GROUP_DOCUMENTS_DIR . '/documents/' . $this->file;
                }
            }
        }
    
        return apply_filters('bp_group_documents_file_url', $document_url, $this->group_id, $this->file);
    
    
    }

    /**
     * path()
     *
     * Returns the full server path of the document.
     *
     * If $legacy_check is true, the function will attempt to check
     * past locations if the existing document is not found (used for retrieval).
     *
     * If $create_folders is true, it will recursively create the path
     * to the new file (used for assignment).
     *
     * @param int $legacy_check
     * @param int $create_folders
     */
    public function path( $legacy_check = 0, $create_folders = 0 )
    {
        echo esc_url($this->get_path($legacy_check, $create_folders));
    }

    /**
     * get_path()
     *
     * Returns the full server path of the document.
     *
     * @param  int $legacy_check
     * @param  int $create_folders
     * @return string $document_path
     */
    public function get_path( $legacy_check = 0, $create_folders = 0 )
    {
        // Place 'group-documents' on the same level as 'group-avatars'
        // Organize docs within group sub-folders
        if (function_exists('bp_core_avatar_upload_path') ) { // BP 1.2 and later
            $document_dir = bp_core_avatar_upload_path() . '/group-documents/' . $this->group_id;
        } else { // BP 1.1
            $path = get_blog_option(BP_ROOT_BLOG, 'upload_path'); // wp-content/blogs.dir/1/files
            $document_dir = WP_CONTENT_DIR . str_replace('wp-content', '', $path);
            $document_dir .= '/group-documents/' . $this->group_id;
        }

        // Create directory or .htaccess if necessary
        $this->create_dir_or_htaccess($document_dir);

        // Ideal location - use this if possible
        $document_path = $document_dir . '/' . $this->file;

        // If we're getting the existing file to display, it may not be there
        // If file is not found, check in legacy locations
        if ($legacy_check && ! file_exists($document_path) ) {
            // Check legacy override
            if (defined('BP_GROUP_DOCUMENTS_FILE_PATH') ) {
                $document_path = BP_GROUP_DOCUMENTS_FILE_PATH . $this->file;
            } else {
                // If not there, check the legacy default
                $document_path = WP_PLUGIN_DIR . '/' . BP_GROUP_DOCUMENTS_DIR . '/documents/' . $this->file;
            }
        }

        return apply_filters('bp_group_documents_file_path', $document_path, $this->group_id, $this->file);
    }

    /**
     * Prints document categories.
     *
     * @since   version 0.5.4
     * @version 4.0 17/2/2025, compatibility with BP 12+
     * 3, 8/12/2014 fix category link
     * v2, 12/11/2014, category link added
     * v1, 21/5/2013, lenasterg
     */
    public function categories()
    {
        $toprint                = '';
        $categories_of_document = $this->get_document_categories();
        $group                  = groups_get_group(array( 'group_id' => $this->group_id ));

        if (! empty($categories_of_document) ) {
            if (! is_wp_error($categories_of_document) ) {
                echo esc_html('In category:', 'bp-group-documents'); // Translators: Used before listing document categories
                
                $buddypress_version = bp_get_version(); // Get the BuddyPress version
                $compare_version = '12.0.0'; // The version to compare against
                
                // Compare the versions
                if (version_compare($buddypress_version, $compare_version, '>=') ) {
                    foreach ( $categories_of_document as $term ) {
                        $toprint .= ' <strong><a href="' . esc_url(bp_get_group_url($group) . BP_GROUP_DOCUMENTS_SLUG . '?bpgrd-category=' . $term->term_id) . '">' .esc_html($term->name) . '</a></strong>,';
                    }
                } else {
                    foreach ( $categories_of_document as $term ) {
                        $toprint .= ' <strong><a href="' . esc_url(bp_get_group_permalink($group) . BP_GROUP_DOCUMENTS_SLUG . '?bpgrd-category=' . $term->term_id) . '">' .esc_html($term->name) . '</a></strong>,';
                    }
                }

                // Remove the last comma and append the period.
                echo wp_kses_post(rtrim($toprint, ',') . '. <br/>');
            }
        }
    }

    /**
     * Fetches document categories.
     *
     * @since   version 0.5.4
     * @version 1, 21/5/2013, lenasterg
     * 
     * @return array
     */
    public function get_document_categories()
    {
        $categories = wp_get_object_terms($this->id, 'group-documents-category');
        return $categories;
    }

    /**
     * icon()
     * 
     * This method retrieves the appropriate icon URL based on the file type or extension,
     * escapes it for security, and outputs an HTML <img> tag with the icon.
     *
     * @return void This method outputs an HTML <img> tag and does not return any value.
     */
    public function icon()
    {
        // Get the icon URL based on the file type or extension
        $icon_url = $this->get_icon();

        // Check if the icon URL is valid (not false)
        if (false !== $icon_url ) {
            // The alt attribute for the image provides context for the type of file it represents.
            echo '<img class="bp-group-documents-icon" src="' . esc_url($icon_url) . '" alt="' .sprintf( 
            // Translators: %s will be replaced with the file extension (e.g., .pdf, .jpg).
                esc_attr__('Icon for file type: %s', 'bp-group-documents'),  esc_attr($this->file_extension) 
            ) . '" />';
        }
    }




    /**
     * Get the icon URL based on the document's file extension.
     *
     * @return boolean|string The URL to the icon image or false if no icon found.
     *
     * @todo: Make it search in a relative folder for icon images.
     * 
     * @since version 1.25
     */
    public function get_icon()
    {
        $icons = array(
            'adp'  => 'page_white_database.png',
            'as'   => 'page_white_actionscript.png',
            'avi'  => 'film.png',
            'bash' => 'script.png',
            'bz'   => 'package.png',
            'bz2'  => 'package.png',
            'c'    => 'page_white_c.png',
            'cf'   => 'page_white_coldfusion.png',
            'cpp'  => 'page_white_cplusplus.png',
            'cs'   => 'page_white_csharp.png',
            'css'  => 'page_white_code.png',
            'deb'  => 'package.png',
            'doc'  => 'page_white_word.png',
            'docx' => 'page_white_word.png',
            'eps'  => 'page_white_vector.png',
            'exe'  => 'application_xp_terminal.png',
            'fh'   => 'page_white_freehand.png',
            'fl'   => 'page_white_flash.png',
            'gif'  => 'picture.png',
            'gz'   => 'package.png',
            'htm'  => 'page_white_code.png',
            'html' => 'page_white_code.png',
            'iso'  => 'cd.png',
            'java' => 'page_white_cup.png',
            'jpeg' => 'picture.png',
            'jpg'  => 'picture.png',
            'json' => 'page_white_code.png',
            'm4a'  => 'music.png',
            'mov'  => 'film.png',
            'mdb'  => 'page_white_database.png',
            'mp3'  => 'music.png',
            'mp4'  => 'mp4.png',
            'mpeg' => 'film.png',
            'msp'  => 'page_white_paintbrush.png',
            'ods'  => 'application_view_columns.png',
            'odt'  => 'page_white_text.png',
            'ogg'  => 'music.png',
            'perl' => 'script.png',
            'pdf'  => 'page_white_acrobat.png',
            'php'  => 'page_white_php.png',
            'png'  => 'picture.png',
            'ppt'  => 'page_white_powerpoint.png',
            'pps'  => 'page_white_powerpoint.png',
            'pptx' => 'page_white_powerpoint.png',
            'ppsx' => 'ppsx.gif',
            'ps'   => 'page_white_paintbrush.png',
            'rb'   => 'page_white_ruby.png',
            'rtf'  => 'page_white_text.png',
            'sh'   => 'script.png',
            'sql'  => 'database.png',
            'swf'  => 'page_white_flash.png',
            'tar'  => 'package.png',
            'txt'  => 'page_white_text.png',
            'wav'  => 'music.png',
            'xls'  => 'page_white_excel.png',
            'xlsx' => 'page_white_excel.png',
            'xml'  => 'page_white_code.png',
            'zip'  => 'page_white_zip.png',
        );

        // Get file extension from document file
        $extension1 = substr($this->file, ( strrpos($this->file, '.') + 1 ));
        $extension  = strtolower($extension1);

        // If icon for the file extension does not exist, return false
        if (! isset($icons[ $extension ]) ) {
            return false;
        }

        // Cache the file extension so that it can be used elsewhere if needed
        $this->file_extension = $extension;

        // Build the icon folder URL
        $img_folder = plugins_url() . '/' . BP_GROUP_DOCUMENTS_DIR . '/images/icons/';
        
         $icon_url = $img_folder . $icons[ $extension ];

        // Allow other code to modify the icon URL if needed
        return apply_filters('bp_group_documents_get_icon', $icon_url);
    }

    /* Static Functions */

    /**
     * Get the document IDs for the current group.
     *
     * @global object $wpdb WordPress database class.
     * @return array List of document IDs for the current group.
     * 
     * @version 2, 12/11/2014
     */
    public static function get_ids_in_current_group()
    {
        global $wpdb;
        $bp = buddypress();

        // Get the current group ID from BuddyPress
        $group_id = $bp->groups->current_group->id;

        // Fetch the document IDs from the database for the given group
        return $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . BP_GROUP_DOCUMENTS_TABLE . ' WHERE group_id = %d', $group_id));
    }

    /**
     * Get the total number of documents in a given group and category (if specified).
     *
     * @global object $wpdb WordPress database class.
     * @param  int $group_id The ID of the group.
     * @param  int $category The category ID (optional).
     * @return int The total number of documents.
     * 
     * @version 1.2.2.
     */
    public static function get_total( $group_id, $category = false )
    {
        global $wpdb;

        // Base SQL query to count documents
        $sql = 'SELECT COUNT(*) FROM ' . BP_GROUP_DOCUMENTS_TABLE . ' WHERE group_id = %d ';
        
        if ($category ) {
            // Get all object IDs for the given category
            $category_ids = get_objects_in_term($category, 'group-documents-category');

            if (! empty($category_ids) ) {
                // Include the category IDs in the query
                $in_clause = '(' . implode(',', array_map('absint', $category_ids)) . ') ';
                $sql .= 'AND id IN ' . $in_clause;
            }
        }

        // Return the result
        $result = $wpdb->get_var($wpdb->prepare($sql, $group_id));
        return $result;
    }
    /**
     * Get the list of documents for a given group, with optional filters.
     *
     * @global type $wpdb
     * @param  int    $group_id The ID of the group.
     * @param  int    $category The category filter (optional).
     * @param  string $sort     The sorting field (optional).
     * @param  string $order    The order direction (optional).
     * @param  int    $start    The starting point for pagination (optional).
     * @param  int    $items    The number of items to retrieve (optional).
     * @return array The list of documents for the given parameters.
     */
    public static function get_list_by_group( $group_id, $category = 0, $sort = 0, $order = 0, $start = 0, $items = 0 )
    {
        global $wpdb;

        if (! $category && ! $sort ) {
            $result = $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . BP_GROUP_DOCUMENTS_TABLE . ' WHERE group_id = %d ORDER BY name ASC', $group_id), ARRAY_A);
        } else {
            // Convert from 1-based to 0-based index for pagination
            --$start;

            // Grab all object ids in the passed category
            $category_ids = get_objects_in_term($category, 'group-documents-category');
            $sql = 'SELECT * FROM ' . BP_GROUP_DOCUMENTS_TABLE . ' WHERE group_id = %d ';
            if (! empty($category_ids) ) {
                $in_clause = '(' . implode(',', $category_ids) . ') ';
                $sql .= 'AND id IN ' . $in_clause;
            }
            $sql .= "ORDER BY $sort $order LIMIT %d, %d";
            $result = $wpdb->get_results($wpdb->prepare($sql, $group_id, $start, $items), ARRAY_A);
        }


        return $result;
    }

    /**
     * Get documents list for the 'newest' widget.
     *
     * @global type $wpdb
     * @param  int $num          The number of documents to retrieve.
     * @param  int $group_filter The group ID filter (optional).
     * @param  int $featured     Whether to filter by featured documents (optional).
     * @return array The list of documents for the newest widget.
     */
    public static function get_list_for_newest_widget( $num, $group_filter = 0, $featured = 0 )
    {
        global $wpdb;
        $bp = buddypress();
        if ($group_filter || $featured ) {
                $sql = 'SELECT * FROM ' . BP_GROUP_DOCUMENTS_TABLE . ' WHERE 1=1 ';
            if ($group_filter ) {
                $sql .= $wpdb->prepare('AND group_id = %d ', $group_filter);
            }
            if ($featured && BP_GROUP_DOCUMENTS_FEATURED ) {
                $sql .= 'AND featured = 1 ';
            }
                $sql   .= 'ORDER BY created_ts DESC LIMIT %d';
                $result = $wpdb->get_results($wpdb->prepare($sql, $num), ARRAY_A);
        } else {
            $result = $wpdb->get_results($wpdb->prepare('SELECT d.* FROM ' . BP_GROUP_DOCUMENTS_TABLE . " d INNER JOIN {$bp->groups->table_name} g ON d.group_id = g.id WHERE g.status = 'public' ORDER BY created_ts DESC LIMIT %d", $num), ARRAY_A);
        }
        return $result;
    }

    /**
     * Get documents list for the 'popular' widget.
     *
     * @global type $wpdb
     * @param  int $num          The number of documents to retrieve.
     * @param  int $group_filter The group ID filter (optional).
     * @param  int $featured     Whether to filter by featured documents (optional).
     * @return array The list of documents for the popular widget.
     */
    public static function get_list_for_popular_widget( $num, $group_filter = 0, $featured = 0 )
    {
        global $wpdb;
        $bp = buddypress();
        if ($group_filter || $featured ) {
            $sql = 'SELECT * FROM ' . BP_GROUP_DOCUMENTS_TABLE . ' WHERE 1=1 ';
            if ($group_filter ) {
                $sql .= $wpdb->prepare('AND group_id = %d ', $group_filter);
            }
            if ($featured ) {
                $sql .= 'AND featured = 1 ';
            }
            $sql   .= 'ORDER BY download_count DESC LIMIT %d';
            $result = $wpdb->get_results($wpdb->prepare($sql, $num), ARRAY_A);
        } else {
            $result = $wpdb->get_results($wpdb->prepare('SELECT d.* FROM ' . BP_GROUP_DOCUMENTS_TABLE . " d INNER JOIN {$bp->groups->table_name} g ON d.group_id = g.id WHERE g.status = 'public' ORDER BY download_count DESC LIMIT %d", $num), ARRAY_A);
        }

              return $result;
    }

    
    /**
     * Retrieves a list of documents for the user groups widget.
     *
     * This function fetches documents from groups that the current user is a member of,
     * optionally filtering for featured documents.
     *
     * @global    wpdb $wpdb WordPress database abstraction object.
     * @param     int  $num      Number of documents to retrieve.
     * @param     bool $featured Whether to only retrieve featured documents. Default is false.
     * @return    array An array of document objects or an empty array if no documents are found.
     * @since     0.5
     * @version   1.2.2
     * @author    stergatu
     * @changelog
     * - 1.2.2 - Improved SQL query for security and performance.
     * - 1.2.1 - 17/9/2013 - Fixed widget functionality bug (https://wordpress.org/support/topic/widget-functionality).
     * - 1.0 - 1/5/2013 - Initial version.
     */
    public static function get_list_for_usergroups_widget($num, $featured = false)
    {
        global $wpdb;
    
        $user_id = get_current_user_id();
        // Check if the current user has groups
        if (!bp_has_groups('user_id=' . $user_id)) {
            return array();
        }

        $user_groups = groups_get_user_groups($user_id);
        $group_ids   = array_map('absint', $user_groups['groups']); // Ensure group IDs are integers
    
        $sql = "
            SELECT *
            FROM " . BP_GROUP_DOCUMENTS_TABLE . "
            WHERE group_id IN (" . implode(',', $group_ids) . ")
        ";

        if ($featured && defined('BP_GROUP_DOCUMENTS_FEATURED') && BP_GROUP_DOCUMENTS_FEATURED) {
            $sql .= " AND featured = 1";
        }

        $sql .= "
            ORDER BY created_ts DESC
            LIMIT " . intval($num);
    
        // Execute query
        $results = $wpdb->get_results($sql, ARRAY_A);

           // Cache the results for 1 hour (3600 seconds)
        if ($results) {
            return $results;
        }

        return array();
    }
    
    /**
     * Creates the upload directory and an .htaccess file to protect it.
     *
     * @uses    wp_mkdir_p() to create the directory.
     * @uses    insert_with_markers() to create the .htaccess file with protection rules.
     * @since   version 0.5
     * @author  stergatu
     * @todo    Uncomment the insert_with_markers when ready.
     * @version 2, 13/5/2013
     */
    private function create_dir_or_htaccess( $dir )
    {
        // Validate that the directory path is safe
        if (! empty($dir) && strpos($dir, '..') === false && strpos($dir, './') === false ) {
            if (! file_exists($dir) ) {
                wp_mkdir_p($dir);
            }

            // If .htaccess doesn't exist, create it with protection rules
            if (! file_exists($dir . '/.htaccess') ) {
                $rules = array( 'Order Allow,Deny', 'Deny from all' );
                insert_with_markers($dir . '/.htaccess', 'Buddypress Group Documents plugin', $rules);
            }
        } else {
            // If the directory path is not safe, return false
            return false;
        }
    }
}

