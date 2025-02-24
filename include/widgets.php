<?php
/**
 * @version 2.0
 */

// Exit if accessed directly
if (! defined('ABSPATH') ) {
    exit;
}

/**
 * Register the BP Group documents widgets.
 *
 * @version 2, 22/4/2014
 */
function bp_group_documents_register_widgets()
{
    if (! bp_is_active('groups') ) {
        return;
    }
    register_widget('BP_Group_Documents_Newest_Widget');
    register_widget('BP_Group_Documents_Popular_Widget');
    register_widget('BP_Group_Documents_Usergroups_Widget');

    if (( is_active_widget(false, false, 'bp_group_documents_newest_widget') ) || ( is_active_widget(false, false, 'bp_group_documents_popular_widget') ) ) {
        add_action('wp_enqueue_scripts', 'bp_group_documents_add_my_stylesheet');
    }
    // The BP_Group_Documents_CurrentGroup widget works only when looking at a group page,
    // and the concept of "current group " doesn't exist on non-root blogs,
    // so we don't register the widget there.
    if (! bp_is_root_blog() ) {
        return;
    }
    register_widget('BP_Group_Documents_CurrentGroup_Widget');

}

add_action('widgets_init', 'bp_group_documents_register_widgets');

/**
 * Enqueue plugin style-file
 */
function bp_group_documents_add_my_stylesheet()
{
    // Respects SSL, Style.css is relative to the current file
    wp_register_style('bp-group-documents', plugins_url(BP_GROUP_DOCUMENTS_DIR) . '/css/style.css', false, BP_GROUP_DOCUMENTS_VERSION);
    wp_enqueue_style('bp-group-documents');
}

/**
 * @version 3.0, 17/2/2025, lenasterg
 */
class BP_Group_Documents_Newest_Widget extends WP_Widget
{
    var $bp_group_documents_name;

    public function __construct()
    {
        $nav_page_name                 = get_option('bp_group_documents_nav_page_name');
        $this->bp_group_documents_name = mb_convert_case(! empty($nav_page_name) ? $nav_page_name : esc_html__('Documents', 'bp-group-documents'), MB_CASE_LOWER);
        parent::__construct(
            'bp_group_documents_newest_widget',
            '(BP Group Documents) ' . sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('Recent Group %s', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ), // Name
            array(
            'description' => sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('The most recently uploaded group %s. Only from public groups', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ),
            'classname'   => 'bp_group_documents_widget',
            ) // Args
        );
    }

