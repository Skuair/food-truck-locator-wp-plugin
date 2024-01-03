<?php
if (!defined('ABSPATH')) die('No direct access allowed');
?>

<div class="wrap">
    <h1><?php _e('Food Truck Locator settings', 'food-truck-locator'); ?></h1>
    <form action='options.php' method='post'>
        <?php
        settings_fields('foodtrucklocator');
        do_settings_sections('foodtrucklocator');
        submit_button();
        ?>
    </form>
</div>