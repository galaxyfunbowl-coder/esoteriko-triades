<?php
/**
 * Plugin Name: Galaxy League Results
 * Description: Bowling triads league: external DB, React UI, Admin scores, roll-offs, HC rules.
 * Version: 0.2.0
 * Author: Galaxy Fun ''n Bowl
 */

if (!defined('ABSPATH')) exit;

define('GLR_VER', '0.2.0');
define('GLR_DIR', plugin_dir_path(__FILE__));
define('GLR_URL', plugin_dir_url(__FILE__));

require_once GLR_DIR . 'inc/DB.php';
require_once GLR_DIR . 'inc/Admin.php';
require_once GLR_DIR . 'inc/Standings.php';
require_once GLR_DIR . 'inc/Ajax.php';
require_once GLR_DIR . 'inc/Logic.php';
require_once GLR_DIR . 'inc/Api.php';

class GalaxyLeagueResults {
  public function __construct() {
    add_action('admin_menu', [$this, 'admin_menu']);
    add_action('admin_init', ['GLR_Admin', 'register_settings']);
    add_action('admin_enqueue_scripts', ['GLR_Admin', 'admin_assets']);

    add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

    add_shortcode('league_standings', ['GLR_Standings', 'shortcode']);

    add_action('wp_ajax_glr_get_standings', ['GLR_Ajax', 'get_standings']);
    add_action('wp_ajax_nopriv_glr_get_standings', ['GLR_Ajax', 'get_standings']);
  }

  public function admin_menu() {
    add_menu_page('Galaxy League','Galaxy League','manage_options','glr-settings',['GLR_Admin','render_settings_page'],'dashicons-chart-bar',58);
    add_submenu_page('glr-settings','Scores','Scores','manage_options','glr-scores',[ 'GLR_Admin','render_scores_page' ]);
  }

  public function enqueue_assets() {
    wp_enqueue_script('glr-app', GLR_URL.'assets/dist/assets/main.js', [], GLR_VER, true);
    wp_localize_script('glr-app','GLR',[
      'ajaxUrl'=>admin_url('admin-ajax.php'),
      'nonce'=>wp_create_nonce('glr_nonce'),
      'rest'=>esc_url_raw( rest_url('glr/v1/') )
    ]);
    wp_enqueue_style('glr-style', GLR_URL.'assets/dist/assets/main.css', [], GLR_VER);
  }
}
new GalaxyLeagueResults();
