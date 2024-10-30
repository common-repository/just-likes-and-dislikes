<?php

if (!class_exists('JLAD_Hooks')) {
    class JLAD_Hooks extends JLAD_Library
    {
        protected $like_column_name     = 'jlad_like_count';
        protected $dislike_column_name  = 'jlad_dislike_count';
        protected $likes_enabled        = true;
        protected $dislikes_enabled     = true;

        private $before_filter_pattern = '/^[\s*\d+]+/';
        private $after_filter_pattern = '/[\s*\d+]+$/';

        public function __construct()
        {
            parent::__construct();

            $display_type = $this->jlad_settings['basic_settings']['like_dislike_display'];

            // Figure out if we're displaying likes, dislikes or both and set the class variables appropriately.
            if($display_type == 'both' || $display_type == 'like_only' ) {
                $this->likes_enabled = true;
            }

            if($display_type == 'both' || $display_type == 'dislike_only' ) {
                $this->dislikes_enabled = true;
            }

            if($display_type == 'like_only' ) {
                $this->dislikes_enabled = false;
            }

            if($display_type == 'dislike_only' ) {
                $this->likes_enabled = false;
            }

            //Add filter/actions for posts.
            add_filter('the_content', array($this, 'posts_like_dislike'), 200);
            add_action('jlad_post_like_dislike_output', array($this, 'generate_post_like_dislike_html'), 10, 3);
            add_action('wp_head', array($this, 'custom_styles'));
            add_shortcode('just_likes_and_dislikes', array($this, 'render_jlad_shortcode'));
            add_shortcode('jlad', array($this, 'render_jlad_shortcode'));
            add_shortcode('just_likes_and_dislikes_top_table', array($this, 'render_top_table_shortcode'));
            add_shortcode('jlad_top_table', array($this, 'render_top_table_shortcode'));

            // Add filter/actions for comments.
            add_filter('comment_text', array($this, 'comments_like_dislike'), 200, 2);
            add_action('jlad_comment_like_dislike_output', array($this, 'generate_comment_like_dislike_html'), 10, 2);

            // Add filter to exclude copying the like/dislike counts when using Yoast Duplicate Posts.
            add_filter('duplicate_post_excludelist_filter', array($this, 'duplicate_post_excludelist_filter'));

            // Filter out the like/dislike counts on excerpts.
            add_filter('get_the_excerpt', array( $this, 'the_excerpt_filter'));

            if(! $this->jlad_settings['basic_settings']['hide_like_dislike_admin'] ) {
                // Add an admin column for like/dislikes
                add_action('pre_get_posts', array($this,'pre_get_posts'));
                $available_post_types = get_post_types(array(), 'names');

                foreach( $available_post_types as $type )
                {
                    add_filter('manage_edit-' . $type . '_sortable_columns', array($this, 'manage_post_posts_sortable_columns' ));
                    add_filter('manage_' . $type . '_posts_columns', array($this, 'manage_post_posts_columns'));
                    add_action('manage_' . $type . '_posts_custom_column', array($this,'manage_post_posts_custom_column'), 10, 2);
                }

                /**
                 * Setup sorting for comments.
                 *
                 * @since 2.4.0
                 */
                add_action('pre_get_comments', array($this,'pre_get_comments'));

                /**
                 * Add like dislike columns in comments section
                 *
                 * @since 1.0.5
                 */
                add_filter('manage_edit-comments_columns', array($this, 'manage_post_posts_columns'));

                /**
                 * Make the like/dislike columns sortable
                 *
                 * @since 1.0.5
                 */
                add_filter('manage_edit-comments_sortable_columns', array($this, 'manage_post_posts_sortable_columns' ));

                /**
                 * Display Like Dislike count in each column
                 *
                 * @since 1.0.5
                 */
                add_action('manage_comments_custom_column', array($this, 'manage_comments_custom_column'), 10, 2);

                /**
                 * Add like dislike columns in categories section
                 *
                 * @since 2.5.0
                 */
                add_filter('manage_edit-category_columns', array($this, 'manage_post_posts_columns'));

                /**
                 * Display Like Dislike count in each column
                 *
                 * @since 2.5.0
                 */
                add_action('manage_category_custom_column', array($this, 'manage_category_custom_column'), 10, 3);

                /**
                 * Add like dislike columns in tags section
                 *
                 * @since 2.5.0
                 */
                add_filter('manage_edit-post_tag_columns', array($this, 'manage_post_posts_columns'));

                /**
                 * Display Like Dislike count in each column
                 *
                 * @since 2.5.0
                 */
                add_action('manage_post_tag_custom_column', array($this, 'manage_tag_custom_column'), 10, 3);
            }

        }

        public function comments_like_dislike($comment_text, $comment = null)
        {
            /**
             * Don't append like dislike when links are being checked
             *
             * @1.0.6
             */
            if (isset($_REQUEST['comment'])) {
                return $comment_text;
            }
            /**
             * Don't implement on admin section
             *
             * @since 1.0.2
             */
            if (is_admin() && !wp_doing_ajax()) {
                return $comment_text;
            }

            ob_start();

            /**
             * Fires while generating the like dislike html
             *
             * @param type string $comment_text
             * @param type array $comment
             *
             * @since 1.0.0
             */
            $post_id = get_the_ID();
            do_action('jlad_comment_like_dislike_output', $comment, $post_id);

            $like_dislike_html = ob_get_contents();

            ob_end_clean();

            if ($this->jlad_settings['basic_settings']['like_dislike_position'] == 'after') {
                /**
                 * Filters Like Dislike HTML
                 *
                 * @param string $like_dislike_html
                 * @param array $cld_settings
                 *
                 * @since 1.0.0
                 */
                $comment_text .= apply_filters('cld_like_dislike_html', $like_dislike_html, $this->jlad_settings);
            } else {
                $comment_text = apply_filters('cld_like_dislike_html', $like_dislike_html, $this->jlad_settings) . $comment_text;
            }

            return $comment_text;
        }

        public function the_excerpt_filter($excerpt)
        {
            if ($this->jlad_settings['basic_settings']['like_dislike_position'] == 'before') {
                $new_excerpt = preg_replace($this->before_filter_pattern, '', $excerpt);
            }

            if ($this->jlad_settings['basic_settings']['like_dislike_position'] == 'after') {
                $new_excerpt = preg_replace($this->after_filter_pattern, '', $excerpt);
            }

            // Make sure we got a string back, otherwise something went wrong and just return the old except.
            if(is_string($new_excerpt) ) { $excerpt = $new_excerpt;
            }

            return $excerpt;
        }

        public function posts_like_dislike($content)
        {
            include JLAD_PATH . '/inc/cores/like-dislike-render.php';
            return $content;
        }

        public function duplicate_post_excludelist_filter($meta_excludelist)
        {
            // Merges the defaults array with our own array of custom fields.
            return array_merge($meta_excludelist, ['jlad_like_count', 'jlad_dislike_count']);
        }

        public function render_jlad_shortcode($atts)
        {
            $content = '';
            $shortcode = true;
            include JLAD_PATH . '/inc/cores/like-dislike-render.php';
            return $content;
        }

        public function generate_post_like_dislike_html($content, $shortcode, $atts)
        {
            include JLAD_PATH . '/inc/views/frontend/post-like-dislike-html.php';
        }

        public function generate_comment_like_dislike_html($comment, $post_id)
        {
            include JLAD_PATH . '/inc/views/frontend/comment-like-dislike-html.php';
        }

        public function custom_styles()
        {
            echo "<style>";
            if ($this->jlad_settings['design_settings']['icon_color'] != '') {
                echo 'a.jlad-like-dislike-trigger {color: ' . esc_attr($this->jlad_settings['design_settings']['icon_color']) . ';}';
            }
            if ($this->jlad_settings['design_settings']['count_color'] != '') {
                echo 'span.jlad-count-wrap {color: ' . esc_attr($this->jlad_settings['design_settings']['count_color']) . ';}';
            }
            echo "</style>";
        }

        public function manage_post_posts_columns($columns)
        {
            // Build the like/dislike icon based on the current design settings.
            $like_title = isset($this->jlad_settings['basic_settings']['like_hover_text']) ? esc_attr($this->jlad_settings['basic_settings']['like_hover_text']) : __('Like', 'just-likes-and-dislikes');
            $dislike_title = isset($this->jlad_settings['basic_settings']['dislike_hover_text']) ? esc_attr($this->jlad_settings['basic_settings']['dislike_hover_text']) : __('Dislike', 'just-likes-and-dislikes');

            list( $like_icon, $dislike_icon ) = $this->get_template_icon($this->jlad_settings['design_settings']['template']);

            // Add a span infront of them to make them look right in the screen options pulldown.
            $like_icon = '<span><span class="vers" title="' . __('Like', 'just-likes-and-dislikes') . '" aria-hidden="true"></span><span class="screen-reader-text">' . __('Like', 'just-likes-and-dislikes') . '</span></span>' . $like_icon;
            $dislike_icon = '<span><span class="vers" title="' . __('Dislike', 'just-likes-and-dislikes') . '" aria-hidden="true"></span><span class="screen-reader-text">' . __('Dislike', 'just-likes-and-dislikes') . '</span></span>' . $dislike_icon;

            // Loop through and create a new array, adding in our column at the right spot.
            foreach( $columns as $key => $value )
            {
                // If we're in the comments screen, add the columns before the "In response to" column.
                if($key == 'response' ) {
                    // Setup placeholders to set later.
                    if($this->likes_enabled) {
                        $new_columns[ $this->like_column_name ] = '';
                    }

                    if($this->dislikes_enabled) {
                        $new_columns[ $this->dislike_column_name ] = '';
                    }
                }

                $new_columns[$key] = $value;

                // If we're in the posts screen, add the columns after the "Comments" column.
                if($key == 'comments' ) {
                    // Setup placeholders to set later.
                    if($this->likes_enabled) {
                        $new_columns[ $this->like_column_name ] = '';
                    }

                    if($this->dislikes_enabled) {
                        $new_columns[ $this->dislike_column_name ] = '';
                    }
                }
            }

            // Now actually set the new column values, if they weren't added in the above loop, they will be added to the end now.
            if($this->likes_enabled) { $new_columns[ $this->like_column_name ] = $like_icon;
            }
            if($this->dislikes_enabled) {$new_columns[ $this->dislike_column_name ] = $dislike_icon;
            }

            return $new_columns;
        }

        public function manage_post_posts_custom_column($column_key, $post_id)
        {
            if ($column_key == $this->like_column_name || $column_key == $this->dislike_column_name) {
                $like_count = intval(get_post_meta($post_id, 'jlad_like_count', true));
                $dislike_count = intval(get_post_meta($post_id, 'jlad_dislike_count', true));

                if ($column_key == $this->like_column_name ) {
                    echo esc_html( $like_count > 0 ? $like_count : '—' );
                }

                if($column_key == $this->dislike_column_name) {
                    echo esc_html( $dislike_count > 0 ? $dislike_count : '—' );
                }
            }
        }

        public function manage_post_posts_sortable_columns($columns)
        {
            $columns['jlad_like_count']      = $this->like_column_name;
            $columns['jlad_dislike_count']   = $this->dislike_column_name;

            return $columns;
        }


        function manage_comments_custom_column($column_key, $comment_id)
        {
            if ($column_key == $this->like_column_name || $column_key == $this->dislike_column_name) {
                $like_count = intval(get_comment_meta($comment_id, 'jlad_like_count', true));
                $dislike_count = intval(get_comment_meta($comment_id, 'jlad_dislike_count', true));

                if ($column_key == $this->like_column_name ) {
                    echo esc_html( $like_count > 0 ? $like_count : '—' );
                }

                if($column_key == $this->dislike_column_name) {
                    echo esc_html( $dislike_count > 0 ? $dislike_count : '—' );
                }
            }
        }

        function manage_category_custom_column($string, $column_key, $category_id)
        {
            if ($column_key == $this->like_column_name || $column_key == $this->dislike_column_name) {
                $like_posts = get_posts( array(
                    'numberposts' => -1,
                    'category' => $category_id,
                    'meta_key' => $this->like_column_name
                    )
                );

                $dislike_posts = get_posts( array(
                    'numberposts' => -1,
                    'category' => $category_id,
                    'meta_key' => $this->dislike_column_name
                    )
                );

                $like_count = 0;
                foreach( $like_posts as $post ) {
                    $like_count += intval( get_post_meta( $post->ID, 'jlad_like_count', true ) );
                }

                $dislike_count = 0;
                foreach( $dislike_posts as $post ) {
                    $dislike_count += intval( get_post_meta( $post->ID, 'jlad_dislike_count', true ) );
                }

                if ($column_key == $this->like_column_name ) {
                    echo esc_html( $like_count > 0 ? $like_count : '—' );
                }

                if($column_key == $this->dislike_column_name) {
                    echo esc_html( $dislike_count > 0 ? $dislike_count : '—' );
                }
            }
        }

        function manage_tag_custom_column($string, $column_key, $tag_id)
        {
            // By default tags are only enabled on posts, but there are plugins to enable
            // them on pages or other taxonomies, so check what kind of post types we are
            // dealing with and then limit the query to just those.
            global $current_screen;

            // Get the currently valid post types.
            $post_types = get_post_types( array(), 'names', 'and');

            // Get the current screen's post type.
            $post_type = $current_screen->post_type;

            // Make sure we have a valid post type, if not default to 'post'.
            if( ! in_array($post_type, $post_types) ) { $post_type = 'post'; }

            if ($column_key == $this->like_column_name || $column_key == $this->dislike_column_name) {
                $like_posts = get_posts( array(
                    'numberposts' => -1,
                    'tag_id' => $tag_id,
                    'meta_key' => $this->like_column_name,
                    'post_type' => $post_type
                    )
                );

                $dislike_posts = get_posts( array(
                    'numberposts' => -1,
                    'tag_id' => $tag_id,
                    'meta_key' => $this->dislike_column_name,
                    'post_type' => $post_type
                    )
                );

                $like_count = 0;
                foreach( $like_posts as $post ) {
                    $like_count += intval( get_post_meta( $post->ID, 'jlad_like_count', true ) );
                }

                $dislike_count = 0;
                foreach( $dislike_posts as $post ) {
                    $dislike_count += intval( get_post_meta( $post->ID, 'jlad_dislike_count', true ) );
                }

                if ($column_key == $this->like_column_name ) {
                    echo esc_html( $like_count > 0 ? $like_count : '—' );
                }

                if($column_key == $this->dislike_column_name) {
                    echo esc_html( $dislike_count > 0 ? $dislike_count : '—' );
                }
            }
        }

        public function pre_get_posts($query)
        {
            global $wpdb;

            // Only filter in the admin
            if(! is_admin() ) {
                return;
            }

            $orderby = $query->get('orderby');

            // Filter if orderby is set to 'jlad_like_count'
            if($this->like_column_name == $orderby ) {
                // Set the meta_query instead of meta_key so that we get
                // all meta values, including those that don't exist yet.
                $query->set('meta_query', array(
                    'relation' => 'OR',
                    array(
                        'key' => $this->like_column_name,
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => $this->like_column_name,
                        'compare' => 'EXISTS'
                    ),
                ) );

                $query->set('orderby', 'meta_value_num');
            }

            // Filter if orderby is set to 'jlad_dislike_count'
            if($this->dislike_column_name == $orderby ) {
                // Set the meta_query instead of meta_key so that we get
                // all meta values, including those that don't exist yet.
                $query->set('meta_query', array(
                    'relation' => 'OR',
                    array(
                        'key' => $this->dislike_column_name,
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => $this->dislike_column_name,
                        'compare' => 'EXISTS'
                    ),
                ) );

                $query->set('orderby', 'meta_value_num');
            }
        }

        public function pre_get_comments($query)
        {
            global $wpdb;

            // Only filter in the admin
            if(! is_admin() ) {
                return;
            }

            $orderby = $query->query_vars['orderby'];

            // Filter if orderby is set to 'jlad_like_count'
            if($this->like_column_name == $orderby ) {
                // Set the meta_query instead of meta_key so that we get
                // all meta values, including those that don't exist yet.
                $query->query_vars['meta_query'] = array(
                    'relation' => 'OR',
                    array(
                        'key' => $this->like_column_name,
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => $this->like_column_name,
                        'compare' => 'EXISTS'
                    ),
                );

                $query->query_vars['orderby'] = 'meta_value_num';
            }

            // Filter if orderby is set to 'jlad_dislike_count'
            if($this->dislike_column_name == $orderby ) {
                // Set the meta_query instead of meta_key so that we get
                // all meta values, including those that don't exist yet.
                $query->query_vars['meta_query'] = array(
                    'relation' => 'OR',
                    array(
                        'key' => $this->dislike_column_name,
                        'compare' => 'NOT EXISTS'
                    ),
                    array(
                        'key' => $this->dislike_column_name,
                        'compare' => 'EXISTS'
                    ),
                );

                $query->query_vars['orderby'] = 'meta_value_num';
            }
        }

        public function render_top_table_shortcode( $atts ) {
            $defaults = array(
                'count' => 10,
                'show_likes' => $this->likes_enabled,
                'show_dislikes' => $this->dislikes_enabled,
                'types' => 'post',
                'show_table_title' => true,
                'show_row_numbers' => true,
            );

            // Process the atts and defaults into a single table.
            $options = shortcode_atts( $defaults, $atts );

            // Convert text string "false" to boolean false.
            if( strtolower( $options['show_likes'] ) == 'false' ) { $options['show_likes'] = false; }
            if( strtolower( $options['show_dislikes'] ) == 'false' ) { $options['show_dislikes'] = false; }
            if( strtolower( $options['show_table_title'] ) == 'false' ) { $options['show_table_title'] = false; }
            if( strtolower( $options['show_row_numbers'] ) == 'false' ) { $options['show_row_numbers'] = false; }

            // Setup the types lists.
            $valid_types = array();
            $types_list = explode( ',', $options['types'] );

            // Get the currently valid post types.
            $post_types = get_post_types( array(), 'names', 'and');

            // Make sure we have at least one type to check.
            if( is_array( $types_list ) && count( $types_list ) > 0 ) {
                // Loop through the types.
                foreach( $types_list as $type ) {
                    // Trim and lowercase to clean up the type.
                    $type = strtolower( trim( $type ) );
                    // Make sure we have a valid post type,
                    // if so add it to the valid types list,
                    // otherwise skip it.
                    if( in_array( $type , $post_types ) ) {
                        $valid_types[] = $type;
                    }
                }
            }

            // If no valid types were found, setup posts as the default.
            if( count( $valid_types ) == 0 ) {
                $options['types'] = array( 'post' );
            } else {
                $options['types'] = $valid_types;
            }

            // Buffer for output.
            $content = '';

            // If we're showing likes, do so.
            if( $options['show_likes'] ) {
                // Generate a table for each valid type we have.
                foreach( $valid_types as $post_type ) {
                    $content .= $this->generate_count_table( $this->like_column_name, $post_type, $options);
                }
            }

            // If we're showing dislikes, do so.
            if( $options['show_dislikes'] ) {
                // Generate a table for each valid type we have.
                foreach( $valid_types as $post_type ) {
                    $content .= $this->generate_count_table( $this->dislike_column_name, $post_type, $options);
                }
            }

            // Return the content.
            return $content;
        }

        private function generate_count_table( $type, $post_type, $options ) {
            include JLAD_PATH . '/inc/cores/count-table-render.php';
            return $content;
        }
    }

    new JLAD_Hooks();
}
