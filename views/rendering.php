<?php
if (!defined('ABSPATH')) die('No direct access allowed');

$locations = [];
$page = 1;
$locations = FoodTruckLocator_Queries::GetAllPublicLocationsWithTimeTables();
$settings = get_option('foodtrucklocator_settings');
$markerColor = '#000';
$vacationMode = false;
$vacationMessage = __('Vacation mode', 'food-truck-locator');
if ($settings) {
    if ($settings['marker_color']) {
        $markerColor = $settings['marker_color'];
    }
    if ($settings['vacation_mode']) {
        $vacationMode = true;
    }
    if ($settings['vacation_mode_message']) {
        $vacationMessage = $settings['vacation_mode_message'];
    }
}

$heightDiv = $shortcodeOptions['height'] ? $shortcodeOptions['height'] : '50vh';
if ($vacationMode) {
?>
    <div id="foodtrucklocator_vacation_banner">
        <p id="icon">🏖️</p>
        <p id="message"><?php echo esc_html($vacationMessage); ?></p>
    </div>
<?php
}
?>
<div id="foodtrucklocator_map" class="<?php echo $vacationMode ? 'vacation' : ''; ?>" style="height: <?php echo esc_attr($heightDiv); ?>;">
</div>

<script>
    const strings = {
        now: '<?php esc_html_e('Now', 'food-truck-locator'); ?>',
        next: '<?php esc_html_e('Next', 'food-truck-locator'); ?>',
        weekDays: [],
    };

    // Try to get localized days of week, otherwise get them from backend
    try {
        var baseDate = new Date(Date.UTC(2023, 11, 31)); // begin with a known sunday 2023-12-31)
        for (i = 0; i < 7; i++) {
            strings.weekDays.push(baseDate.toLocaleDateString(window.navigator.language, {
                weekday: 'long'
            }));
            baseDate.setDate(baseDate.getDate() + 1);
        }
    } catch (error) {
        strings.weekDays = [
            '<?php esc_html_e('Sunday', 'food-truck-locator'); ?>',
            '<?php esc_html_e('Monday', 'food-truck-locator'); ?>',
            '<?php esc_html_e('Tuesday', 'food-truck-locator'); ?>',
            '<?php esc_html_e('Wednesday', 'food-truck-locator'); ?>',
            '<?php esc_html_e('Thursday', 'food-truck-locator'); ?>',
            '<?php esc_html_e('Friday', 'food-truck-locator'); ?>',
            '<?php esc_html_e('Friday', 'food-truck-locator'); ?>',
            '<?php esc_html_e('Saturday', 'food-truck-locator'); ?>',
        ];
    }

    let locations = <?php echo wp_json_encode($locations); ?>;
    locations.sort((a, b) => parseInt(a.weekday) - parseInt(b.weekday)); // Sort timetables by weekday for better visualization

    window.addEventListener('DOMContentLoaded', () => {
        const foodTruckLocator = new FoodTruckLocator(
            locations,
            <?php echo $vacationMode ? 'true' : 'false'; ?>,
            strings,
            '<?php echo esc_attr($markerColor); ?>'
        );
        foodTruckLocator.renderMap();
    });
</script>