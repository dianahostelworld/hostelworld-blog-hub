<?php
/**
 * Plugin Name:       Hostelworld Blog Hubs
 * Plugin URI:        https://www.hostelworld.com/blog/
 * Description:       Adds editable Continent, Country, City and State Hub page templates for the blog, plus a Country Hub variant that lists states instead of cities. All content is edited on the Page itself, in the panel below the editor.
 * Version:           5.18.3
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Hostelworld
 * Text Domain:       hostelworld
 *
 * HOW TO USE
 * 1. Plugins > Add New > Upload Plugin > choose this zip > Install > Activate.
 * 2. Pages > Add New > give it a title, e.g. "Europe".
 * 3. Sidebar > Page > Template > "Continent Hub". Publish.
 * 4. The "Continent hub content" panel appears below the editor, pre-filled with the
 *    Europe copy as a starting point. Edit it and hit Update.
 *
 * @package Hostelworld
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'HW_HUB_VERSION', '5.18.3' );
// Kept as-is deliberately: this string is stored on every Page already
// using the template. Renaming it would unassign them.
define( 'HW_HUB_TEMPLATE_KEY', 'hw-hub-destination' );
define( 'HW_HUB_COUNTRY_TEMPLATE_KEY', 'hw-hub-country' );
define( 'HW_HUB_CITY_TEMPLATE_KEY', 'hw-hub-city' );
// A country hub that lists states as cards, grouped by region, instead of a
// list of cities. Built for the USA, where 50 rows would be far too long.
define( 'HW_HUB_COUNTRY_STATES_TEMPLATE_KEY', 'hw-hub-country-states' );
define( 'HW_HUB_STATE_TEMPLATE_KEY', 'hw-hub-state' );
define( 'HW_HUB_PATH', plugin_dir_path( __FILE__ ) );
define( 'HW_HUB_URL', plugin_dir_url( __FILE__ ) );
// Public, not protected. A leading underscore makes meta "protected" in
// WordPress, and protected meta is skipped by most translation and import
// tooling — Polylang strips it before copying, and DeepL never sees it.
define( 'HW_HUB_META_PREFIX', 'hw_hub_' );
define( 'HW_HUB_LEGACY_META_PREFIX', '_hw_hub_' );

// Production Static Pages API. Deliberately a constant and not a setting:
// this runs on the live blog, and a hub quietly reading staging would publish
// fictional ratings and prices. Staging is also not reachable outside the VPN.
define( 'HW_HUB_API_BASE', 'https://api.hostelworld.com/static-pages-api/1.0' );

require_once HW_HUB_PATH . 'includes/defaults.php';
require_once HW_HUB_PATH . 'includes/defaults-country.php';
require_once HW_HUB_PATH . 'includes/defaults-city.php';
require_once HW_HUB_PATH . 'includes/defaults-states.php';
require_once HW_HUB_PATH . 'includes/schema.php';
require_once HW_HUB_PATH . 'includes/schema-country.php';
require_once HW_HUB_PATH . 'includes/schema-city.php';
require_once HW_HUB_PATH . 'includes/schema-states.php';
require_once HW_HUB_PATH . 'includes/data.php';
require_once HW_HUB_PATH . 'includes/data-states.php';
require_once HW_HUB_PATH . 'includes/continents.php';
require_once HW_HUB_PATH . 'includes/hostels-embed.php';
require_once HW_HUB_PATH . 'includes/destination-box.php';
require_once HW_HUB_PATH . 'includes/helpers.php';
require_once HW_HUB_PATH . 'includes/admin.php';
require_once HW_HUB_PATH . 'includes/deepl.php';
require_once HW_HUB_PATH . 'includes/import.php';
require_once HW_HUB_PATH . 'includes/export.php';
require_once HW_HUB_PATH . 'includes/good-for-bulk.php';
require_once HW_HUB_PATH . 'includes/report.php';
require_once HW_HUB_PATH . 'includes/checkup.php';
require_once HW_HUB_PATH . 'includes/hostels-gaps.php';
require_once HW_HUB_PATH . 'includes/postcard.php';
require_once HW_HUB_PATH . 'includes/trending.php';
require_once HW_HUB_PATH . 'includes/team.php';

/**
 * Adds the template to the dropdown on the Page edit screen.
 */
