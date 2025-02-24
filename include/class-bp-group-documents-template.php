<?php
// Exit if accessed directly
if (! defined('ABSPATH') ) {
    exit;
}

/**
 * Class BP_Group_Documents_Template
 *
 * Handles the logic for displaying and managing documents in a group,
 * including category filtering, sorting, pagination, and document details.
 */
class BP_Group_Documents_Template
{
    
    // Category Filtering
    public $category;
    public $parent_id;

    // Sorting
    public $order;
    private $sql_sort;
    private $sql_order;

    // Paging
    public $total_records;
    private $total_pages;
    private $page = 1;
    private $start_record = 1;
    private $end_record;
    private $items_per_page;

    // Misc
    public $action_link;

    // Top display - "list view"
    public $document_list;

    // Bottom display - "detail view"
    public $show_detail = 0;
    public $name = '';
    public $description = '';
    public $group_categories = array();
    public $doc_categories = array();
    public $operation = 'add';
    public $featured;
    public $id = '';
    public $header;


    /**
     * Constructor for BP_Group_Documents_Template
     *
     * Initializes necessary variables and performs actions to prepare the document list.
     */
    public function __construct()
    {
        $bp=buddypress();
        // Get parent category ID (used in category logic)
        $this->parent_id = self::get_parent_category_id();

        // Perform logic for posting, URL, categories, sorting, and paging
        $this->do_post_logic();
        $this->do_url_logic();
        $this->do_category_logic();
        $this->do_sorting_logic();
        $this->do_paging_logic();

        // Fetch the document list based on current group, filters, and settings
        $this->document_list = BP_Group_Documents::get_list_by_group(
            $bp->groups->current_group->id,
            $this->category,
            $this->sql_sort,
            $this->sql_order,
            $this->start_record,
            $this->items_per_page
        );
    }

    
    /**
     * Retrieves the parent category ID for the current group.
     *
     * If the parent category doesn't exist, it creates one using the group ID as part of the category name.
     *
     * @return int The term ID of the parent category.
     */
    public static function get_parent_category_id()
    {
        $bp = buddypress();

        // Generate the category name based on the current group's ID
        $category_name = 'g' . $bp->groups->current_group->id;
    
        // Check if the category already exists
        $parent_info = term_exists($category_name, 'group-documents-category');

        // If the category doesn't exist, create it
        if (!$parent_info) {
            $parent_info = wp_insert_term(
                $category_name, // Category name
                'group-documents-category', // Taxonomy
                array(
                'slug' => sanitize_title($category_name) // Ensure the slug is sanitized
                )
            );
        }

        // Return the term ID, ensuring it's an integer
        return isset($parent_info['term_id']) ? (int) $parent_info['term_id'] : 0;
    }


