<?php
if (!defined('ABSPATH')) die('No direct access allowed');

$locations = [];
$page = 1;
$locations = FoodTruckLocator_Queries::GetAllPublicLocationsWithTimeTables();
$settings = get_option('foodtrucklocator_settings');
$markerColor = '#000';
$vacationMode = false;
$vacationMessage = __('Vacation mode', 'food-truck-locator');
$showDays = false;
if ($settings) {
    if (isset($settings['marker_color'])) {
        $markerColor = $settings['marker_color'];
    }
    if (isset($settings['vacation_mode'])) {
        $vacationMode = true;
    }
    if (isset($settings['vacation_mode_message'])) {
        $vacationMessage = $settings['vacation_mode_message'];
    }
    if (isset($settings['show_days'])) {
        $showDays = true;
    }
}

$heightDiv = $shortcodeOptions['height'] ? $shortcodeOptions['height'] : '50vh';

?>
<div id="foodtrucklocator_container">
    <?php
    if ($vacationMode) {
    ?>
        <div id="foodtrucklocator_vacation_banner">
            <p id="icon">🏖️</p>
            <p id="message"><?php echo esc_html($vacationMessage); ?></p>
        </div>
    <?php
    }
    if ($showDays) {
    ?>
        <div id="foodtrucklocator_show_days_list_container">
            <div id="foodtrucklocator_show_days_list_opener" onclick="javascript: foodTruckLocator.toggleDayList();">
                <span id="foodtrucklocator_show_days_list_opener_open">📆</span>
                <span id="foodtrucklocator_show_days_list_opener_close">❌</span>
            </div>
            <div id="foodtrucklocator_show_days_list"></div>
        </div>
    <?php
    }
    ?>
    <div id="foodtrucklocator_map" class="<?php echo $vacationMode ? 'vacation' : ''; ?>" style="height: <?php echo esc_attr($heightDiv); ?>;"></div>
</div>


<script>
    let foodTruckLocator;
    const strings = {
        now: '<?php esc_html_e('Now', 'food-truck-locator'); ?>',
        next: '<?php esc_html_e('Next', 'food-truck-locator'); ?>',
        regularSlots: '<?php esc_html_e('Regular slots', 'food-truck-locator'); ?>',
        oneoffDates: '<?php esc_html_e('Oneoff dates', 'food-truck-locator'); ?>',
        today: '<?php esc_html_e('Today', 'food-truck-locator'); ?>',
        weekDays: [],
    };

    // Try to get localized days of week, otherwise get them from backend
    try {
        var baseDate = new Date(Date.UTC(2023, 11, 31)); // begin with a known sunday (2023-12-31)
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
        foodTruckLocator = new FoodTruckLocator(
            locations,
            <?php echo $vacationMode ? 'true' : 'false'; ?>,
            strings,
            '<?php echo esc_attr($markerColor); ?>',
            document.querySelector('#foodtrucklocator_show_days_list_container')
        );
        foodTruckLocator.renderMap();

        // Populate the show days list
        const showDaysList = document.querySelector('#foodtrucklocator_show_days_list');
        if (showDaysList) {
            foodTruckLocator.generateDaysList(showDaysList);
        }
    });
</script>