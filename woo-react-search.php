<?php
/**
 * Plugin Name: Woo React Search
 * Description: A React-powered AJAX search bar for WooCommerce. Use shortcode [woo_react_search]
 * Version: 1.0
 * Author: Laalaasaur
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Woo_React_Search {

    public function __construct() {
        add_shortcode( 'woo_react_search', array( $this, 'render_shortcode' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_route' ) );
    }

    // 1. Render the div AND enqueue scripts when the shortcode is used
    public function render_shortcode() {
        $asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
        
        // Check if React files actually exist
        if ( file_exists( $asset_file ) ) {
            $assets = require $asset_file;
            
            wp_enqueue_script(
                'woo-react-search-js',
                plugin_dir_url( __FILE__ ) . 'build/index.js',
                $assets['dependencies'],
                $assets['version'],
                true // Load in footer
            );

            wp_enqueue_style(
                'woo-react-search-css',
                plugin_dir_url( __FILE__ ) . 'build/style-index.css',
                array(),
                $assets['version']
            );

            wp_localize_script( 'woo-react-search-js', 'wooReactSearchData', array(
                'restUrl' => esc_url_raw( rest_url( 'woo-react-search/v1/products' ) ),
                'homeUrl' => esc_url_raw( home_url( '/' ) )
            ) );
        } else {
            // If you see this, it means you need to run `npm run build`
            if ( current_user_can( 'manage_options' ) ) {
                return '<p style="color:red; border:1px solid red; padding: 10px;"><b>Woo React Search Error:</b> Build files are missing. Please open your terminal, navigate to this plugin folder, and run <code>npm install</code> followed by <code>npm run build</code>.</p>';
            }
        }

        return '<div id="woo-react-search-root"></div>';
    }

    // 2. Register Custom REST Endpoint
    public function register_rest_route() {
        register_rest_route( 'woo-react-search/v1', '/products', array(
            'methods'  => 'GET',
            'callback' => array( $this, 'get_products' ),
            'permission_callback' => '__return_true', 
        ) );
    }

    // 3. Fetch Products Callback
    public function get_products( $request ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_Error( 'woo_missing', 'WooCommerce is not active', array( 'status' => 500 ) );
        }

        $term = sanitize_text_field( $request->get_param( 'term' ) );
        if ( empty( $term ) ) { return array(); }

        // --- STEP 1: Find products where the TITLE or CONTENT matches the text ---
        $query1 = new WP_Query( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 15,
            's'              => $term,
            'fields'         => 'ids', // ONLY get IDs (makes this query super fast)
        ) );
        $title_match_ids = $query1->posts;

        // --- STEP 2: Find Categories or Tags where the NAME matches the text ---
        $matching_terms = get_terms( array(
            'taxonomy'   => array( 'product_cat', 'product_tag' ),
            'name__like' => $term, // e.g. typing "shirt" finds the "Shirts" category
            'fields'     => 'ids',
            'hide_empty' => true,
        ) );

        $category_match_ids = array();
        if ( ! empty( $matching_terms ) && ! is_wp_error( $matching_terms ) ) {
            // Get all product IDs that belong to those matching categories/tags
            $category_match_ids = get_objects_in_term( $matching_terms, array( 'product_cat', 'product_tag' ) );
        }

        // --- STEP 3: Merge the IDs and remove duplicates ---
        $all_ids = array_unique( array_merge( $title_match_ids, $category_match_ids ) );

        // If no products matched the title or the categories, stop here.
        if ( empty( $all_ids ) ) {
            return rest_ensure_response( array() );
        }

        // --- STEP 4: Fetch the final product data for React ---
        $final_query = new WP_Query( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 5,         // Max 5 results in the dropdown
            'post__in'       => $all_ids,  // Pass in our merged IDs
            'orderby'        => 'post__in' // Keep the most relevant matches at the top
        ) );

        $products = array();

        if ( $final_query->have_posts() ) {
            while ( $final_query->have_posts() ) {
                $final_query->the_post();
                $product = wc_get_product( get_the_ID() );
                
                $products[] = array(
                    'id'    => $product->get_id(),
                    'title' => $product->get_name(),
                    'url'   => $product->get_permalink(),
                    'price' => $product->get_price_html(),
                    'image' => get_the_post_thumbnail_url( $product->get_id(), 'thumbnail' ),
                );
            }
            wp_reset_postdata();
        }

        return rest_ensure_response( $products );
    }
}

new Woo_React_Search();