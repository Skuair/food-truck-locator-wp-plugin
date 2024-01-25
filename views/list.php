<?php
if (!defined('ABSPATH')) die('No direct access allowed');

include(plugin_dir_path(__FILE__) . '../includes/LocationsListTable.php');

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Food Truck locations', 'food-truck-locator'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=foodtrucklocator-edit'); ?>" class="page-title-action"><?php esc_html_e('Add', 'food-truck-locator'); ?></a>
    <div style="display: flex; align-items: center; border: 1px solid rgba(34, 113, 177, 1); background-color: rgba(34, 113, 177, 0.1);">
        <span class="dashicons dashicons-lightbulb" style="margin: 1rem;"></span>
        <p>
            <b><?php esc_html_e('How to use it?', 'food-truck-locator'); ?></b><br />
            <?php esc_html_e('Just copy and paste the following shortcode in your page or post:', 'food-truck-locator'); ?> <kbd>[foodtrucklocator]</kbd><br />
            <?php esc_html_e('You can use the "height" option to define the height (css value, default is 50vh) of the map div like', 'food-truck-locator'); ?> <kbd>[foodtrucklocator height="75vh"]</kbd>
        </p>
    </div>
    <form method="post">
        <?php
        $locations = new FoodTruckLocator_Locations_List();
        $locations->prepare_items();
        $locations->display();
        ?>
    </form>
</div>