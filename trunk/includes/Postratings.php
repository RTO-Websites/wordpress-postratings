<?php namespace Inc;

use Admin\PostratingsAdmin;
use Pub\PostratingsPublic;

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/crazypsycho
 * @since      1.0.0
 *
 * @package    Postratings
 * @subpackage Postratings/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Postratings
 * @subpackage Postratings/includes
 * @author     crazypsycho <wordpress@hennewelt.de>
 */
class Postratings {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      PostratingsLoader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $pluginName The string used to uniquely identify this plugin.
     */
    protected $pluginName;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct() {

        $this->pluginName = 'postratings';
        $this->version = '1.0.0';

        $this->loadDependencies();
        $this->setLocale();
        $this->defineAdminHooks();
        $this->definePublicHooks();

    }


    /**
     * Gets a rating
     *
     * @param $postId
     * @param string $key
     * @param bool $summary
     * @return array
     */
    public static function getRating( $postId, $key = '', $summary = false ) {
        if ( !$summary ) {
            $metaKey = !empty( $key ) ? '_postratings_' . $key : '_postratings';
            $ratings = get_post_meta( $postId, $metaKey, true );
        } else {
            // load all ratings
            $allMeta = get_post_meta( $postId, '', true );
            $ratings = array();

            foreach ( $allMeta as $metaKey => $data ) {
                if ( strpos( $metaKey, '_postratings' ) === 0 ) {
                    foreach ( $data as $row ) {
                        $row = unserialize( $row );
                        $ratings = array_merge( $ratings, $row );
                    }
                }
            }
        }

        if ( empty( $ratings ) ) {
            // currently no ratings
            return array(
                'postid' => $postId,
                'ratingCount' => 0,
                'ratingResult' => 0,
                'ratingAll' => 0,
                'key' => $key,
            );
        }

        $ratingAll = 0;
        $countRatings = 0;
        // loop all ratings to get full rating
        foreach ( $ratings as $rating ) {
            if ( empty( $rating['rating'] ) ) {
                continue;
            }
            // contains: userid, ip, rating, time
            $ratingAll += $rating['rating'];
            $countRatings+=1;
        }

        // calculate average rating
        $ratingResult = 0;
        if ( $countRatings ) {
            $ratingResult = round( $ratingAll / $countRatings, 1 );
        }
        return array(
            'postid' => $postId,
            'ratingCount' => $countRatings,
            'ratingResult' => $ratingResult,
            'ratingAll' => $ratingAll,
            'key' => $key,
        );
    }

    public static function getResultHtml( $rating ) {
        $output = '';

        $output .= '<div class="postratings ' . ( !empty( $rating['class'] ) ? $rating['class'] : '' ) . '" 
            data-key="' . $rating['key'] . '"
            data-postid="' . $rating['postid'] . '"
            data-rating="' . $rating['ratingResult'] . '"
            data-ratingall="' . $rating['ratingAll'] . '"
            data-ratingpercent="' . ( $rating['ratingResult'] / 5 * 100 ) . '%"
            data-ratingcount="' . $rating['ratingCount'] . '">';

        $output .= Postratings::getStarHtml( $rating );

        $output .= '</div>';

        return $output;
    }


    public static function getStarHtml( $rating ) {
        $output = '';
        for ( $i = 1; $i < 6; $i += 1 ) {
            $starClass = ' postrating-star-empty';
            if ( $i <= $rating['ratingResult'] ) {
                $starClass = ' postrating-star-full';
            } else if ( $i - 0.25 < $rating['ratingResult'] ) {
                $starClass = ' postrating-star-threequarter';
            } else if ( $i - 0.5 < $rating['ratingResult'] ) {
                $starClass = ' postrating-star-half';
            } else if ( $i - 0.75 < $rating['ratingResult'] ) {
                $starClass = ' postrating-star-quarter';
            }

            $output .= '<span class="postrating-star ' . $starClass . '"></span>';
        }

        return $output;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - PostratingsLoader. Orchestrates the hooks of the plugin.
     * - PostratingsI18n. Defines internationalization functionality.
     * - PostratingsAdmin. Defines all hooks for the admin area.
     * - PostratingsPublic. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function loadDependencies() {

        $this->loader = new PostratingsLoader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the PostratingsI18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function setLocale() {

        $pluginI18n = new PostratingsI18n();
        $pluginI18n->setDomain( $this->getPostratings() );

        $this->loader->addAction( 'plugins_loaded', $pluginI18n, 'loadPluginTextdomain' );

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function defineAdminHooks() {

        $pluginAdmin = new PostratingsAdmin( $this->getPostratings(), $this->getVersion() );

        $this->loader->addAction( 'admin_enqueue_scripts', $pluginAdmin, 'enqueueStyles' );
        $this->loader->addAction( 'admin_enqueue_scripts', $pluginAdmin, 'enqueueScripts' );

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function definePublicHooks() {

        $pluginPublic = new PostratingsPublic( $this->getPostratings(), $this->getVersion() );

        $this->loader->addAction( 'wp_enqueue_scripts', $pluginPublic, 'enqueueStyles' );
        $this->loader->addAction( 'wp_enqueue_scripts', $pluginPublic, 'enqueueScripts' );

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function getPostratings() {
        return $this->pluginName;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    PostratingsLoader    Orchestrates the hooks of the plugin.
     */
    public function getLoader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function getVersion() {
        return $this->version;
    }


    public static function getOptions() {
        return array(
            'onlyLoggedIn' => get_theme_mod( 'postratings_onlyLoggedIn', false ),
            'noDashicons' => get_theme_mod( 'postratings_noDashicons', false ),
            'noDefaultStyle' => get_theme_mod( 'postratings_noDefaultStyle', false ),
            'commentFields' => explode( "\n", get_theme_mod( 'postratings_commentFields', '' ) ),
            'showCommentSummary' => get_theme_mod( 'postratings_showCommentSummary', false ),
        );
    }
}
