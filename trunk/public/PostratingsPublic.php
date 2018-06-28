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
use Admin\PostratingsAdmin;
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

    private $textdomain;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string $pluginName The name of the plugin.
     * @param      string $version The version of this plugin.
     */
    public function __construct( $pluginName, $version ) {

        $this->pluginName = $pluginName;
        $this->textdomain = $pluginName;
        $this->version = $version;
        $this->options = Postratings::getOptions();

        // generate field-key from sanitized label
        foreach ( $this->options['commentFields'] as $orgKey => $label ) {
            if ( empty( $label ) ) {
                continue;
            }
            $key = sanitize_key( $label );
            $this->options['commentFields'][$key] = $label;
            unset( $this->options['commentFields'][$orgKey] );
        }

        add_shortcode( 'postrating', array( $this, 'postratingsShortcode' ) );
        add_shortcode( 'postratings', array( $this, 'postratingsShortcode' ) );

        if ( true || !empty( $this->options['appendToComments'] ) ) {
            add_action( 'comment_form_after_fields', array( $this, 'appendStarsToCommentForm' ) );
            add_action( 'comment_form_logged_in_after', array( $this, 'appendStarsToCommentForm' ) );
            //add_filter( 'comment_form_defaults', array( $this, 'addHiddenInputToCommentForm' ) );
            add_action( 'comment_form_after_fields', array( $this, 'addHiddenInputToCommentForm' ) );
            add_action( 'comment_form_logged_in_after', array( $this, 'addHiddenInputToCommentForm' ) );

            add_action( 'comment_post', array( $this, 'saveComment' ) );

            add_filter( 'comment_text', array( $this, 'addRatingsToComment' ), 100 );
        }
    }

    /**
     * Add rating result to a comment
     *
     * @param $content
     * @param null $comment
     * @param array $args
     * @return string
     */
    public function addRatingsToComment( $content, $comment = null, $args = [] ) {
        $ratings = get_comment_meta( get_comment_ID(), 'postrating', true );

        if ( empty( $ratings ) ) {
            return $content;
        }

        $output = '';
        $ratings = json_decode( $ratings );

        if ( !empty( $this->options['showCommentSummary'] ) ) {
            $ratings->Summary = [ 'count' => 0, 'rating' => 0 ];
        }

        foreach ( $ratings as $key => $rating ) {
            $label = !empty( $this->options['commentFields'][$key] ) ? $this->options['commentFields'][$key] : __( $key, $this->textdomain );

            if ( is_array( $rating ) && !empty( $rating['count'] ) ) {
                $rating = $rating['rating'] / $rating['count'];
            }

            $output .= '<div class="postrating-field">';
            $output .= '<div class="postrating-label">' . $label . '</div>';
            $output .= '<div class="postratings no-action">';
            $output .= Postratings::getStarHtml( array(
                'ratingResult' => $rating,
            ) );
            $output .= '</div>';
            $output .= '</div>';

            if ( isset( $ratings->Summary ) ) {
                $ratings->Summary['count'] += 1;
                $ratings->Summary['rating'] += $rating;
            }
        }

        return $content . $output;

    }

    /**
     * Caller for Admin-SaveRating
     *
     * @param $values
     */
    public function saveRating( $values ) {
        ob_start();
        PostratingsAdmin::getInstance()->saveRating( $values, true );
        ob_clean();
    }

    /**
     * Saves rating from comment
     *
     * @param $commentId
     */
    public function saveComment( $commentId ) {
        $json = filter_input( INPUT_POST, 'postrating-values' );
        if ( empty( $json ) ) {
            return;
        }
        $newRatings = json_decode( $json );

        foreach ( $newRatings as $key => $value ) {
            $this->saveRating( array(
                'postId' => filter_input( INPUT_POST, 'comment_post_ID' ),
                'ratingKey' => $key,
                'rating' => $value,
            ) );

            add_comment_meta( $commentId, 'postrating', $json );
        }
    }

    /**
     * Adds a hidden field to comment-form.
     * Will be filled with values from rating
     *
     * @param $default#
     */
    public function addHiddenInputToCommentForm( $default ) {
        //$commenter = wp_get_current_commenter();
        //$default['fields']['postratings'] = '<input type="hidden" name="postrating-comment" />';
        echo '<input type="hidden" name="postrating-values" class="postrating-values" />';
        //return $default;
    }

    /**
     * Append the stars to comment-form
     */
    public function appendStarsToCommentForm() {
        $questions = $this->options['commentFields'];
        $output = '';

        foreach ( $questions as $label ) {
            if ( empty( $label ) ) {
                continue;
            }
            $key = sanitize_key( $label );

            $output .= '<div class="postrating-field">';
            $output .= '<div class="postrating-label">' . $label . '</div>';
            $output .= do_shortcode( '[postrating key=' . $key . ' nosubmit value=1]' );
            $output .= '</div>';
        }

        echo $output;
    }

    /**
     * Shortcode to add stars
     *  Args:
     *   key (for more than one field)
     *   nosubmit: disable ajax-submit
     *
     * @param $args
     * @param string $content
     * @return string
     */
    public function postratingsShortcode( $args = array(), $content = '' ) {
        if ( empty( $args ) ) {
            $args = array();
        }
        $postId = $this->getPostIdFromArgs( $args );
        $isSummaray = in_array( 'summary', $args, true );
        $key = !empty( $args['key'] ) ? $args['key'] : '';
        $rating = Postratings::getRating( $postId, $key, $isSummaray );
        $rating['class'] = '';

        if ( in_array( 'nosubmit', $args, true ) ) {
            $rating['class'] = ' no-submit';
        }
        if ( in_array( 'noaction', $args, true )
            || $isSummaray
        ) {
            $rating['class'] .= ' no-action';
        }

        if ( isset( $args['value'] ) ) {
            $rating['ratingResult'] = $args['value'];
        }

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

        if ( empty( $this->options['noDefaultStyle'] ) ) {
            wp_enqueue_style( $this->pluginName, plugin_dir_url( __FILE__ ) . 'css/postratings-public.css', array(), $this->version, 'all' );
        }

        if ( empty( $this->options['noDashicons'] ) ) {
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

        if ( is_user_logged_in() || empty( $this->options['onlyLoggedIn'] ) ) {
            wp_enqueue_script( $this->pluginName, plugin_dir_url( __FILE__ ) . 'js/postratings-public.js', array( 'jquery' ), $this->version, false );

            wp_localize_script( $this->pluginName, $this->pluginName, array(
                'adminAjax' => admin_url( 'admin-ajax.php' ),
            ) );
        }
    }

}
