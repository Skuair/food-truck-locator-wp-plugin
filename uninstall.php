<?php
if (!defined('WP_UNINSTALL_PLUGIN'))
    exit();

include(plugin_dir_path(__FILE__) . 'includes/FoodTruckLocator.php');
include(plugin_dir_path(__FILE__) . 'includes/Queries.php');

$plugin = new FoodTruckLocator();
$plugin->uninstall();
