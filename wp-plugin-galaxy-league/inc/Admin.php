<?php
if (!defined('ABSPATH')) exit;

class GLR_Admin {
  public static function register_settings() {
    register_setting('glr_settings', 'glr_db_host');
    register_setting('glr_settings', 'glr_db_name');
    register_setting('glr_settings', 'glr_db_user');
    register_setting('glr_settings', 'glr_db_pass');
  }

  public static function render_settings_page() { ?>
    <div class="wrap">
      <h1>Galaxy League Settings</h1>
      <form method="post" action="options.php">
        <?php settings_fields('glr_settings'); do_settings_sections('glr_settings'); ?>
        <table class="form-table" role="presentation">
          <tr><th scope="row"><label>DB Host</label></th>
            <td><input type="text" name="glr_db_host" value="<?php echo esc_attr(get_option('glr_db_host','localhost')); ?>" class="regular-text"></td></tr>
          <tr><th scope="row"><label>DB Name</label></th>
            <td><input type="text" name="glr_db_name" value="<?php echo esc_attr(get_option('glr_db_name','')); ?>" class="regular-text"></td></tr>
          <tr><th scope="row"><label>DB User</label></th>
            <td><input type="text" name="glr_db_user" value="<?php echo esc_attr(get_option('glr_db_user','')); ?>" class="regular-text"></td></tr>
          <tr><th scope="row"><label>DB Pass</label></th>
            <td><input type="password" name="glr_db_pass" value="<?php echo esc_attr(get_option('glr_db_pass','')); ?>" class="regular-text"></td></tr>
        </table>
        <?php submit_button(); ?>
      </form>
      <p>Run <code>sql/migrations.mysql.sql</code> on your external MySQL. Then fill the credentials above.</p>
    </div>
  <?php }
}