    /**
     * 
     * @version 4.0 17/2/2025, compatibility BP 12+
     * 3, 22/4/2014 add sanitize_text_field
     * v2, 1/5/2013, stergatu
     */
    public function widget( $args, $instance )
    {
        $buddypress_version = bp_get_version(); // Get the BuddyPress version
        $compare_version = '12.0.0'; // The version to compare against
        
        $title = apply_filters(
            'widget_title', 
            empty($instance['title']) ? 
            sprintf(
                  // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('Recent Group %s',                 'bp-group-documents'),
                esc_html($this->bp_group_documents_name)
            ) 
            : 
             sanitize_text_field(wp_unslash($instance['title']))
        );

        echo wp_kses_post($args['before_widget'].$args['before_title'] . $title . $args['after_title']);
         
        do_action('bp_group_documents_newest_widget_before_html');

        $defaults = array(
        'download_count' => true,
        'featured'       => false,
        'group_filter'   => 0,
        'num_items'      => 5,
        'title'          => sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('Recent Group %s', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
        ),
        );

        $instance = wp_parse_args((array) $instance, $defaults);

        $document_list = BP_Group_Documents::get_list_for_newest_widget(absint($instance['num_items']), $instance['group_filter'], (bool) $instance['featured']);
        if ($document_list && count($document_list) >= 1 ) {
            echo '<ul id="bp-group-documents-recent" class="bp-group-documents-list" >';
            foreach ( $document_list as $item ) {
                $document = new BP_Group_Documents($item['id']);
                $group    = new BP_Groups_Group($document->group_id);
                echo '<li>';
                if (get_option('bp_group_documents_display_icons') ) {
                    $document->icon();
                }
                ?>
                <a class="bp-group-documents-title" id="group-document-link-<?php echo esc_attr($document->id); ?>"   href="<?php $document->url(); ?>" target="_blank">
                <?php echo esc_html(stripslashes($document->name)); ?>
</a>

                <?php
                if (! $instance['group_filter'] ) {
                    // Compare the versions
                    if (version_compare($buddypress_version, $compare_version, '>=') ) {
                        
                        echo sprintf(
                        /* Translators: %s: Group name */
                            esc_html__('posted in %s', 'bp-group-documents'), '<a href="' . esc_url(bp_get_group_url($group)) . '">' . esc_attr($group->name) . '</a>'
                        );
                    } else {
                        echo sprintf(
                        /* Translators: %s: Group name */
                            esc_html__('posted in %s', 'bp-group-documents'), '<a href="' . esc_url(bp_get_group_permalink($group)) . '">' . esc_attr($group->name) . '</a>'
                        );
                    }
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="widget-error">' . sprintf(
            /* translators: %s: Document type name */
                esc_html__('There are no %s to display.', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ) . '</div></p>';
        }
        echo wp_kses_post($args['after_widget']);
    }

    /**
     *
     * @param  type $new_instance
     * @param  type $old_instance
     * @return type
     * @todo,  25/4/2013, lenasterg, add functionality for documents_category selection (minor)
     */
    public function update( $new_instance, $old_instance )
    {
        do_action('bp_group_documents_widget_update');

        $default_title = sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('Recent Group %s', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
        );

        $new_title =  sanitize_text_field(wp_unslash($new_instance['title']));

        $instance                 = $old_instance;
        $instance['title']        = empty($new_title) ?  sanitize_text_field(wp_unslash($default_title)) : $new_title;
        $instance['group_filter'] = absint($new_instance['group_filter']);
        $instance['featured']     = intval((bool) $new_instance['featured']);
        $instance['num_items']    = empty($num_items) ? 5 : absint($new_instance['num_items']);

        return $instance;
    }

    /**
     *
     * @param   type $instance
     * @todo,   25/4/2013, stergatu, add functionality for documents_category selection (minor)
     * @version 2.0
     */
    public function form( $instance )
    {
        do_action('bp_group_documents_newest_widget_form');

        $defaults = array(
        'download_count' => true,
        'featured'       => false,
        'group_filter'   => 0,
        'num_items'      => 5,
        'title'          => sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('Recent Group %s', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
        ),
        );

        $instance     = wp_parse_args((array) $instance, $defaults);
        $title        = esc_attr($instance['title']);
        $group_filter = absint($instance['group_filter']);
        $featured     = (bool) $instance['featured'];
        $num_items    = empty($instance['num_items']) ? 5 : absint($instance['num_items']);
        ?>
    <p>
        <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
            <?php esc_html_e('Title:', 'bp-group-documents'); ?>
        </label>
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
               name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
               type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
        <?php if (BP_GROUP_DOCUMENTS_WIDGET_GROUP_FILTER) { ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('group_filter')); ?>">
                <?php esc_html_e('Filter by Group:', 'bp-group-documents'); ?>
            </label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('group_filter')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('group_filter')); ?>">
                <option value="0"><?php esc_html_e('Select Group...', 'bp-group-documents'); ?></option>
                <?php
                $groups_list = BP_Groups_Group::get(array( 'type' => 'alphabetical' ));
                foreach ($groups_list['groups'] as $group) {
                    echo '<option value="' . esc_attr($group->id) . '" ';
                    selected($group->id, $group_filter);
                    echo '>' . esc_html(stripslashes($group->name)) . '</option>';
                }
                ?>
            </select>
        </p>
        <?php }
        if (BP_GROUP_DOCUMENTS_FEATURED) { ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('featured')); ?>">
                    <?php printf(
                        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                        esc_html__('Show featured %s only', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
                    ); ?>
            </label>
            <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('featured')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('featured')); ?>" 
                   value="1" <?php checked($featured); ?> />
        </p>
        <?php } ?>
    <p>
        <label for="<?php echo esc_attr($this->get_field_id('num_items')); ?>">
             <?php esc_html_e('Number of items to show:', 'bp-group-documents'); ?>
        </label>
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('num_items')); ?>" 
               name="<?php echo esc_attr($this->get_field_name('num_items')); ?>" 
               type="text" value="<?php echo esc_attr($num_items); ?>" style="width: 30%;" />
    </p>
        <?php
    }
}