function hw_hub_register_template( $templates ) {
	$templates[ HW_HUB_TEMPLATE_KEY ]         = __( 'Continent Hub', 'hostelworld' );
	$templates[ HW_HUB_COUNTRY_TEMPLATE_KEY ] = __( 'Country Hub', 'hostelworld' );
	$templates[ HW_HUB_CITY_TEMPLATE_KEY ]    = __( 'City Hub', 'hostelworld' );
	$templates[ HW_HUB_COUNTRY_STATES_TEMPLATE_KEY ] = __( 'Country Hub (states)', 'hostelworld' );
	$templates[ HW_HUB_STATE_TEMPLATE_KEY ]          = __( 'State Hub', 'hostelworld' );

	return $templates;
}
add_filter( 'theme_page_templates', 'hw_hub_register_template' );

/**
 * Serves the plugin's template file when a Page has the hub template selected.
 */
function hw_hub_use_template( $template ) {
	if ( ! is_singular( 'page' ) ) {
		return $template;
	}

	$slug = get_page_template_slug( get_queried_object_id() );

	if ( HW_HUB_COUNTRY_STATES_TEMPLATE_KEY === $slug ) {
		$file = HW_HUB_PATH . 'templates/country-states-hub.php';

		return file_exists( $file ) ? $file : $template;
	}

	if ( HW_HUB_STATE_TEMPLATE_KEY === $slug ) {
		$file = HW_HUB_PATH . 'templates/state-hub.php';

		return file_exists( $file ) ? $file : $template;
	}

	if ( HW_HUB_CITY_TEMPLATE_KEY === $slug ) {
		$file = HW_HUB_PATH . 'templates/city-hub.php';

		return file_exists( $file ) ? $file : $template;
	}

	if ( HW_HUB_COUNTRY_TEMPLATE_KEY === $slug ) {
		$file = HW_HUB_PATH . 'templates/country-hub.php';

		return file_exists( $file ) ? $file : $template;
	}

	if ( HW_HUB_TEMPLATE_KEY !== $slug ) {
		return $template;
	}

	$file = HW_HUB_PATH . 'templates/continent-hub.php';

	return file_exists( $file ) ? $file : $template;
}
add_filter( 'template_include', 'hw_hub_use_template' );

/**
 * Loads the hub stylesheet, and only on pages that use the template.
 */
function hw_hub_front_assets() {
	if ( ! is_singular( 'page' ) ) {
		return;
	}

	if ( ! in_array( get_page_template_slug( get_queried_object_id() ), hw_hub_template_keys(), true ) ) {
		return;
	}

	// Leaflet, for the neighbourhood and region maps. OpenStreetMap tiles need no key.
	if ( in_array( get_page_template_slug( get_queried_object_id() ), array( HW_HUB_CITY_TEMPLATE_KEY, HW_HUB_STATE_TEMPLATE_KEY ), true ) ) {
		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), '1.9.4', true );
	}

	wp_enqueue_style( 'hw-hub', HW_HUB_URL . 'assets/hub.css', array(), HW_HUB_VERSION );
	wp_enqueue_script( 'hw-hub', HW_HUB_URL . 'assets/hub.js', array(), HW_HUB_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'hw_hub_front_assets' );

/**
 * Every template slug this plugin owns.
 *
 * @return array
 */
function hw_hub_template_keys() {
	return array(
		HW_HUB_TEMPLATE_KEY,
		HW_HUB_COUNTRY_TEMPLATE_KEY,
		HW_HUB_CITY_TEMPLATE_KEY,
		HW_HUB_COUNTRY_STATES_TEMPLATE_KEY,
		HW_HUB_STATE_TEMPLATE_KEY,
	);
}

/**
 * Opens the page with the site's own header.
 *
 * Classic themes get get_header(). Block themes have no header.php, so the
 * document shell is opened here and the site's header template part rendered.
 */
function hw_hub_site_header() {
	if ( hw_hub_is_block_theme() ) {
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
		<head>
			<meta charset="<?php bloginfo( 'charset' ); ?>">
			<meta name="viewport" content="width=device-width, initial-scale=1">
			<?php wp_head(); ?>
		</head>
		<body <?php body_class(); ?>>
		<?php
		wp_body_open();

		if ( function_exists( 'block_header_area' ) ) {
			block_header_area();
		}

		return;
	}

	get_header();
}

/**
 * Closes the page with the site's own footer.
 */
function hw_hub_site_footer() {
	if ( hw_hub_is_block_theme() ) {
		if ( function_exists( 'block_footer_area' ) ) {
			block_footer_area();
		}
		wp_footer();
		echo '</body></html>';
		return;
	}

	get_footer();
}

/**
 * True only for a block theme with no classic header.php to fall back on.
 */
function hw_hub_is_block_theme() {
	if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
		return false;
	}

	if ( file_exists( get_stylesheet_directory() . '/header.php' ) || file_exists( get_template_directory() . '/header.php' ) ) {
		return false;
	}

	return true;
}
