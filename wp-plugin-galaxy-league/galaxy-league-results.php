<?php
/**
 * Plugin Name: Galaxy League Results
 * Description: Standings & results for bowling triads. External DB via PDO, React frontend, shortcodes.
 * Version: 0.1.0
 * Author: Galaxy Fun 'n Bowl
 */

if (!defined('ABSPATH')) exit;

define('GLR_VER', '0.1.0');
define('GLR_DIR', plugin_dir_path(__FILE__));
define('GLR_URL', plugin_dir_url(__FILE__));

require_once GLR_DIR . 'inc/DB.php';
require_once GLR_DIR . 'inc/Admin.php';
require_once GLR_DIR . 'inc/Standings.php';
require_once GLR_DIR . 'inc/Ajax.php';

class GalaxyLeagueResults {
  public function __construct() {
    add_action('admin_menu', [$this, 'admin_menu']);
    add_action('admin_init', ['GLR_Admin', 'register_settings']);

    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

    add_shortcode('league_standings', ['GLR_Standings', 'shortcode']);

    add_action('wp_ajax_glr_get_standings', ['GLR_Ajax', 'get_standings']);
    add_action('wp_ajax_nopriv_glr_get_standings', ['GLR_Ajax', 'get_standings']);
  }

  public function admin_menu() {
    add_menu_page(
      'Galaxy League',
      'Galaxy League',
      'manage_options',
      'glr-settings',
      ['GLR_Admin', 'render_settings_page'],
      'dashicons-chart-bar',
      58
    );
  }

  public function enqueue_assets() {
    // Built bundle (after npm run build). For dev we can load Vite too, but keep it simple.
    wp_enqueue_script(
      'glr-app',
      GLR_URL . 'assets/dist/assets/main.js',
      [],
      GLR_VER,
      true
    );
    wp_localize_script('glr-app', 'GLR', [
      'ajaxUrl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce('glr_nonce'),
    ]);
    wp_enqueue_style('glr-style', GLR_URL . 'assets/dist/assets/main.css', [], GLR_VER);
  }
}
new GalaxyLeagueResults();