/**
 * @version 4, 17/2/2025, lenasterg
 */
class BP_Group_Documents_Popular_Widget extends WP_Widget
{

    var $bp_group_documents_name;

    public function __construct()
    {
        $bp                            = buddypress();
        $nav_page_name                 = get_option('bp_group_documents_nav_page_name');
        $this->bp_group_documents_name = mb_convert_case(! empty($nav_page_name) ? $nav_page_name : esc_html__('Documents', 'bp-group-documents'), MB_CASE_LOWER);
        parent::__construct(
            'bp_group_documents_popular_widget',
            '(BP Group Documents) ' . sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('Popular Group %s', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ), // Name
            array(
            'description' => sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('The most commonly downloaded group %s. Only for public groups', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ),
            'classname'   => 'bp_group_documents_widget',
            )
        );

    }

    /**
     * 
     * @param type $args
     * @param type $instance
     * 
     * @version 2.0 17/2/2025, compatibility BP 12+
     */
    function widget( $args, $instance )
    {
        $buddypress_version = bp_get_version(); // Get the BuddyPress version
        $compare_version = '12.0.0'; // The version to compare against
    
     
        $title = apply_filters(
            'widget_title', 
            empty($instance['title']) ? 
            sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('Popular Group %s', 'bp-group-documents'),
                esc_html($this->bp_group_documents_name)
            ) 
            : 
             sanitize_text_field(wp_unslash($instance['title']))
        );
    
        echo wp_kses_post($args['before_widget'].$args['before_title'] . $title . $args['after_title']);
        do_action('bp_group_documents_popular_widget_before_html');

        /*         * *
        * Main HTML Display
        */

