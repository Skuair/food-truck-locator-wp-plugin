<?php
if (!defined('ABSPATH')) die('No direct access allowed');

$location = new stdClass();
$timeTables = [];
$settings = get_option('foodtrucklocator_settings');
$markerColor = '#000';
if ($settings) {
    if ($settings['marker_color']) {
        $markerColor = $settings['marker_color'];
    }
}
if ($_GET['locationId']) {
    $result = FoodTruckLocator_Queries::GetLocationById($_GET['locationId']);
    if ($result) {
        $location = $result[0];
        $resultTimeTables = FoodTruckLocator_Queries::GetTimeTablesByLocationId($_GET['locationId']);
        if ($resultTimeTables) {
            $timeTables = $resultTimeTables;
        }
    } else {
?>
        <div class="notice notice-error">
            <p>
                <?php _e('No location found with this ID.', 'food-truck-locator'); ?>
            </p>
        </div>
<?php
        die();
    }
}
?>

<div class="wrap">
    <h1>
        <?php $_GET['locationId'] ? _e('Edit a location', 'food-truck-locator') : _e('Add a location', 'food-truck-locator'); ?>
    </h1>
    <form id="locationForm" name="locationForm" method="POST">
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="name"><?php _e('Location name', 'food-truck-locator'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="name" name="name" value="<?php echo wp_unslash($location->name); ?>">
                        <input type="hidden" name="id" id="id" value="<?php echo $location->id; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="description"><?php _e('Location description or additionnal information', 'food-truck-locator'); ?></label>
                    </th>
                    <td>
                        <textarea id="description" name="description" style="width: 100%;"><?php echo wp_unslash($location->description); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Position', 'food-truck-locator'); ?></label>
                    </th>
                    <td>
                        <div id="map" style="height: 50vh;"></div>
                        <input type="hidden" name="latitude" id="latitude" value="<?php echo $location->latitude; ?>">
                        <input type="hidden" name="longitude" id="longitude" value="<?php echo $location->longitude; ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Time table', 'food-truck-locator'); ?></label>
                    </th>
                    <td id="timetables">
                        <button type="button" class="button-primary" style="margin-bottom: 1rem;" onclick="javascript: addTimeTable();"><?php _e('Add', 'food-truck-locator'); ?></button>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="visible"><?php _e('Visible', 'food-truck-locator'); ?></label>
                    </th>
                    <td id="timetables">
                        <input type="checkbox" name="visible" id="visible" value="1" <?php echo $location->visible || !$location->id ? ' checked="checked"' : ''; ?>; </td>
                </tr>
            </tbody>
        </table>
        <?php
        wp_nonce_field('edit-location_' . ($_GET['locationId'] ? $_GET['locationId'] : 0));
        submit_button(__('Save changes', 'food-truck-locator'), 'primary', 'save');
        ?>
    </form>
    <div id="feedback">
        <p></p>
    </div>
</div>

<script>
    let map;
    const latField = document.querySelector('#latitude');
    const lngField = document.querySelector('#longitude');
    const weekDays = [{
            value: 1,
            label: '<?php _e('Monday', 'food-truck-locator'); ?>'
        },
        {
            value: 2,
            label: '<?php _e('Tuesday', 'food-truck-locator'); ?>'
        },
        {
            value: 3,
            label: '<?php _e('Wednesday', 'food-truck-locator'); ?>'
        },
        {
            value: 4,
            label: '<?php _e('Thursday', 'food-truck-locator'); ?>'
        },
        {
            value: 5,
            label: '<?php _e('Friday', 'food-truck-locator'); ?>'
        },
        {
            value: 6,
            label: '<?php _e('Saturday', 'food-truck-locator'); ?>'
        },
        {
            value: 0,
            label: '<?php _e('Sunday', 'food-truck-locator'); ?>'
        },
    ];

    window.addEventListener('DOMContentLoaded', () => {
        map = L.map('map').setView([44.9763, 5.1080], 3);
        // Get the lat/lng set for the current entry or ask to put the current coords
        if (latField.value || lngField.value) {
            setMapView([latField.value, lngField.value], 18);
        } else {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition((position) => setMapView([position.coords.latitude, position.coords.longitude], 14));
            } else {
                setMapView([44.9763, 5.1080], 3); //Default french touch view 🥖
            }
        }
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        jQuery('#locationForm').submit((e) => {
            const feedback = jQuery('#feedback');
            const location = jQuery(e.target).serializeArray().reduce((acc, {
                name,
                value
            }) => ({
                ...acc,
                [name]: value
            }), {});
            const timeTable = jQuery('#timetables .timetable').toArray().map((e) => ({
                weekday: jQuery(e).find('.day').val(),
                start_time: jQuery(e).find('.fromTime').val(),
                end_time: jQuery(e).find('.toTime').val(),
                visible: jQuery(e).find('.visible').is(":checked") ? '1' : '0',
            }));
            jQuery.ajax({
                    data: {
                        action: 'save_location',
                        _ajax_nonce: jQuery('#_wpnonce').val(),
                        location: location,
                        timeTables: timeTable
                    },
                    type: 'post',
                    url: ajaxurl,
                })
                .done(res => {
                    jQuery(feedback.children()[0]).html(res.data.message);
                    if (res.success) {
                        feedback.removeClass().addClass('notice notice-success is-dismissible');
                        // Redirect to list page on creation only
                        if (!location.id) {
                            feedback.append('<p><?php _e('Redirecting to locations list...', 'food-truck-locator'); ?></p>');
                            window.location.href = '<?php echo admin_url('admin.php?page=foodtrucklocator-list'); ?>';
                        }
                    } else {
                        feedback.removeClass().addClass('notice notice-error');
                        if (res.data.details) {
                            feedback.append('<p><em>' + res.data.details + '</em></p>');
                        }
                    }
                })
                .fail(error => {
                    feedback.removeClass().addClass('notice notice-error');
                    jQuery(feedback.children()[0]).html(error);
                })
                .always(() => jQuery('html, body').animate({
                    scrollTop: feedback.offset().top
                }, 2000));
            e.preventDefault();
        });

        fillExistingTimeTables(<?php echo json_encode($timeTables); ?>);
    });

    function setMapView(coords, zoom) {
        map.setView(coords, zoom);
        L.marker(coords, {
                draggable: true,
                autoPan: true,
                icon: L.divIcon({
                    className: "custom-marker",
                    iconAnchor: [15, 30],
                    popupAnchor: [0, -30],
                    html: `<div style="background-color: <?php echo $markerColor; ?>"></div>`,
                })
            })
            .bindPopup(`<?php _e('Hey! Drag me to one of your best spot!', 'food-truck-locator'); ?> 🚐`)
            .on('moveend', (e) => setLocation(e.target.getLatLng()))
            .addTo(map)
            .openPopup();
    }

    function setLocation(coords) {
        latField.value = coords.lat;
        lngField.value = coords.lng;
    }

    function addTimeTable(timeTable = {}) {
        const div = jQuery('<div>')
            .addClass('timetable')
            .css('display', 'flex')
            .css('align-items', 'center')
            .css('gap', '1rem')
            .css('margin-bottom', '1rem')
            .appendTo('#timetables');
        const selectDay = jQuery('<select>').addClass('day').appendTo(div);
        const fromTime = jQuery('<input>').attr('type', 'time').addClass('fromTime');
        const toTime = jQuery('<input>').attr('type', 'time').addClass('toTime');
        const visible = jQuery('<input>').attr('type', 'checkbox').addClass('visible').val(1);
        jQuery(weekDays).each((i, e) => selectDay.append(jQuery('<option>').attr('value', e.value).text(e.label)));
        selectDay.val(timeTable.weekday);
        jQuery('<span>').html('<?php _e('From', 'food-truck-locator'); ?> ')
            .append(fromTime)
            .appendTo(div);
        fromTime.val(timeTable.start_time);
        jQuery('<span>').html('<?php _e('To', 'food-truck-locator'); ?> ')
            .append(toTime)
            .appendTo(div);
        toTime.val(timeTable.end_time);
        jQuery('<span>').html('<?php _e('Visible', 'food-truck-locator'); ?> ')
            .append(visible)
            .appendTo(div);
        visible.prop('checked', timeTable.visible === '1' || !timeTable.id);
        jQuery('<button>')
            .attr('type', 'button')
            .addClass('button-secondary')
            .css('display', 'flex')
            .css('align-items', 'center')
            .css('color', 'red')
            .css('border-color', 'red')
            .html('<span class="dashicons dashicons-trash"></span>')
            .on('click', () => removeTimeTable(div))
            .appendTo(div);
    }

    function removeTimeTable(element) {
        element.remove();
    }

    function fillExistingTimeTables(timeTables) {
        for (const timeTable of timeTables) {
            addTimeTable(timeTable);
        }
    }
</script>

<style>
    #map .leaflet-marker-icon.custom-marker>div {
        width: 30px;
        height: 30px;
        display: block;
        border-radius: 1.5rem 1.5rem 0;
        border: 1px solid #fff;
        transform: rotate(45deg);
    }
</style>