    /**
     * Processes document submissions and updates based on POST data.
     *
     * This function checks if the user has submitted a new document or updated an existing one.
     * It verifies nonces for security, sanitizes inputs, and processes the appropriate actions
     * (adding or editing documents). It also handles featured status and category updates.
     *
     * @version 1.3 3/10/2022 - Removed get_magic_quotes_gpc check for PHP > 7.x.
     * @version 1.2.2 3/10/2013 - Added sanitize_text_field and wp_verify_nonce for improved security.
     * @since   1.11 - Fixed security issues.
     */
    private function do_post_logic()
    {
        $bp = buddypress();
        // Check if there is a document operation in the POST request
        if (isset($_POST['bp_group_documents_operation'])) {

            // Verify nonce for security
            $nonce = isset($_POST['bp_group_document_save_nonce']) ? $_POST['bp_group_document_save_nonce'] : '';
            if (!wp_verify_nonce($nonce, 'bp_group_document_save_' . $_POST['bp_group_documents_operation'])) {
                 bp_core_add_message(esc_html__('Invalid nonce. Possible duplicate submission or security issue.', 'bp-group-documents'), 'error');
                return false;
            }

            do_action('bp_group_documents_template_do_post_action');

            if (( function_exists('get_magic_quotes_gpc') ) && ( get_magic_quotes_gpc() ) ) {
                $_POST = array_map('stripslashes_deep', $_POST);
            }
            // Featured document flag (default to '0')
            $bp_group_documents_featured = '0';
            if (array_key_exists('bp_group_documents_featured', $_POST)) {
                $bp_group_documents_featured = sanitize_text_field(wp_unslash($_POST['bp_group_documents_featured']));
            }

              // Switch based on operation type: 'add' or 'edit'
    
            switch ($_POST['bp_group_documents_operation']) {
            case 'add':
                $document = new BP_Group_Documents();
                if ($document->current_user_can('add') ) {
                    $document->user_id  = get_current_user_id();
                    $document->group_id = $bp->groups->current_group->id;
                    $document->name     = sanitize_text_field(wp_unslash($_POST['bp_group_documents_name']));
                    $document->description = wp_filter_post_kses(wpautop($_POST['bp_group_documents_description']));
                    $document->featured = apply_filters('bp_group_documents_featured_in', $bp_group_documents_featured);
          
                    // Save the document and update categories
                    if ($document->save()) {
                        self::update_categories($document);
                        do_action('bp_group_documents_add_success', $document);
                        bp_core_add_message(esc_html__('Document successfully uploaded', 'bp-group-documents'));
                        // Ανακατεύθυνση στη σελίδα της ομάδας ή σε άλλη προσαρμοσμένη σελίδα
                        $buddypress_version = bp_get_version(); // Get the BuddyPress version
                        $compare_version = '12.0.0'; // The version to compare against
                        // Compare the versions
                        if (version_compare($buddypress_version, $compare_version, '>=') ) {
                            wp_redirect(bp_get_group_url() . 'documents');
                        } else {
                             wp_redirect(bp_get_group_permalink()  . 'documents');
                        }
                 
                    }
                } else {
                    bp_core_add_message(esc_html__('There was a security problem', 'bp-group-documents'), 'error');
                    return false;
                }
                break;
            case 'edit':
                //                $document = new BP_Group_Documents($_POST['bp_group_documents_id']);
                // Check if document ID is set and sanitize it
                if (isset($_POST['bp_group_documents_id']) && ! empty($_POST['bp_group_documents_id']) ) {
                    // Sanitize the input before using it
                    $document_id = sanitize_text_field(wp_unslash($_POST['bp_group_documents_id']));
            
                    $document = new BP_Group_Documents($document_id); // Pass the sanitized ID to the document constructor   
                } else {
                    bp_core_add_message(esc_html__('Invalid document ID', 'bp-group-documents'), 'error');
                    return false;
                }
                if ($document->current_user_can('edit') ) {
                    $document->name = sanitize_text_field(wp_unslash($_POST['bp_group_documents_name']));
                    $document->description = wp_filter_post_kses(wpautop(wp_unslash($_POST['bp_group_documents_description'])));
                    $document->featured = apply_filters('bp_group_documents_featured_in', $bp_group_documents_featured);
                    // Update categories and save the document
                    self::update_categories($document);
                    if ($document->save() ) {
                        do_action('bp_group_documents_edit_success', $document);
                        bp_core_add_message(esc_html__('Document successfully edited', 'bp-group-documents'));
                    }
                } else {
                    bp_core_add_message(esc_html__('There was a security problem', 'bp-group-documents'), 'error');
                    return false;
                }
                break;
            } //end switch
        } //end if operation
    }

    
    