        $defaults = array(
        'download_count' => true,
        'featured'       => false,
        'group_filter'   => 0,
        'num_items'      => 5,
        'title'          => sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('Popular Group %s', 'bp-group-documents'),
            esc_html($this->bp_group_documents_name)
        ),
        );

        $instance = wp_parse_args((array) $instance, $defaults);

        $document_list = BP_Group_Documents::get_list_for_popular_widget(absint($instance['num_items']), $instance['group_filter'], (bool) $instance['featured']);

        if ($document_list && count($document_list) >= 1 ) {
            echo '<ul id="bp-group-documents-recent" class="bp-group-documents-list">';
            foreach ( $document_list as $item ) {
                $document = new BP_Group_Documents($item['id']);
                $group    = new BP_Groups_Group($document->group_id);
                echo '<li>';
                if (get_option('bp_group_documents_display_icons') ) {
                    $document->icon();
                }
                ?>
<a class="bp-group-documents-title" id="group-document-link-<?php echo esc_attr($document->id); ?>" 
   href="<?php $document->url(); ?>" target="_blank">
                <?php echo esc_html(stripslashes($document->name)); ?>
</a>

                <br>
                <?php
                if (! $instance['group_filter'] ) {
                    // Compare the versions
                    if (version_compare($buddypress_version, $compare_version, '>=') ) {
                        /* translators: %s: Group name */
                        echo sprintf(esc_html__('posted in %s', 'bp-group-documents'), '<a href="' . esc_url(bp_get_group_url($group)) . '">' . esc_html($group->name) . '</a>.');
                    } else {
                        /* translators: %s: Group name */
                        echo sprintf(esc_html__('posted in %s', 'bp-group-documents'), '<a href="' . esc_url(bp_get_group_permalink($group)) . '">' . esc_html($group->name) . '</a>.');
                    }
                }
                if ($instance['download_count'] ) {
                    echo ' <span class="group-documents-download-count">' .
                    esc_html($document->download_count) . ' ' . esc_html__('downloads', 'bp-group-documents') .
                    '</span>';
                }
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="widget-error">' . sprintf(
            /* Translators: %s: The name of the document type (e.g., "documents") */
                esc_html__('There are no %s to display.', 'bp-group-documents'),
                esc_html($this->bp_group_documents_name)
            ) . '</div>';
        }
        echo wp_kses_post($args['after_widget']);
    }

    public function update( $new_instance, $old_instance )
    {
        do_action('bp_group_documents_newest_widget_update');

        $default_title = sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('Popular Group %s', 'bp-group-documents'),
            esc_html($this->bp_group_documents_name)
        );
    
        $new_title =  sanitize_text_field(wp_unslash($new_instance['title']));

        $instance                   = $old_instance;
        $instance['title']          = empty($new_title) ?  sanitize_text_field(wp_unslash($default_title)) : $new_title;
        $instance['group_filter']   = absint($new_instance['group_filter']);
        $instance['featured'] = !empty($new_instance['featured']) ? (bool)$new_instance['featured'] : false;
        $instance['num_items']      = empty($num_items) ? 5 : absint($new_instance['num_items']);
        $instance['download_count'] = !empty($new_instance['download_count']) ? (bool)$new_instance['download_count'] : false;

        return $instance;
    }

    function form( $instance )
    {
        do_action('bp_group_documents_newest_widget_form');

        $defaults = array(
        'download_count' => true,
        'featured'       => false,
        'group_filter'   => 0,
        'num_items'      => 5,
        'title'          => sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('Popular Group %s', 'bp-group-documents'),
            esc_html($this->bp_group_documents_name)
        ),
        );

        $instance       = wp_parse_args((array) $instance, $defaults);
        $title          = esc_attr($instance['title']);
        $group_filter   = absint($instance['group_filter']);
        $featured       = (bool) $instance['featured'];
        $num_items      = empty($instance['num_items']) ? 5 : absint($instance['num_items']);
        $download_count = (bool) $instance['download_count'];
        ?>
        <p><label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'bp-group-documents'); ?></label>
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
           name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p>
        <?php if (BP_GROUP_DOCUMENTS_WIDGET_GROUP_FILTER ) { ?>
            <p><label><?php esc_html_e('Filter by Group:', 'bp-group-documents'); ?></label>
                <select class="widefat" id="<?php echo esc_attr($this->get_field_id('group_filter')); ?>" name="<?php echo esc_attr($this->get_field_name('group_filter')); ?>" >
                    <option value="0"><?php esc_html_e('Select Group...', 'bp-group-documents'); ?></option>
            <?php
            $groups_list = BP_Groups_Group::get(array( 'type' => 'alphabetical' ));
            //                                get_alphabetically();
            foreach ( $groups_list['groups'] as $group ) {
                echo '<option value="' . esc_attr($group->id) . '" ';
                if ($group->id === $group_filter ) {
                    echo 'selected="selected"';
                }
                echo '>' . esc_html(stripslashes($group->name)) . '</option>';
            }
            ?>
                </select></p>
            <?php
        }
        if (BP_GROUP_DOCUMENTS_FEATURED ) {
            ?>
            <p><label for="<?php echo esc_attr($this->get_field_id('featured')); ?>">
            <?php printf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('Show featured %s only', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ); ?></label>
                <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('featured')); ?>"
               name="<?php echo esc_attr($this->get_field_name('featured')); ?>" value="1" 
                                                      <?php
                                                        checked($featured);
                                                        ?>
                >
            </p>
        <?php } ?>

        <p><label><?php esc_html_e('Number of items to show:', 'bp-group-documents'); ?></label> 
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('num_items')); ?>" 
           name="<?php echo esc_attr($this->get_field_name('num_items')); ?>" type="text" value="<?php echo absint($num_items); ?>" style="width: 30%" /></p>
        <p><input type="checkbox" id="<?php echo esc_attr($this->get_field_id('download_count')); ?>" name="<?php echo esc_attr($this->get_field_name('download_count')); ?>" value="1" 
                <?php
                if ($download_count ) {
                    echo 'checked="checked"';
                }
                ?>
            >
            <label><?php printf(esc_html__('Show downloads', 'bp-group-documents'), esc_html($this->bp_group_documents_name)); ?></label></p>
        <?php
    }

}

/**
 * @version 4, 22/4/2014, add sanitize_text_field
 * v3, 13/5/2013, lenasterg
 */
class BP_Group_Documents_Usergroups_Widget extends WP_Widget
{

    var $bp_group_documents_name;

