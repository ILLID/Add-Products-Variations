<?php
/*
Plugin Name: Add Products Variations
Description: Display products variations on shop pages together with other products types
Version: 1.00
Author: ILLID
Author URI: https://wpmichael.com
Text Domain: add-products-variations
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Add_Products_Variations {

    /**
     * @var Add_Products_Variations The single instance of the class
     */
    protected static $_instance = null;

    /**
     * Main Add_Products_Variations Instance
     *
     * Ensures only one instance of Add_Products_Variations is loaded or can be loaded.
     *
     * @static
     * @return Add_Products_Variations - Main instance
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {

        add_action( 'admin_menu',  array( $this, 'admin_menu' ) );

        add_action( 'admin_init', array( $this, 'register_settings' ) );

        add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 999 );

    }

    /*
     * Register new settings page
     */
    public function admin_menu() {
        add_options_page( __( 'Add Products Variations settings page', 'add-products-variations' ), __( 'Add Products Variations', 'add-products-variations' ), 'manage_options', 'adv-plugin', array( $this, 'render_settings_page' ) );
    }

    /*
     * Settings page content
     */
    public function render_settings_page() {
        ?>
        <h2><?php _e( 'Add Products Variations settings page', 'add-products-variations' ); ?></h2>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'adv_options' );
            do_settings_sections( 'adv_plugin' ); ?>
            <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save', 'add-products-variations' ); ?>" />
        </form>
        <?php
    }

    /*
     * Register settings
     */
    public function register_settings() {

        register_setting( 'adv_options', 'adv_options' );
        add_settings_section( 'api_settings', '', array( $this, 'adv_plugin_section_text' ), 'adv_plugin' );

        add_settings_field( 'adv_plugin_settings_excludes', __( 'Exclude', 'add-products-variations' ), array( $this, 'adv_plugin_settings_excludes' ), 'adv_plugin', 'api_settings' );

    }

    /*
     * Section description
     */
    function adv_plugin_section_text() {
        echo '<p>' . __( 'Specify product IDs that must be excluded from shop pages. Specify variable product ID to exclude all its child products.', 'add-products-variations' ) . '</p>';
    }

    /*
     * Settings page option
     */
    function adv_plugin_settings_excludes() {
        $options = get_option( 'adv_options' );
        echo "<input style='min-width: 300px;' id='adv_plugin_settings_excludes' name='adv_options[excluded]' type='text' value='" . esc_attr( $options['excluded'] ) . "' />";
    }

    /*
     * Eable product_variation post type for loops
     */
    public function pre_get_posts( $query ) {

        if ( is_admin() || ! is_shop() || ! is_main_query() ) {
            return $query;
        }

        /*
         * IDs of products that need to be excluded
         * if variable product ID specified - will be excluded all its child products
         */
        $options = get_option( 'adv_options' );
        $exclude_products = array();

        if ( $options && isset( $options['excluded'] ) ) {
            $exclude_products = explode(',', $options['excluded'] );
            $exclude_products = array_map( 'trim', $exclude_products );
        }

        $query->set( 'post_type', array( 'product', 'product_variation' ) );

        if ( $exclude_products ) {

            $child_ids = array();

            foreach ( $exclude_products as $exluded_id ) {

                $product = wc_get_product( $exluded_id );

                if ( is_a( $product, 'WC_Product' ) && $product->is_type( 'variable' ) && sizeof( $product->get_children() ) > 0 ) {
                    foreach ( $product->get_children() as $child_id ) {
                        $child_ids[] = $child_id;
                    }
                }

            }

            if ( ! empty( $child_ids ) ) {
                $exclude_products = array_merge( $exclude_products, $child_ids );
            }

            $query->set( 'post__not_in', $exclude_products );

        }

    }

}

Add_Products_Variations::instance();