    /**
     * Updates the categories assigned to a document.
     *
     * This function removes existing categories and assigns new categories based on the
     * submitted form data. It also handles the creation of a new category if specified.
     *
     * @param object $document The document object containing the document's ID.
     */
    private function update_categories($document)
    {
        // Remove existing categories from the document
        wp_set_object_terms($document->id, null, 'group-documents-category');

        // Update categories from checkbox list if present
        if (array_key_exists('bp_group_documents_categories', $_POST)) {
            $category_ids = apply_filters('bp_group_documents_category_ids_in', array_map('absint', (array) $_POST['bp_group_documents_categories'])); // Sanitize category IDs
            wp_set_object_terms($document->id, $category_ids, 'group-documents-category');
        }

        // Check if a new category was added
        if (!empty($_POST['bp_group_documents_new_category'])) {
            $new_category = sanitize_text_field(wp_unslash($_POST['bp_group_documents_new_category'])); // Sanitize new category input

            // Check if the new category already exists
            if (!term_exists($new_category, 'group-documents-category', $this->parent_id)) {
                // Insert the new category under the parent category
                $term_info = wp_insert_term($new_category, 'group-documents-category', array('parent' => $this->parent_id));

                // Assign the newly created category to the document
                if (!is_wp_error($term_info) && isset($term_info['term_id'])) {
                    wp_set_object_terms($document->id, $term_info['term_id'], 'group-documents-category', true);
                }
            }
        }
    }


    /**
     *
     * @version 1.2.2 add security, fix misplayed error messages
     * v1.2.1, 1/8/2013, stergatu, implement direct call to  add document functionality
     * @since   version 0.8
     */
    private function do_url_logic()
    {
        $bp = buddypress();
        do_action('bp_group_documents_template_do_url_logic');

        //figure out what to display in the bottom "detail" area based on url
        //assume we are adding a new document
        $document = new BP_Group_Documents();

        if ($document->current_user_can('add') ) {
            $this->header      = esc_html__('Upload a New Document', 'bp-group-documents');
            $this->show_detail = 1;
        }
        //if we're editing, grab existing data
        //
        if (( BP_GROUP_DOCUMENTS_SLUG === $bp->current_action ) ) {
            if (count($bp->action_variables) > 0 ) {
                //stergatu add on 1/8/2013
                //implement direct call to  document file functionality
                if ('add' === $bp->action_variables[0] ) {
                    if ($document->current_user_can('add') ) {
                        ?>
                        <script language="javascript">
                            jQuery(document).ready(function ($) {
                                $('#bp-group-documents-upload-button').slideUp();
                                $('#bp-group-documents-upload-new').slideDown();
                                $('html, body').animate({
                                    scrollTop: $("#bp-group-documents-upload-new").offset().top
                                }, 2000);
                            });
                        </script>
                        <?php
                    } else {
                        bp_core_add_message(esc_html__("You don't have permission to upload files", 'bp-group-documents'), 'error');
                    }
                }
                if (count($bp->action_variables) > 1 ) {
                    $document = new BP_Group_Documents($bp->action_variables[1]);
                    if ('edit' === $bp->action_variables[0] ) {
                        if ($document->current_user_can('edit', bp_get_current_group_id()) ) {
                            if (! wp_verify_nonce($_REQUEST['_wpnonce'], 'group-documents-edit-link') ) {
                                bp_core_add_message(esc_html__('There was a security problem', 'bp-group-documents'), 'error');
                                return false;
                            }
                            if (! ctype_digit($bp->action_variables[1]) ) {
                                bp_core_add_message(esc_html__('The item to edit could not be found', 'bp-group-documents'), 'error');
                                return false;
                            }

                            if (ctype_digit($bp->action_variables[1]) ) {
                                $this->show_detail    = 1;
                                $this->name           = apply_filters('bp_group_documents_name_out', $document->name);
                                $this->description    = apply_filters('bp_group_documents_description_out', $document->description);
                                $this->featured       = apply_filters('bp_group_documents_featured_out', $document->featured);
                                $this->doc_categories = wp_get_object_terms($document->id, 'group-documents-category');
                                $this->operation      = 'edit';
                                $this->id             = $bp->action_variables[1];
                                $this->header         = esc_html__('Edit Document', 'bp-group-documents');
                            }
                            //otherwise, we might be deleting
                        }
                    }
                    if ('delete' === $bp->action_variables[0] ) {
                        if ($document->current_user_can('delete', bp_get_current_group_id()) ) {
                            if (! ctype_digit($bp->action_variables[1]) ) {
                                bp_core_add_message(esc_html__('The item to delete could not be found', 'bp-group-documents'), 'error');
                                return false;
                            }
                            if (bp_group_documents_delete($bp->action_variables[1]) ) {
                                bp_core_add_message(esc_html__('Document successfully deleted', 'bp-group-documents'));
                            }
                        } else {
                            bp_core_add_message(esc_html__("You don't have permission to delete the file", 'bp-group-documents'), 'error');
                            return false;
                        }
                    }
                }
            }
        }
    }

