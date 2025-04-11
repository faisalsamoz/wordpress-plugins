<?php
/*
* Plugin Name: Connect Now
* Description: Connect Now: Effortlessly schedule meetings and appointments directly from your WordPress site.
* Version: 1.0
* Author: Codeavour
*/


//include shortcodes
require_once plugin_dir_path(__FILE__) . 'includes/cnow-short-codes.php';

//include scripts
require_once plugin_dir_path(__FILE__) . 'includes/cnow-enqueue-scripts.php';

//include admin settings page
require_once plugin_dir_path(__FILE__). 'admin/cnow-settings-page.php';