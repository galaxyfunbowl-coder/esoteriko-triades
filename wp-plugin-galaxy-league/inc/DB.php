<?php
if (!defined('ABSPATH')) exit;

class GLR_DB {
  private static ?PDO $pdo = null;

  public static function pdo(): PDO {
    if (self::$pdo) return self::$pdo;

    $host = get_option('glr_db_host','');
    $name = get_option('glr_db_name','');
    $user = get_option('glr_db_user','');
    $pass = get_option('glr_db_pass','');
    if (!$host || !$name || !$user) throw new Exception('GLR: External DB settings missing.');

    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $opt = [
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,
    ];
    self::$pdo = new PDO($dsn,$user,$pass,$opt);
    return self::$pdo;
  }
}
