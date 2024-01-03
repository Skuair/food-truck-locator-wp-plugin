<?php

function foodtrucklocator_settingsSectionGeneral($args)
{
    $options = $args['options'];
?>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="foodtrucklocator_settings[marker_color]"><?php _e('Marker color', 'food-truck-locator'); ?></label>
            </th>
            <td><input type="color" name="foodtrucklocator_settings[marker_color]" value="<?php echo $options['marker_color']; ?>"></td>
        </tr>
        <tr>
            <th scope="row">
                <label for="foodtrucklocator_settings[vacation_mode]"><?php _e('Vacation mode', 'food-truck-locator'); ?></label>
            </th>
            <td>
                <input type="checkbox" name="foodtrucklocator_settings[vacation_mode]" value="1" <?php echo $options['vacation_mode'] && $options['vacation_mode'] == '1' ? 'checked' : ''; ?>>
                <br /><small><?php _e('The vacation mode darken the map and add a message you can customize just right there', 'food-truck-locator'); ?></small>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="foodtrucklocator_settings[vacation_mode_message]"><?php _e('Vacation mode message', 'food-truck-locator'); ?></label>
            </th>
            <td>
                <textarea name="foodtrucklocator_settings[vacation_mode_message]" style="width: 100%;"><?php echo $options['vacation_mode_message']; ?></textarea>
            </td>
        </tr>
    </table>
<?php
}
