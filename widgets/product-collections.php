<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Register Custom WooCommerce Widget for Elementor
 */
add_action('elementor/widgets/widgets_registered', 'register_custom_woocommerce_widget');

function register_custom_woocommerce_widget() {
    if (!did_action('elementor/loaded')) {
        return;
    }

    if (class_exists('\Elementor\Widget_Base')) {

        class Custom_WooCommerce_Elementor_Widget extends \Elementor\Widget_Base {

            // [Widget properties, controls, render methods here...]

            public function get_name() {
                return 'custom-woocommerce-products';
            }

            public function get_title() {
                return __('Product Collections', 'text-domain');
            }

            public function get_icon() {
                return 'eicon-woocommerce';
            }

            public function get_categories() {
                return ['general'];
            }

            protected function _register_controls() {
                // Enqueue and localize the JavaScript for this control
                wp_enqueue_script('custom-woocommerce-elementor-ajax', plugin_dir_url(dirname(__FILE__)) . 'js/ajax.js', ['jquery'], '1.0', true);
                wp_localize_script('custom-woocommerce-elementor-ajax', 'customWooElementor', ['ajaxurl' => admin_url('admin-ajax.php')]);

                $this->start_controls_section(
                    'content_section',
                    [
                        'label' => __('Settings', 'text-domain'),
                        'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                    ]
                );                          

                $this->add_control(
                    'number_of_products',
                    [
                        'label' => __('Number of Products', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::NUMBER,
                        'min' => 1,
                        'max' => 100,
                        'default' => 4,
                    ]
                );

                $this->add_responsive_control(
                    'columns',
                    [
                        'label' => __('Columns', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'default' => '4',
                        'options' => [
                            '1' => '1',
                            '2' => '2',
                            '3' => '3',
                            '4' => '4',
                            '5' => '5',
                            '6' => '6',
                        ],
                        'selectors' => [
                            '{{WRAPPER}} .products' => 'grid-template-columns: repeat({{VALUE}}, 1fr);',
                        ],
                    ]
                );              

                $this->add_control(
                    'product_type',
                    [
                        'label' => __('Product Type', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'default' => 'newest',
                        'options' => [
                            'best_sellers' => __('Best Sellers', 'text-domain'),
                            'newest' => __('Newest', 'text-domain'),
                            'on_sale' => __('On Sale', 'text-domain'),
                            'featured' => __('Featured', 'text-domain'),
                            'categories' => __('Categories', 'text-domain'),
                            'tags' => __('Tags', 'text-domain'),
                            'attributes' => __('Attributes', 'text-domain'),
                            'specific_products' => __('Specific Products', 'text-domain'),
                        ]
                    ]
                );


                $categories_array = [];
                $categories = get_terms('product_cat');
                foreach($categories as $category) {
                    $categories_array[$category->term_id] = $category->name;
                }

                $this->add_control(
                    'product_categories',
                    [
                        'label' => __('Select Categories', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => $categories_array,
                        'condition' => [
                            'product_type' => 'categories',
                        ],
                    ]
                );
                
                // Getting available product tags:
                $tags_array = [];
                $tags = get_terms('product_tag');
                if (!is_wp_error($tags)) {
                    foreach($tags as $tag) {
                        $tags_array[$tag->slug] = $tag->name;
                    }
                }

                $this->add_control(
                    'product_tags',
                    [
                        'label' => __('Select Tags', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => $tags_array,
                        'condition' => [
                            'product_type' => 'tags',
                        ],
                    ]
                );  
                
                $attribute_taxonomies = wc_get_attribute_taxonomies();
                $attributes_array = [];

                foreach($attribute_taxonomies as $attribute) {
                    $attributes_array[$attribute->attribute_name] = $attribute->attribute_label;
                }

                $this->add_control(
                    'product_attributes',
                    [
                        'label' => __('Select Attribute', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT,
                        'options' => $attributes_array,
                        'condition' => [
                            'product_type' => 'attributes',
                        ],
                    ]
                );  

                $this->add_control(
                    'attribute_terms',
                    [
                        'label' => __('Select Attribute Terms', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => [], // We'll populate this dynamically with JavaScript later
                        'condition' => [
                            'product_type' => 'attributes',
                        ],
                    ]
                );



                // Fetch all products for selection
                $products_array = [];
                $products = wc_get_products(['numberposts' => -1]);
                foreach ($products as $product) {
                    $products_array[$product->get_id()] = $product->get_name();
                }

                $this->add_control(
                    'selected_products',
                    [
                        'label' => __('Select Products', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SELECT2,
                        'multiple' => true,
                        'label_block' => true,
                        'options' => $products_array,
                        'condition' => [
                            'product_type' => 'specific_products',
                        ],
                    ]
                );


                // Control to show or hide the product title
                $this->add_control(
                    'show_title',
                    [
                        'label' => __('Show Title', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'text-domain'),
                        'label_off' => __('No', 'text-domain'),
                        'return_value' => 'yes',
                        'default' => 'yes',
                    ]
                );

                // Control to show or hide the product price
                $this->add_control(
                    'show_price',
                    [
                        'label' => __('Show Price', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'text-domain'),
                        'label_off' => __('No', 'text-domain'),
                        'return_value' => 'yes',
                        'default' => 'yes',
                    ]
                );

                // Control to show or hide the "Add to Cart" button
                $this->add_control(
                    'show_add_to_cart',
                    [
                        'label' => __('Show Add to Cart', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'text-domain'),
                        'label_off' => __('No', 'text-domain'),
                        'return_value' => 'yes',
                        'default' => 'yes',
                    ]
                );
                
                $this->add_control(
                    'hide_sale_badge',
                    [
                        'label' => __('Hide Sale Badge', 'text-domain'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'label_on' => __('Yes', 'text-domain'),
                        'label_off' => __('No', 'text-domain'),
                        'return_value' => 'yes',
                        'default' => 'no',
                    ]
                );
                

                $this->add_responsive_control(
                    'content_align',
                    [
                        'label' => __( 'Alignment', 'text-domain' ),
                        'type' => \Elementor\Controls_Manager::CHOOSE,
                        'options' => [
                            'left'    => [
                                'title' => __( 'Left', 'text-domain' ),
                                'icon' => 'eicon-text-align-left',
                            ],
                            'center' => [
                                'title' => __( 'Center', 'text-domain' ),
                                'icon' => 'eicon-text-align-center',
                            ],
                            'right' => [
                                'title' => __( 'Right', 'text-domain' ),
                                'icon' => 'eicon-text-align-right',
                            ],
                        ],
                        'default' => 'left',
                        'toggle' => true,
                        'selectors' => [
                            '{{WRAPPER}} .woocommerce ul.products li.product,
                             {{WRAPPER}} .woocommerce ul.products li.product .woocommerce-loop-product__title, 
                             {{WRAPPER}} .woocommerce ul.products li.product .price, 
                             {{WRAPPER}} .woocommerce ul.products li.product .add_to_cart_button, 
                             {{WRAPPER}} .woocommerce ul.products li.product .button' => 'text-align: {{VALUE}};',
                        ],
                    ]
                );

                $this->add_control(
                    'html_msg',
                    [
                        'type'    => \Elementor\Controls_Manager::RAW_HTML,
                        'raw'     => '<div style="margin:0; background-color: #f7d08a; padding: 10px 15px; border-left: 4px solid #f5a623; color: #6a3403; font-style:normal; "><strong>Read Me First:</strong>1. Incase of some themes, the appearance might be distorted in the editor. However, if you refresh and view the actual page, it should display correctly.<br>2. Some themes might be able to over ride these settings.</div>',
                        'content_classes' => 'elementor-descriptor',
                    ]
                );              

                $this->end_controls_section();

            }

            protected function render() {
                
$settings = $this->get_settings_for_display();

    $style = '<style>';

    if ($settings['show_title'] !== 'yes') {
        $style .= '.woocommerce ul.products li.product .woocommerce-loop-product__title { display: none; }';
    }
    if ($settings['show_price'] !== 'yes') {
        $style .= '.woocommerce ul.products li.product .price { display: none; }';
    }
    if ($settings['show_add_to_cart'] !== 'yes') {
        $style .= '.woocommerce ul.products li.product .add_to_cart_button, .woocommerce ul.products li.product .button { display: none; }';
    }           
    if ($settings['hide_sale_badge'] === 'yes') {
        $style .= '.woocommerce span.onsale { display: none; }';
    }

                

    $style .= '</style>';

    echo $style;
            

    $columns = (isset($settings['columns'])) ? absint($settings['columns']) : 4; // Defaults to 4 columns

    $shortcode = '[products columns="%d" limit="%d"';

    switch($settings['product_type']) {
        case 'best_sellers':
            $shortcode .= ' best_selling="true"';
            break;
        case 'newest':
            // Already the default behavior, nothing to add
            break;
        case 'on_sale':
            $shortcode .= ' on_sale="true"';
            break;
        case 'featured':
            $shortcode .= ' visibility="featured"';
            break;
        case 'categories':
            if (!empty($settings['product_categories'])) {
                $cat_ids = implode(',', $settings['product_categories']);
                $shortcode .= ' category="%s"';
            }
            break;
        case 'tags':
                if (!empty($settings['product_tags'])) {
                    $tag_slugs = implode(',', $settings['product_tags']);
                    $shortcode .= ' tag="' . $tag_slugs . '"';
                }
            break;
        case 'attributes':
                if (!empty($settings['product_attributes']) && !empty($settings['attribute_terms'])) {
                    $attribute_terms = implode(',', $settings['attribute_terms']);
                    $shortcode .= sprintf(' attribute="%s" terms="%s"', esc_attr($settings['product_attributes']), esc_attr($attribute_terms));
                }
                break;  
        case 'specific_products':
                if (!empty($settings['selected_products'])) {
                    $selected_ids = implode(',', $settings['selected_products']);
                    $shortcode .= ' ids="%s"';
                }
                break;                      
    }

    // Ensure the shortcode is closed with a bracket
    if (isset($cat_ids)) {
        $shortcode = sprintf($shortcode, $columns, $settings['number_of_products'], $cat_ids) . ']';
    } elseif (isset($selected_ids)) { // New condition for specific products
        $shortcode = sprintf($shortcode, $columns, $settings['number_of_products'], $selected_ids) . ']';
    } else {
        $shortcode = sprintf($shortcode, $columns, $settings['number_of_products']) . ']';
    }
                

    echo do_shortcode($shortcode);

            }

        }

        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Custom_WooCommerce_Elementor_Widget());
    }
}

/**
 * Enqueue styles when editing or previewing in Elementor
 */
function custom_woocommerce_elementor_widget_styles() {
    if (\Elementor\Plugin::$instance->editor->is_edit_mode() || \Elementor\Plugin::$instance->preview->is_preview_mode()) {
        wp_enqueue_style('custom-woocommerce-elementor-widget-style', plugin_dir_url(dirname(__FILE__)) . 'css/style.css', [], '1.0');
    }
}
add_action('wp_enqueue_scripts', 'custom_woocommerce_elementor_widget_styles');

/**
 * AJAX callback to retrieve WooCommerce attribute terms
 */
function custom_get_attribute_terms_callback() {
    if (isset($_POST['attribute_name'])) {
        $taxonomy = 'pa_' . sanitize_text_field($_POST['attribute_name']);
        $terms = get_terms($taxonomy);

        $response = [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $response[$term->slug] = $term->name;
            }
        }

        echo json_encode($response);
    }
    wp_die();
}
add_action('wp_ajax_get_attribute_terms', 'custom_get_attribute_terms_callback');
add_action('wp_ajax_nopriv_get_attribute_terms', 'custom_get_attribute_terms_callback');