    /**
     *
     * @param  int $cat_id
     * @return boolean
     */
    public function doc_in_category( $cat_id )
    {
        foreach ( $this->doc_categories as $doc_category ) {
            if ($doc_category->term_id === $cat_id ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @version 2.0, 11/11/2022
     */
    private function do_category_logic()
    {
        do_action('bp_group_documents_template_do_category_logic');

        // 1st priority: Category from URL
        if (isset($_GET['bpgrd-category'])) {
              $this->category = sanitize_text_field(wp_unslash($_GET['bpgrd-category'])); // Sanitize URL parameter
        }
        // 2nd priority: Category from cookies
        elseif (isset($_COOKIE['bp-group-documents-category'])) {
             $this->category = sanitize_text_field(wp_unslash($_COOKIE['bp-group-documents-category'])); // Sanitize cookie value
        }
        // No category specified, show all categories
        else {
            $this->category = false;
        }

        // Retrieve group categories
        $this->group_categories = self::get_group_categories($this->parent_id);
    }


    /**
     * Retrieves group's documents categories based on the parent category ID.
     *
     * @param  bool $not_empty Whether to exclude empty categories. Default is true.
     * @return WP_Term[]|int[]|WP_Error An array of WP_Term objects or term IDs, or a WP_Error on failure.
     */
    public static function get_group_categories($not_empty = true)
    {
        $parent_id = self::get_parent_category_id();

        if ($not_empty) {
            $args = array(
            'taxonomy' => 'group-documents-category',
            'parent'   => $parent_id,
            );
        } else {
            $args = array(
            'taxonomy'   => 'group-documents-category', // Taxonomy is now part of the $args array
            'parent'     => $parent_id,
            'hide_empty' => false,
            );
        }

        return get_terms($args);
    }

    private function do_sorting_logic()
    {
        do_action('bp_group_documents_template_do_sorting_logic');
        //1st priority, order is in url.  Store in cookie as well
        if (isset($_GET['bpgrd-order']) ) {
            $this->order = sanitize_text_field(wp_unslash($_GET['bpgrd-order']));
            //order wasn't in url, check for cookies
        } elseif (isset($_COOKIE['bp-group-documents-order']) ) {
            $this->order = sanitize_text_field(wp_unslash($_COOKIE['bp-group-documents-order']));
            //no order to be found, use default, and put in cookie
        } else {
            $this->order = 'newest';
        }

        switch ( $this->order ) {
        case 'newest':
            $this->sql_sort  = 'created_ts';
            $this->sql_order = 'DESC';
            break;
        case 'alpha':
            $this->sql_sort  = 'name';
            $this->sql_order = 'ASC';
            break;
        case 'popular':
            $this->sql_sort  = 'download_count';
            $this->sql_order = 'DESC';
            break;
        default:// default to newest
            $this->sql_sort  = 'created_ts';
            $this->sql_order = 'DESC';
            break;
        }
    }

    /**
     * @author
     * @since
     */
    private function do_paging_logic()
    {
        $bp = buddypress();

        do_action('bp_group_documents_template_do_paging_logic');

        $this->items_per_page = get_option('bp_group_documents_items_per_page');

        $this->total_records = BP_Group_Documents::get_total($bp->groups->current_group->id, $this->category);

        $this->total_pages = ceil($this->total_records / $this->items_per_page);

        if (isset($_GET['page']) && ctype_digit($_GET['page']) ) {
            $this->page         = $_GET['page'];
            $this->start_record = ( ( $this->page - 1 ) * $this->items_per_page ) + 1;
        }
        $last_possible    = $this->items_per_page * $this->page;
        $this->end_record = ( $this->total_records < $last_possible ) ? $this->total_records : $last_possible;

        $this->action_link = get_bloginfo('url') . '/' . bp_get_groups_root_slug() . '/' . $bp->current_item . '/' . $bp->current_action . '/';
    }

    
    /**
     * Displays the pagination count, showing the range of items being viewed and the total number of items.
     *
     * This function prints the pagination count in the format:
     * "Viewing item X to Y (of Z items)" where X is the start record,
     * Y is the end record, and Z is the total number of items.
     *
     * @since 1.0.0
     */
    public function pagination_count()
    {
        // Ensure that the necessary values (start, end, total) are set
        if (isset($this->start_record, $this->end_record, $this->total_records) ) {
            printf(
            // Translators: This text shows the current range of items being viewed (start to end) and the total item count. %1$s: Start record, %2$s: End record, %3$s: Total records
                esc_html__('Viewing item %1$s to %2$s (of %3$s items)', 'bp-group-documents'),
                esc_html($this->start_record),  // Escapes the start record value
                esc_html($this->end_record),    // Escapes the end record value
                esc_html($this->total_records)  // Escapes the total records count
            );
        } else {
            // Fallback message if required values are not set
            // Translator comment: This is a fallback message when no items are available to display.
            esc_html_e('No items to display', 'bp-group-documents');
        }
    }

    /**
     * Outputs pagination links for navigation.
     */
    public function pagination_links()
    {
        // Check if we are not on the first page
        if (1 !== $this->page) {
            // Translators: This is the "Previous" page navigation symbol.
            // &laquo; represents the left-pointing double angle quotation mark ("<<" symbol).
            printf(
                '<a class="page-numbers prev" href="%s">%s</a>',
                esc_url(add_query_arg('page', absint($this->page - 1), $this->action_link)), // Securely appending the page parameter
                esc_html(__('&laquo;', 'bp-group-documents')) // Escaping and marking as translatable
            );
        }

        // Loop through each page and generate the pagination links
        for ($i = 1; $i <= $this->total_pages; $i++) {
            if ($this->page === $i) {
                // Translators: Indicates the current page number in pagination. %d is the current page number.
                printf(
                    '<span class="page-numbers current">%d</span>',
                    absint($i) // Ensures the current page number is an integer
                );
            } else {
                // Translators: This link navigates to a specific page in pagination. %d is the page number.
                printf(
                    '<a class="page-numbers" href="%s">%d</a>',
                    esc_url(add_query_arg('page', absint($i), $this->action_link)), // Securely appending the page parameter
                    absint($i) // Ensures the page number is an integer and properly displayed
                );
            }
        }

        // Check if we are not on the last page
        if ($this->total_pages !== $this->page) {
            // Translators: This is the "Next" page navigation symbol.
            // &raquo; represents the right-pointing double angle quotation mark (">>" symbol).
            printf(
                '<a class="page-numbers next" href="%s">%s</a>',
                esc_url(add_query_arg('page', absint($this->page + 1), $this->action_link)), // Securely appending the page parameter
                esc_html(__('&raquo;', 'bp-group-documents')) // Escaping and marking as translatable
            );
        }
    }


    /**
     *
     * @return type
     */
    public function show_pagination()
    {

        return ( $this->total_pages > 1 );
    }

    /**
     * Displays an "Add new" button
     * 
     * @version 2.0 17/2/2025, compatibility BP 12+
     */
    public function show_add_new_button()
    {
        if (is_user_logged_in() ) {
            $document = new BP_Group_Documents();
            if ($document->current_user_can('add', bp_get_current_group_id()) ) {
                $buddypress_version = bp_get_version(); // Get the BuddyPress version
                $compare_version = '12.0.0'; // The version to compare against
                // Compare the versions
                if (version_compare($buddypress_version, $compare_version, '>=') ) {
                    $url = bp_get_group_url() . BP_GROUP_DOCUMENTS_SLUG . '/add';
                } else {
                    $url = bp_get_group_permalink() . BP_GROUP_DOCUMENTS_SLUG . '/add';
                }
                ?>
                <div>
                    <a href="<?php echo esc_url($url); ?>" class="button"><?php esc_html_e('Add New', 'bp-group-documents'); ?></a>
                </div>
                <?php
            }
        }
    }

}
