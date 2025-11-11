<?php
if (!defined('ABSPATH')) exit;

class GLR_Ajax {
  public static function get_standings() {
    check_ajax_referer('glr_nonce');
    try {
      $pdo = GLR_DB::pdo();
      $stmt = $pdo->query('SELECT team_id, team_name, total_points, total_pins FROM team_standings ORDER BY total_points DESC, total_pins DESC');
      wp_send_json_success(['rows'=>$stmt->fetchAll()]);
    } catch(Throwable $e) {
      wp_send_json_error(['message'=>$e->getMessage()]);
    }
  }
}
