<?php
if (!defined('ABSPATH')) die('No direct access allowed');

/**
 * @package Food Truck Locator
 * @version 1.0.1
 */
/*
Plugin Name: Food Truck Locator 
Plugin URI : https://www.adreliaweb.fr
Description: Add a map of your food truck locations by date and time to keep your customers informed!
Author: Romain Rebotier
Version: 1.0.1
Author URI: https://www.adreliaweb.fr
Text Domain: food-truck-locator
Domain Path: /lang/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

include(plugin_dir_path(__FILE__) . 'includes/FoodTruckLocator.php');
include(plugin_dir_path(__FILE__) . 'includes/Queries.php');
include(plugin_dir_path(__FILE__) . 'includes/settings-general-section.php');

$plugin = new FoodTruckLocator('1.0.1');
$plugin->init();
