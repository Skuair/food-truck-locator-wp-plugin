<?php

function foodtrucklocator_settingsSectionGeneral($args)
{
    $options = $args['options'];
?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="foodtrucklocator_settings[marker_color]"><?php esc_html_e('Marker color', 'food-truck-locator'); ?></label>
            </th>
            <td><input type="color" name="foodtrucklocator_settings[marker_color]" value="<?php echo esc_attr($options['marker_color']); ?>"></td>
        </tr>
        <tr>
            <th scope="row">
                <label for="foodtrucklocator_settings[vacation_mode]"><?php esc_html_e('Vacation mode', 'food-truck-locator'); ?></label>
            </th>
            <td>
                <input type="checkbox" name="foodtrucklocator_settings[vacation_mode]" value="1" <?php echo $options['vacation_mode'] && $options['vacation_mode'] == '1' ? 'checked' : ''; ?>>
                <br /><small><?php esc_html_e('The vacation mode darken the map and add a message you can customize just right there', 'food-truck-locator'); ?></small>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="foodtrucklocator_settings[vacation_mode_message]"><?php esc_html_e('Vacation mode message', 'food-truck-locator'); ?></label>
            </th>
            <td>
                <textarea name="foodtrucklocator_settings[vacation_mode_message]" style="width: 100%;"><?php echo esc_textarea($options['vacation_mode_message']); ?></textarea>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="foodtrucklocator_settings[show_days]"><?php esc_html_e('Show days list on map', 'food-truck-locator'); ?></label>
            </th>
            <td>
                <input type="checkbox" name="foodtrucklocator_settings[show_days]" value="1" <?php echo $options['show_days'] && $options['show_days'] == '1' ? 'checked' : ''; ?>>
                <br /><small><?php esc_html_e('This will add a menu on the map to show the days when you have locations set. A click on a day will center the map on the related locations.', 'food-truck-locator'); ?></small>
            </td>
        </tr>
    </table>
<?php
}
