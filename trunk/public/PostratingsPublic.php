<?php namespace Pub;

    /**
     * The public-facing functionality of the plugin.
     *
     * @link       https://github.com/crazypsycho
     * @since      1.0.0
     *
     * @package    Postratings
     * @subpackage Postratings/public
     */
use Inc\Postratings;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Postratings
 * @subpackage Postratings/public
 * @author     crazypsycho <wordpress@hennewelt.de>
 */
class PostratingsPublic {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $pluginName The ID of this plugin.
     */
    private $pluginName;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * The options from admin-page
     *
     * @since       1.0.3
     * @access      private
     * @var         array[]
     */
    private $options;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $pluginName The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct( $pluginName, $version ) {

        $this->pluginName = $pluginName;
        $this->version = $version;
        $this->options =  Postratings::getOptions();

        add_shortcode( 'postrating', array( $this, 'postratingsShortcode' ) );
        add_shortcode( 'postratings', array( $this, 'postratingsShortcode' ) );

    }

    public function postratingsShortcode( $args, $content = '' ) {
        $postId = $this->getPostIdFromArgs( $args );
        $rating = Postratings::getRating( $postId );

        return Postratings::getResultHtml( $rating );
    }


    /**
     * Get postid from args.
     *  First it trys to get id from first param, so you can use
     *  [postrating 541]
     *
     * If this it not set, it looks for postid argument
     * If this is not set, it use global post-id from loop
     */
    private function getPostIdFromArgs( $args ) {
        if ( isset( $args[0] ) && is_numeric( $args[0] ) ) {
            return $args[0];
        } else if ( isset( $args['postid'] ) ) {
            return $args['postid'];
        } else {
            global $post;
            if ( !empty( $post->ID ) ) {
                return $post->ID;
            }
        }
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueueStyles() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in PostratingsLoader as all of the hooks are defined
         * in that particular class.
         *
         * The PostratingsLoader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        if (empty($this->options['noDefaultStyle'])) {
            wp_enqueue_style( $this->pluginName, plugin_dir_url( __FILE__ ) . 'css/postratings-public.css', array(), $this->version, 'all' );
        }

        if (empty($this->options['noDashicons'])) {
            wp_enqueue_style( 'dashicons' );
        }
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueueScripts() {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in PostratingsLoader as all of the hooks are defined
         * in that particular class.
         *
         * The PostratingsLoader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        if (is_user_logged_in() || empty( $this->options['onlyLoggedIn'] )) {
            wp_enqueue_script( $this->pluginName, plugin_dir_url( __FILE__ ) . 'js/postratings-public.js', array( 'jquery' ), $this->version, false );

            wp_localize_script( $this->pluginName, $this->pluginName, array(
                'adminAjax' => admin_url( 'admin-ajax.php' ),
            ) );
        }
    }

}
