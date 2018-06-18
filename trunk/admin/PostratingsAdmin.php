<?php namespace Admin;

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://github.com/crazypsycho
 * @since      1.0.0
 *
 * @package    Postratings
 * @subpackage Postratings/admin
 */


include_once( 'PostratingsThemeCustomizer.php' );

use Inc\Postratings;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Postratings
 * @subpackage Postratings/admin
 * @author     crazypsycho <wordpress@hennewelt.de>
 */
class PostratingsAdmin {

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

    private static $instance;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $pluginName The name of this plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct( $pluginName, $version ) {
        self::$instance = $this;

        $this->pluginName = $pluginName;
        $this->version = $version;
        $this->options =  Postratings::getOptions();
        // add options to customizer
        add_action( 'customize_register', array( new \PostratingsThemeCustomizer(), 'actionCustomizeRegister' ) );

        // add menu page to link to customizer
        add_action( 'admin_menu', function () {
            $returnUrl = urlencode( $_SERVER['REQUEST_URI'] );
            \add_menu_page(
                'PostRatings',
                'PostRatings',
                'edit_theme_options',
                'customize.php?return=' . $returnUrl . '&autofocus[panel]=postratings-panel',
                null,
                'dashicons-star-half'
            );
        } );

        /* $postgratingsPage = new MagicAdminPage(
            'post-ratings',
            'PostRatings',
            'PostRatings',
            null,
            'dashicons-star-half'
        );

        $postgratingsPage->addFields( array(
            'mainSettings' => array(
                'type' => 'headline',
                'title' => __( 'Main-Settings', $this->textdomain ),
            ),

            'onlyLoggedIn' => array(
                'type' => 'checkbox',
                'title' => __( 'Only logged users can vote', $this->textdomain ),
                'default' => false,
            ),

            'noDashicons' => array(
                'type' => 'checkbox',
                'title' => __( 'Don´t load dashicons', $this->textdomain ),
                'default' => false,
            ),

            'noDefaultStyle' => array(
                'type' => 'checkbox',
                'title' => __( 'Don´t load default styles', $this->textdomain ),
                'default' => false,
            ),
        ) ); */


        // Register ajax
        add_action( 'wp_ajax_postrating', array( $this, 'addRating' ) );
        add_action( 'wp_ajax_nopriv_postrating', array( $this, 'addRating' ) );
    }

    public function addRating() {
        header( 'Content-Type: application/json' );

        $postId = filter_input( INPUT_GET, 'postid' );
        $rating = filter_input( INPUT_GET, 'rating' );
        $ratings = get_post_meta( $postId, 'postratings', true );
        $message = '';

        if ( empty( $postId ) || !filter_has_var( INPUT_GET, 'rating' ) ) {
            exit();
        }

        $currentUser = get_current_user_id();

        if ( empty( $currentUser ) && !empty( $this->options['onlyLoggedIn'] ) ) {
            // login required -> abort
            echo $this->getResultOutput( $postId, false, 'Login required' );
            exit();
        }

        if ( empty( $ratings ) ) {
            // add first rating
            // contains: userid, ip, rating, time
            $ratings = array();
        }

        $newRating = array(
            'userid' => $currentUser,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'rating' => $rating,
            'time' => time(),
        );

        $addNew = true;
        // check if user has already rated or ip is already used
        foreach ( $ratings as $key => $rating ) {
            if ( !empty( $currentUser ) && $rating['userid'] == $currentUser ) {
                // override current rating of user
                $addNew = false;
                $ratings[$key] = $newRating;
                break;
            }

            if ( empty( $currentUser )
                && $rating['ip'] == $_SERVER['REMOTE_ADDR']
                && $rating['time'] +  86400  > time() )
            {
                // already voted last 24h -> replace rating
                $addNew = false;
                $ratings[$key] = $newRating;
                $message = 'IP has voted last 24h';
            }
        }

        // user hasnt rated -> add new
        if ( $addNew ) {
            $ratings[] = $newRating;
        }

        // write to post-meta
        update_post_meta( $postId, 'postratings', $ratings );

        echo $this->getResultOutput( $postId, true, $message );
        exit();
    }

    private function getResultOutput( $postId, $success = true, $message = '' ) {
        $currentUser = get_current_user_id();
        // load new ratings and html for stars
        $newRatings = Postratings::getRating( $postId );
        $newRatings['html'] = Postratings::getStarHtml( $newRatings );
        $newRatings['success'] = $success;
        $newRatings['message'] = $message;
        $newRatings['user'] = $currentUser;

        return json_encode( $newRatings );
    }

    /**
     * Register the stylesheets for the admin area.
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

        wp_enqueue_style( $this->pluginName, plugin_dir_url( __FILE__ ) . 'css/postratings-admin.css', array(), $this->version, 'all' );

    }

    /**
     * Register the JavaScript for the admin area.
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

        wp_enqueue_script( $this->pluginName, plugin_dir_url( __FILE__ ) . 'js/postratings-admin.js', array( 'jquery' ), $this->version, false );

    }

    static function getInstance() {
        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance ) {
            self::$instance = new self;
        }

        return self::$instance;
    }
}
