<?php
if (!defined('ABSPATH')) exit;

class GLR_Standings {
  public static function shortcode($atts) {
    // Container for React app
    return '<div id="glr-root" data-view="standings"></div>';
  }
}
