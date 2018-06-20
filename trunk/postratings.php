<?php

use Inc\Postratings;
use Inc\PostratingsActivator;
use Inc\PostratingsDeactivator;

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/RTO-Websites/wordpress-postratings
 * @since             1.0.0
 * @package           Postratings
 *
 * @wordpress-plugin
 * Plugin Name:       PostRatings
 * Plugin URI:        https://github.com/RTO-Websites/wordpress-postratings
 * Description:       Simple plugin to add star-rating to posts
 * Version:           2.0.0
 * Author:            rtowebsites
 * Author URI:        https://www.rto.de
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       postratings
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The class responsible for auto loading classes.
 */
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/PostratingsActivator.php
 */
function activatePostratings() {
	PostratingsActivator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/PostratingsDeactivator.php
 */
function deactivatePostratings() {
	PostratingsDeactivator::deactivate();
}

register_activation_hook( __FILE__, 'activatePostratings' );
register_deactivation_hook( __FILE__, 'deactivatePostratings' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function runPostratings() {

	$plugin = new Postratings();
	$plugin->run();

}
runPostratings();
