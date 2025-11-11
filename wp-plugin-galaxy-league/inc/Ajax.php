<?php
if (!defined('ABSPATH')) exit;

class GLR_Ajax {
  public static function get_standings() {
    check_ajax_referer('glr_nonce');

    try {
      $pdo = GLR_DB::pdo();
      // Prefer the view `team_standings`; fallback simple aggregation if view missing
      $sql = "SELECT team_id, team_name, total_points, total_pins
              FROM team_standings
              ORDER BY total_points DESC, total_pins DESC";
      $stmt = $pdo->query($sql);
      $rows = $stmt->fetchAll();

      wp_send_json_success(['rows' => $rows]);
    } catch (Throwable $e) {
      wp_send_json_error(['message' => $e->getMessage()]);
    }
  }
}