    function __construct()
    {
        $bp                            = buddypress();
        $nav_page_name                 = get_option('bp_group_documents_nav_page_name');
        $this->bp_group_documents_name = ! empty($nav_page_name) ? $nav_page_name : esc_html__('Documents', 'bp-group-documents');
        parent::__construct(
            'bp_group_documents_usergroups_widget',
            '(BP Group Documents) ' . sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('%s in your groups', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ), // Name
            array(
            'description' => sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('%s for a logged in user\'s groups.', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ),
            'classname'   => 'bp_group_documents_widget',
            )
        );

       
    }

    /**
     *
     * @param  type $args
     * @param  type $instance
     * @return type
     * 
     * @version 2.0 17/2/2025, compatibility BP 12+
     */
    public function widget( $args, $instance )
    {
        //only show widget to logged in users
        if (! is_user_logged_in() ) {
            return;
        }
    
        $buddypress_version = bp_get_version(); // Get the BuddyPress version
        $compare_version = '12.0.0'; // The version to compare against
    
        //get the groups the user is part of
        $results = groups_get_user_groups(get_current_user_id());
        //don't show widget if user doesn't have any groups
        if (0 === $results['total'] ) {
            return;
        }
       

        $title = apply_filters(
            'widget_title', 
            empty($instance['title']) ? 
            sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('Recent %s from your Groups', 'bp-group-documents'),
                esc_html($this->bp_group_documents_name)
            ) 
            : $instance['title']
        );
        
        echo wp_kses_post($args['before_widget'].$args['before_title'] . esc_html($title) . $args['after_title']);
    
        do_action('bp_group_documents_usergroups_widget_before_html');

        $defaults      = array(
        'featured'  => false,
        'num_items' => 5,
        'title'     => sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('%s in your groups', 'bp-group-documents'),
            esc_html($this->bp_group_documents_name)
        ),
        );
        $instance      = wp_parse_args((array) $instance, $defaults);
        $document_list = BP_Group_Documents::get_list_for_usergroups_widget(absint($instance['num_items']), (bool) $instance['featured']);

        if ($document_list && count($document_list) >= 1 ) {
            echo '<ul id="bp-group-documents-usergroups" class="bp-group-documents-list">';
            foreach ( $document_list as $item ) {
                $document = new BP_Group_Documents($item['id']);
                $group    = new BP_Groups_Group($document->group_id);

                echo '<li>';

                if (get_option('bp_group_documents_display_icons')) {
                    $document->icon(); // Display the document icon if the option is enabled
                }
                ?>
<a class="bp-group-documents-title" id="group-document-link-<?php echo esc_attr($document->id); ?>" 
   href="<?php $document->url(); ?>" target="_blank">
                <?php echo esc_html(stripslashes($document->name)); // Escaping the document name for security ?>
</a>

                <?php
                // Compare the versions
                if (version_compare($buddypress_version, $compare_version, '>=') ) {
                    echo sprintf(
                    // Translators: %s is the group name, wrapped in an HTML anchor tag for linking to the group's page.
                        esc_html__('posted in %s', 'bp-group-documents'),
                        '<a href="' . esc_url(bp_get_group_url($group)) . '">' . esc_html($group->name) . '</a>'
                    );
                } else {
                        echo sprintf(
                    // Translators: %s is the group name, wrapped in an HTML anchor tag for linking to the group's page.
                            esc_html__('posted in %s', 'bp-group-documents'), '<a href="' . esc_url(bp_get_group_permalink($group)) . '">' . esc_html($group->name) . '</a>'
                        );
                }

                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo '<div class="widget-error">' . sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('There are no %s to display.', 'bp-group-documents'),
                esc_html($this->bp_group_documents_name)
            ) . '</div></p>';
        }
        echo wp_kses_post($args['after_widget']);
    }

    /**
     *
     * @param   type $new_instance
     * @param   type $old_instance
     * @return  type
     * @version 2.0 9/4/2019
     */
    function update( $new_instance, $old_instance )
    {
        do_action('bp_group_documents_usergroups_widget_update');

        $default_title = sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('%s in your groups', 'bp-group-documents'),
            esc_html($this->bp_group_documents_name)
        );

        $new_title =  sanitize_text_field(wp_unslash($new_instance['title']));
        $num_items = empty($new_instance['num_items']) ? 5 : absint($new_instance['num_items']);

        $instance          = $old_instance;
        $instance['title'] = empty($new_title) ?  sanitize_text_field(wp_unslash($default_title)): $new_title;
        if (array_key_exists('featured', $new_instance) ) {
            $instance['featured'] = intval((bool) $new_instance['featured']);
        } else {
            $instance['featured'] = false;
        }
        $instance['num_items'] = empty($num_items) ? 5 : absint($new_instance['num_items']);

        return $instance;
    }

    function form( $instance )
    {
        do_action('bp_group_documents_usergroups_widget_form');

        $defaults = array(
        'featured'  => false,
        'num_items' => 5,
        'title'     => sprintf(
        // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
            esc_html__('%s in your groups', 'bp-group-documents'),
            esc_html($this->bp_group_documents_name)
        ),
        );

        $instance  = wp_parse_args((array) $instance, $defaults);
        $title     = esc_attr($instance['title']);
        $featured  = (bool) $instance['featured'];
        $num_items = empty($instance['num_items']) ? 5 : absint($instance['num_items']);
        ?>

        <p><label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('Title:', 'bp-group-documents'); ?></label>
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
           name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
           type="text" value="<?php echo esc_attr($title); ?>" /></p>
        <?php if (BP_GROUP_DOCUMENTS_FEATURED ) { ?>
            <p><label for="<?php echo esc_attr($this->get_field_id('featured')); ?>">
            <?php printf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('Show featured %s only', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ); ?>
        </label> 
        <input type="checkbox" id="<?php echo esc_attr($this->get_field_id('featured')); ?>"
               name="<?php echo esc_attr($this->get_field_name('featured')); ?>" value="1"           <?php
                checked($featured);
                ?>
                >
            </p>
        <?php } ?>

        <p>
        <label for="<?php echo esc_attr($this->get_field_id('num_items')); ?>"><?php esc_html_e('Number of items to show:', 'bp-group-documents'); ?></label>
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('num_items')); ?>" 
           name="<?php echo esc_attr($this->get_field_name('num_items')); ?>" 
           type="text" value="<?php echo esc_attr($num_items); ?>" style="width: 30%" />
    </p>
        <?php
    }

}

