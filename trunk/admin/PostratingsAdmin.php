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
        $this->options = Postratings::getOptions();
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

        // Register ajax
        add_action( 'wp_ajax_postrating', array( $this, 'saveRating' ) );
        add_action( 'wp_ajax_nopriv_postrating', array( $this, 'saveRating' ) );

    }

    /**
     * Save rating to post-meta
     *
     * @param null $values
     */
    public function saveRating( $values = null, $noEcho = false ) {
        if ( !$noEcho ) {
            header( 'Content-Type: application/json' );
        }

        if ( empty( $values ) ) {
            $values = [];
            $values['postId'] = filter_input( INPUT_GET, 'postid' );
            $values['rating'] = filter_input( INPUT_GET, 'rating' );
            $values['ratingKey'] = filter_input( INPUT_GET, 'key' );
        }

        $metaKey = !empty( $values['ratingKey'] ) ? '_postratings_' . $values['ratingKey'] : '_postratings';
        $ratings = get_post_meta( $values['postId'], $metaKey, true );
        $message = '';

        if ( empty( $values['postId'] ) || empty( $values['rating'] ) ) {
            if ( !$noEcho ) {
                echo $this->getResultOutput( $values['postId'], false, $values['ratingKey'],'Rating empty' );
                exit();
            } else {
                return;
            }
        }

        $currentUser = get_current_user_id();

        if ( empty( $currentUser ) && !empty( $this->options['onlyLoggedIn'] ) ) {
            // login required -> abort

            if ( !$noEcho ) {
                echo $this->getResultOutput( $values['postId'], false, $values['ratingKey'], 'Login required' );
                exit();
            } else {
                return;
            }
        }

        if ( empty( $ratings ) ) {
            // add first rating
            // contains: userid, ip, rating, time
            $ratings = array();
        }

        $newRating = array(
            'userid' => $currentUser,
            'ip' => $_SERVER['REMOTE_ADDR'],
            'rating' => $values['rating'],
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
                && $rating['time'] + 86400 > time() ) {
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
        update_post_meta( $values['postId'], $metaKey, $ratings );

        if ( !$noEcho ) {
            echo $this->getResultOutput( $values['postId'], true, $values['ratingKey'], $message );
            exit();
        }
    }

    private function getResultOutput( $postId, $success = true, $key, $message = '' ) {
        $currentUser = get_current_user_id();
        // load new ratings and html for stars
        $newRatings = Postratings::getRating( $postId, $key );
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
