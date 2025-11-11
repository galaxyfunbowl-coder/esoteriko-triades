<?php
if (!defined('ABSPATH')) exit;

class GLR_Admin {
  public static function register_settings() {
    register_setting('glr_settings','glr_db_host');
    register_setting('glr_settings','glr_db_name');
    register_setting('glr_settings','glr_db_user');
    register_setting('glr_settings','glr_db_pass');
  }

  public static function admin_assets($hook) {
    if (strpos($hook,'glr-scores') !== false) {
      $file = GLR_DIR.'assets/dist/assets/admin.js';
      $ver = file_exists($file) ? filemtime($file) : GLR_VER;
      wp_enqueue_script('glr-admin', GLR_URL.'assets/dist/assets/admin.js', ['wp-element'], $ver, true);
      wp_localize_script('glr-admin','GLR',[
        'rest'=>esc_url_raw( rest_url('glr/v1/') ),
        'nonce'=>wp_create_nonce('wp_rest')
      ]);
    }
    if (strpos($hook,'glr-manage') !== false) {
      $file = GLR_DIR.'assets/dist/assets/admin-manage.js';
      $ver = file_exists($file) ? filemtime($file) : GLR_VER;
      wp_enqueue_script('glr-admin-manage', GLR_URL.'assets/dist/assets/admin-manage.js', ['wp-element'], $ver, true);
      wp_localize_script('glr-admin-manage','GLR',[
        'rest'=>esc_url_raw( rest_url('glr/v1/') ),
        'nonce'=>wp_create_nonce('wp_rest')
      ]);
    }
    if (strpos($hook,'glr-setup') !== false) {
      $file = GLR_DIR.'assets/dist/assets/admin-setup.js';
      $ver = file_exists($file) ? filemtime($file) : GLR_VER;
      wp_enqueue_script('glr-admin-setup', GLR_URL.'assets/dist/assets/admin-setup.js', ['wp-element'], $ver, true);
      wp_localize_script('glr-admin-setup','GLR',[
        'rest'=>esc_url_raw( rest_url('glr/v1/') ),
        'nonce'=>wp_create_nonce('wp_rest')
      ]);
    }
  }

  public static function render_settings_page() { ?>
    <div class='wrap'>
      <h1>Galaxy League Settings</h1>
      <form method='post' action='options.php'>
        <?php settings_fields('glr_settings'); do_settings_sections('glr_settings'); ?>
        <table class='form-table'>
          <tr><th>DB Host</th><td><input name='glr_db_host' class='regular-text' value='<?php echo esc_attr(get_option('glr_db_host','localhost'));?>'></td></tr>
          <tr><th>DB Name</th><td><input name='glr_db_name' class='regular-text' value='<?php echo esc_attr(get_option('glr_db_name',''));?>'></td></tr>
          <tr><th>DB User</th><td><input name='glr_db_user' class='regular-text' value='<?php echo esc_attr(get_option('glr_db_user',''));?>'></td></tr>
          <tr><th>DB Pass</th><td><input type='password' name='glr_db_pass' class='regular-text' value='<?php echo esc_attr(get_option('glr_db_pass',''));?>'></td></tr>
        </table>
        <?php submit_button(); ?>
      </form>
      <p>Run <code>sql/migrations.mysql.sql</code> on your external MySQL first.</p>
    </div>
  <?php }

  public static function render_scores_page() { ?>
    <div class='wrap'>
      <h1>Enter Scores & Roll-offs</h1>
      <div id='glr-scores-root'></div>
    </div>
  <?php }

  public static function render_manage_page() { ?>
    <div class='wrap'>
      <h1>League Management</h1>
      <div id='glr-manage-root'></div>
    </div>
  <?php }

  public static function render_setup_page() { ?>
    <div class='wrap'>
      <h1>Setup Wizard (1η Αγωνιστική)</h1>
      <div id='glr-setup-root'></div>
    </div>
  <?php }
}