/**
 * Current displayed group documents widget
 *
 * @version 2, 17/2/2025, lenasterg
 */
class BP_Group_Documents_CurrentGroup_Widget extends WP_Widget
{
    var $bp_group_documents_name;

    public function __construct()
    {
        $nav_page_name                 = get_option('bp_group_documents_nav_page_name');
        $this->bp_group_documents_name = ! empty($nav_page_name) ? $nav_page_name : esc_html__('Documents', 'bp-group-documents');
        parent::__construct(
            'bp_group_documents_current_group_widget',
            '(BP Group Documents) ' . sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('%s in this group', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ), // Name
            array(
            'description' => sprintf(
            // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                esc_html__('%s for the current group.', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
            ),
            'classname'   => 'bp_group_documents_widget',
            )
        );

    }

    /**
     *
     * @param   type  $args
     * @param   array $instance
     * @version 6.0 17/2/2025, compatibility BP 12+
     */
    public function widget( $args, $instance )
    {
        $bp = buddypress();

        $instance['group_id'] = bp_get_current_group_id();

        if ($instance['group_id'] > 0 ) {
            $buddypress_version = bp_get_version(); // Get the BuddyPress version
            $compare_version = '12.0.0'; // The version to compare against
    
            $group = $bp->groups->current_group;

            // If the group  public, or the user is super_admin or the user is member of group
            if (( 'public' === $group->status ) || ( is_super_admin() ) || ( groups_is_user_member(bp_loggedin_user_id(), $instance['group_id']) ) ) {

                $defaults = array(
                 'featured'  => false,
                 'num_items' => 5,
                'title'     => sprintf(
                // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                    esc_html__('Recent %s for the group', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
                ),
                );

                $instance = wp_parse_args((array) $instance, $defaults);
                

                $title = apply_filters(
                    'widget_title', 
                    empty($instance['title']) ? 
                    sprintf(
                    // Translators: %s will be replaced with the name of the document type, e.g., "Documents".
                        esc_html__('Recent %s for the group', 'bp-group-documents'),
                        esc_html($this->bp_group_documents_name)
                    ) 
                    : 
                    $instance['title']
                );
                
                
                echo wp_kses_post($args['before_widget'].$args['before_title'] . $title . $args['after_title']);
                do_action('bp_group_documents_current_group_widget_before_html');

                $document_list = BP_Group_Documents::get_list_for_newest_widget(absint($instance['num_items']), $instance['group_id'], (bool) $instance['featured']);
                if ($document_list && count($document_list) >= 1 ) {
                    $show_icon       = false;
                    $show_size       = false;
                    $show_owner      = false;
                    $show_date       = false;
                    $show_categories = false;
                    $show_download   = false;
                    if (get_option('bp_group_documents_display_icons') ) {
                        $show_icon = true;
                    }
                    if (get_option('bp_group_documents_display_file_size') ) {
                        $show_size = true;
                    }
                    if (get_option('bp_group_documents_display_owner') ) {
                                  $show_owner = true;
                    }
                    if (get_option('bp_group_documents_display_date') ) {
                        $show_date = true;
                    }
                    if (get_option('bp_group_documents_use_categories') ) {
                        $show_categories = true;
                    }

                    if (get_option('bp_group_documents_display_download_count') ) {
                        $show_download = true;
                    }

                    echo '<ul id="bp-group-documents-current-group" class="bp-group-documents-list">';
                    foreach ( $document_list as $item ) {
                        // Create a new document object based on the item ID
                        $document = new BP_Group_Documents($item['id']);

                        echo '<li>';
                        // Check if the option to display icons is enabled
                        if (get_option('bp_group_documents_display_icons') ) {
                            $document->icon();
                        }
                        ?>
    <a class="bp-group-documents-title" 
   id="group-document-link-<?php echo esc_attr($document->id); ?>" 
   href="<?php $document->url(); ?>" 
   target="_blank">
                        <?php
                        // Translators: This is the title of the document being displayed. %s is the document name.
                        echo esc_html(stripslashes($document->name)); // Safely output the document name after removing backslashes.
                        ?>
</a>

                            <?php
                            if (true === $show_size ) {
                                        echo ' <span class="group-documents-filesize">(' . esc_html(get_file_size($document)) . ')</span>';
                            }
                            ?>
                            </a> &nbsp;<div class="bp-group-documents-meta">
                            <?php
                            if (true === $show_categories ) {
                                $document->categories();
                            }
                            if ($show_owner && $show_date ) {
                                printf(
                                // Translators:  %1$s: Name or link to the user who uploaded the document. %2$s: The date when the document was uploaded, formatted according to site settings.
                                    esc_html__('Uploaded by %1$s on %2$s', 'bp-group-documents'), 
                                    wp_kses_post(bp_core_get_userlink($document->user_id)),
                                    esc_html(
                                        date_i18n(
                                            get_option('date_format'), 
                                            $document->created_ts
                                        )
                                    )
                                );
                            } else {
                                if (true === $show_owner ) {
                                    printf(
                                    /* Translators: %s: User link */
                                        esc_html__('Uploaded by %s', 'bp-group-documents'), wp_kses_post(bp_core_get_userlink($document->user_id))
                                    );
                                }
                                if (true === $show_date ) {
                                    printf(
                                    /* Translators: %s: Date */
                                        esc_html__('Uploaded on %s', 'bp-group-documents'), esc_html(date_i18n(get_option('date_format'), $document->created_ts))
                                    );
                                }
                            }
                            ?>
                            <?php
                            echo '</li>';
                    }
                        echo '</ul>';
                } else {
                    
                    echo '<div class="widget-error">' . sprintf(
                    /* Translators: %s: Name of document  */
                        esc_html__('There are no %s to display.', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
                    ) . '</div></p>';
                }
                if (is_user_logged_in() ) {
                    $document_for_group = new BP_Group_Documents();
                    if ($document_for_group->current_user_can('add', $instance['group_id']) ) {
                        if (version_compare($buddypress_version, $compare_version, '>=') ) {
                            $url = bp_get_group_url($bp->groups->current_group) . BP_GROUP_DOCUMENTS_SLUG . '/add';
                        } else {
                            $url = bp_get_group_permalink($bp->groups->current_group) . BP_GROUP_DOCUMENTS_SLUG . '/add';
                        }
                        ?>
                <div class="generic-button group-button public">
            <a href="<?php echo esc_url($url); ?>" class="generic-button"><?php esc_html_e('Add New', 'bp-group-documents'); ?></a>
        </div>
                        <?php
                    }
                }
                if (version_compare($buddypress_version, $compare_version, '>=') ) {
                    echo '<div class="view-all"><a href="' . esc_url(
                        bp_get_group_url($bp->groups->current_group)
                        . BP_GROUP_DOCUMENTS_SLUG 
                    )
                    . '#object-nav">' . esc_html__('View all', 'bp-group-documents') . '</a></div>';
                } else {
                    echo '<div class="view-all"><a href="' . esc_url(
                        bp_get_group_permalink($bp->groups->current_group) 
                        . BP_GROUP_DOCUMENTS_SLUG 
                    )
                    . '#object-nav">' . esc_html__('View all', 'bp-group-documents') . '</a></div>';
                }
                echo wp_kses_post($args['after_widget']);
            }
        }
    }

    /**
     *
     * @param   array $new_instance
     * @param   array $old_instance
     * @return  array
     * @version 2.0, 9/4/2019
     */
    public function update( $new_instance, $old_instance )
    {
        do_action('bp_group_documents_current_group_widget_update');
        
        $default_title = sprintf(
        /* Translators: %s: Name of the document type */
            esc_html__('Recent %s for the group', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
        );

        $new_title =  sanitize_text_field(wp_unslash($new_instance['title']));
        $num_items = empty($new_instance['num_items']) ? 5 : absint($new_instance['num_items']);

        $instance          = $old_instance;
        $instance['title'] = empty($new_title) ?  sanitize_text_field(wp_unslash($default_title)) : $new_title;

        if (array_key_exists('featured', $new_instance) ) {
            $instance['featured'] = intval((bool) $new_instance['featured']);
        } else {
            $instance['featured'] = false;
        }
        $instance['num_items'] = empty($num_items) ? 5 : absint($new_instance['num_items']);

        return $instance;
    }

    /**
     *
     * @param type $instance
     */
    public function form( $instance )
    {
        do_action('bp_group_documents_current_group_widget_form');
        
        $defaults = array(
        'featured'  => false,
        'num_items' => 5,
        'title'     => sprintf(
        /* Translators: %s: Name of document */
            esc_html__('Recent %s for the group', 'bp-group-documents'), esc_html($this->bp_group_documents_name)
        ),
        );

        $instance  = wp_parse_args((array) $instance, $defaults);
        $title     = esc_attr($instance['title']);
        $featured  = (bool) $instance['featured'];
        $num_items = empty($instance['num_items']) ? 5 : absint($instance['num_items']);
        ?>
                <p><label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
        <?php esc_html_e('Title:', 'bp-group-documents'); ?></label>
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
               name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
               value="<?php echo esc_attr($title); ?>" /></p>
        <?php if (BP_GROUP_DOCUMENTS_FEATURED ) { ?>
                <p>
            <label for="<?php echo esc_attr($this->get_field_id('featured')); ?>">
            <?php
            /* translators: %s: Name of the document type */
            printf(esc_html__('Show featured %s only', 'bp-group-documents'), esc_html($this->bp_group_documents_name)); ?>
</label>
<input type="checkbox" id="<?php echo esc_attr($this->get_field_id('featured')); ?>"
       name="<?php echo esc_attr($this->get_field_name('featured')); ?>" value="1" <?php checked($featured); ?>>
</p>
        <?php } ?>
            <p><label for="<?php echo esc_attr($this->get_field_id('num_items')); ?>">
        <?php esc_html_e('Number of items to show:', 'bp-group-documents'); ?></label> 
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('num_items')); ?>" name="<?php echo esc_attr($this->get_field_name('num_items')); ?>" type="text" 
               value="<?php echo esc_attr($num_items); ?>" style="width: 30%" /></p>
        <?php
    }
}

