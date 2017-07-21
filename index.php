<?php
/*
Plugin Name: Persian Gravity Forms
Plugin URI: https://wordpress.org/plugins/persian-gravity-forms/
Description: Gravity Forms for Iranian, This plugin extends the Gravity Forms and its addons with Persian language
Version: 2.1.5
Requires at least: 4.0
Author: hannansoft
Author URI: http://www.gravityforms.ir/
Text Domain: GF_FA
Domain Path: /languages/
*/

if (!defined('ABSPATH')) exit;

if (!defined('GF_PARSI_VERSION'))
    define('GF_PARSI_VERSION', '2.1.5');

if (!defined('GF_PARSI_URL'))
    define('GF_PARSI_URL', plugins_url('', __FILE__) . '/');

if (!defined('GF_PARSI_DIR'))
    define('GF_PARSI_DIR', plugin_dir_path(__FILE__));

add_action('plugins_loaded', 'persian_gravity_load_textdomain');

function persian_gravity_load_textdomain()
{
    load_plugin_textdomain('GF_FA', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

include GF_PARSI_DIR . '/persian-gravity.php';