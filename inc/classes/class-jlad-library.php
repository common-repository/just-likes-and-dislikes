<?php

if (!class_exists('JLAD_Library')) {

    class JLAD_Library
    {

        var $jlad_settings;

        function __construct()
        {
            // Load up the current settings.
            $this->jlad_settings = get_option('jlad_settings');

            // Let's do some magic to make sure the settings array *always* has *all* of the keys
            // in it.  This avoids wp_debug messages for missing keys.

            // Get the default settings.
            $defaults = $this->get_default_settings();

            // If there are no settings set, save the defaults.
            if( ! is_array( $this->jlad_settings ) ) {
                $this->jlad_settings = $defaults;

                update_option( 'jlad_settings', $this->jlad_settings );

                return;
            }

            // Go through and merge the default keys into the main settings, this ensure all key values
            // have been set without actually setting the values.
            $this->jlad_settings = $this->merge_settings_and_defaults_keys( $this->jlad_settings, $defaults );

        }

        function merge_settings_and_defaults_keys( $jlad_settings, $defaults = null ) {
            if( is_null( $defaults ) ) {
                $defaults = $this->get_default_settings();
            }

            // Which settings groups we're going to process.
            $settings = array( 'basic_settings', 'design_settings' );

            // Loop through the setting groups.
            foreach( $settings as $setting ) {
                // Loop through each of the settings groups in the defaults.
                foreach ($defaults[$setting] as $key => $value) {
                    // Check to see if the array key from the defaults exists in the current settings.
                    if(! array_key_exists($key, $jlad_settings[$setting]) ) {
                        // If it doesn't, set it to an empty string.  We can't set it to the default because
                        // the setting may have purposefully deleted the value, like the "enable" setting in basic
                        // settings, that would then be stored as a missing key.  Instead, set it to null so the
                        // key exists, but has no value.
                        $jlad_settings[$setting][$key] = null;
                    }
                }
            }

            return $jlad_settings;
        }

        function print_array($array)
        {
            echo "<pre>";
            print_r($array);
            echo "</pre>";
        }

        /**
         * Returns default settings array
         *
         * @return array
         *
         * @since 1.0.0
         */
        function get_default_settings()
        {
            $default_settings = array();
            $default_settings['basic_settings']['status'] = 1;
            $default_settings['basic_settings']['post_types'] = array();
            $default_settings['basic_settings']['like_dislike_position'] = 'after';
            $default_settings['basic_settings']['like_dislike_display'] = 'both';
            $default_settings['basic_settings']['like_dislike_resistriction'] = 'cookie';
            $default_settings['basic_settings']['display_order'] = 'like-dislike';
            $default_settings['basic_settings']['like_hover_text'] = '';
            $default_settings['basic_settings']['dislike_hover_text'] = '';
            $default_settings['basic_settings']['display_zero'] = '';
            $default_settings['basic_settings']['hide_counter_info_metabox'] = '';
            $default_settings['basic_settings']['hide_like_dislike_admin'] = '';
            $default_settings['basic_settings']['login_link'] = '';
            $default_settings['design_settings']['template'] = 'template-1';
            $default_settings['design_settings']['like_icon'] = '';
            $default_settings['design_settings']['dislike_icon'] = '';
            $default_settings['design_settings']['icon_color'] = '';
            $default_settings['design_settings']['count_color'] = '';
            $default_settings['design_settings']['disable_fontawesome'] = '';

            return $default_settings;
        }

        function get_template_names()
        {
            $template_names =  array(
                                        'template-1' => __('Thumbs', 'just-likes-and-dislikes'),
                                        'template-2' => __('Hearts', 'just-likes-and-dislikes'),
                                        'template-3' => __('Check/Cross-out', 'just-likes-and-dislikes'),
                                        'template-4' => __('Happy/Sad', 'just-likes-and-dislikes'),
                                        'template-5' => __('Plus/Minus', 'just-likes-and-dislikes'),
                                        'template-6' => __('Up/Down', 'just-likes-and-dislikes'),
                                        'template-7' => __('Fire/Extinguisher', 'just-likes-and-dislikes'),
                                        'custom'     => __('Custom', 'just-likes-and-dislikes')
                                    );

            /**
             * Filters template name list
             *
             * @param int
             *
             * @since 2.0.0
             */
            $template_names = apply_filters('jlad_template_names', $template_names);

            return $template_names;
        }

        function get_template_count()
        {
            return count($this->get_template_names()) - 1;
        }

        function get_template_icon( $template )
        {
            $icon_color = $this->jlad_settings['design_settings']['icon_color'];
            $color_style = '';

            if( $icon_color ) { $color_style = ' style="color: ' . esc_attr( $icon_color ) . ';"'; }

            $like_title = $this->jlad_settings['basic_settings']['like_hover_text'];
            $dislike_title = $this->jlad_settings['basic_settings']['dislike_hover_text'];

            if( $like_title == '' ) { $like_title = __( 'Like', 'just-likes-and-dislikes' ); }
            if( $dislike_title == '' ) { $dislike_title = __( 'Dislike', 'just-likes-and-dislikes' ); }

            if( $this->jlad_settings['basic_settings']['like_dislike_resistriction'] == 'user' ) {
                if( ! is_user_logged_in() ) {
                    $like_title = sprintf( __('%1$s (login required)', 'just-likes-and-dislikes'), $like_title );
                    $dislike_title = sprintf( __('%1$s (login required)', 'just-likes-and-dislikes'), $dislike_title );
                }
            }

            switch ($template)
            {
                case 'template-2':
                    $like_icon      = '<i class="fas fa-heart"' . $color_style . ' title="' . $like_title . '"></i>';
                    $dislike_icon   = '<i class="fa fa-heartbeat"' . $color_style . ' title="' . $dislike_title . '"></i>';
                    break;
                case 'template-3':
                    $like_icon      = '<i class="fas fa-check"' . $color_style . ' title="' . $like_title . '"></i>';
                    $dislike_icon   = '<i class="fas fa-times"' . $color_style . ' title="' . $dislike_title . '"></i>';
                    break;
                case 'template-4':
                    $like_icon      = '<i class="far fa-smile"' . $color_style . ' title="' . $like_title . '"></i>';
                    $dislike_icon   = '<i class="far fa-frown"' . $color_style . ' title="' . $dislike_title . '"></i>';
                    break;
                case 'template-5':
                    $like_icon      = '<i class="fa-solid fa-circle-plus"' . $color_style . ' title="' . $like_title . '"></i>';
                    $dislike_icon   = '<i class="fa-solid fa-circle-minus"' . $color_style . ' title="' . $dislike_title . '"></i>';
                    break;
                case 'template-6':
                    $like_icon      = '<i class="fa-solid fa-circle-up"' . $color_style . ' title="' . $like_title . '"></i>';
                    $dislike_icon   = '<i class="fa-solid fa-circle-down"' . $color_style . ' title="' . $dislike_title . '"></i>';
                    break;
                case 'template-7':
                    $like_icon      = '<i class="fa-solid fa-fire"' . $color_style . ' title="' . $like_title . '"></i>';
                    $dislike_icon   = '<i class="fa-solid fa-fire-extinguisher"' . $color_style . ' title="' . $dislike_title . '"></i>';
                    break;
                case 'custom':
                    if ($this->jlad_settings['design_settings']['like_icon'] != '') {
                        $like_icon = '<img src="' . $this->jlad_settings['design_settings']['like_icon'] . '" alt="' . $like_title . '" title="' . $like_title . '"/>';
                        $dislike_icon = '<img src="' . $this->jlad_settings['design_settings']['dislike_icon'] . '" alt="' . $dislike_title . '" title="' . $dislike_title . '"/>';
                    }
                    break;
                default:
                    $like_icon      = '<i class="fas fa-thumbs-up"' . $color_style . ' title="' . $like_title . '"></i>';
                    $dislike_icon   = '<i class="fas fa-thumbs-down"' . $color_style . ' title="' . $dislike_title . '"></i>';
                    break;
            }

            $icons = array( $like_icon, $dislike_icon );

            /**
             * Filters template icons
             *
             * @param int
             *
             * @since 2.0.0
             */
            $icons = apply_filters('jlad_template_icons', $icons);

            return $icons;

        }

        function get_template_preview( $template, $hidden = true )
        {
            $html  = '<div class="jlad-each-template-preview jlad-like-dislike-wrap jlad-' . esc_attr($template) . '"';
            if($hidden ) {
	    	$html .= ' style="display: none;"';
            }
            $html .= ' data-template-ref="' . esc_attr($template) . '">' . PHP_EOL;
            $html .= '<div class="jlad-template-preview jlad-like-wrap jlad-common-wrap">' . PHP_EOL;

            list( $like_icon, $dislike_icon ) = $this->get_template_icon($template);

            $html .= '<a>' . $like_icon . '</a>' . PHP_EOL;
            $html .= '<span class="jlad-like-count-wrap jlad-count-wrap">25</span>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;
            $html .= '<div class="jlad-template-preview jlad-dislike-wrap jlad-common-wrap">' . PHP_EOL;
            $html .= '<a>' . $dislike_icon . '</a>' . PHP_EOL;
            $html .= '<span class="jlad-dislike-count-wrap jlad-count-wrap">0</span>' . PHP_EOL;

            $html .= '</div>' . PHP_EOL;
            $html .= '</div>' . PHP_EOL;

            return $html;
        }

        /**
         * Returns visitors IP address
         *
         * @return string $ip
         *
         * @since 1.0.0
         */
        function get_user_IP()
        {
            $client = sanitize_text_field( @$_SERVER['HTTP_CLIENT_IP'] );
            $forward = sanitize_text_field( @$_SERVER['HTTP_X_FORWARDED_FOR'] );
            $remote = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );

            // Make sure remote is a valid IP address, otherwise use a default.
            if (filter_var($remote, FILTER_VALIDATE_IP) === false) { $remote = '127.0.0.1'; }

            if (filter_var($client, FILTER_VALIDATE_IP)) {
                $ip = $client;
            } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
                $ip = $forward;
            } else {
                $ip = $remote;
            }

            return $ip;
        }

        /**
         * Sanitizes Multi Dimensional Array
         *
         * @param  array $array
         * @param  array $sanitize_rule
         * @return array
         *
         * @since 1.0.0
         */
        function sanitize_array($array = array(), $sanitize_rule = array())
        {
            if (!is_array($array) || count($array) == 0) {
                return array();
            }

            foreach ($array as $k => $v) {
                if (!is_array($v)) {

                    $default_sanitize_rule = (is_numeric($k)) ? 'html' : 'text';
                    $sanitize_type = isset($sanitize_rule[$k]) ? $sanitize_rule[$k] : $default_sanitize_rule;
                    $array[$k] = $this->sanitize_value($v, $sanitize_type);
                }
                if (is_array($v)) {
                    $array[$k] = $this->sanitize_array($v, $sanitize_rule);
                }
            }

            return $array;
        }

        /**
         * Sanitizes Value
         *
         * @param  type $value
         * @param  type $sanitize_type
         * @return string
         *
         * @since 1.0.0
         */
        function sanitize_value($value = '', $sanitize_type = 'text')
        {
            switch ($sanitize_type) {
            case 'html':
                return $this->sanitize_html($value);
                    break;
            case 'none':
                return $value;
                    break;
            default:
                return sanitize_text_field($value);
                    break;
            }
        }

        /**
         * Sanitizes the content by bypassing allowed html
         *
         * @param  string $text
         * @return string
         *
         * @since 1.0.0
         */
        function sanitize_html($text)
        {
            $allowed_html = wp_kses_allowed_html('post');
            return wp_kses($text, $allowed_html);
        }

        /**
         * Prints display none
         *
         * @param string $param1
         * @param string $param2
         *
         * @since 1.0.5
         */
        function display_none($param1, $param2)
        {
            if ($param1 != $param2) {
                echo 'style="display:none"';
            }
        }

        /**
         * Returns current page URL
         *
         * @since 1.0.5
         */
        function get_current_page_url()
        {
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                $url = "https://";
            } else {
                $url = "http://";
            }
            // Append the host(domain name, ip) to the URL.
            $url .= sanitize_text_field( $_SERVER['HTTP_HOST'] );

            // Append the requested resource location to the URL
            $url .= sanitize_text_field( $_SERVER['REQUEST_URI'] );

            return $url;
        }

    }

}