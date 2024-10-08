<?php

class FoodTruckLocator
{
    protected $views = [
        'list' => 'views/list',
        'edit' => 'views/edit',
        'settings' => 'views/settings',
        'rendering' => 'views/rendering',
    ];
    private $version;
    private $current_page = '';

    public function __construct($version = '')
    {
        $this->version = $version;
    }

    public function init()
    {
        add_action('admin_menu', [$this, 'menuEntry']);
        register_activation_hook(__FILE__, [$this, 'install']);
        add_action('plugins_loaded', [$this, 'updateDbCheck']);
        add_action('admin_init', [$this, 'settingsInit']);
        add_action('wp_enqueue_scripts', [$this, 'addScripts']);
        add_action('admin_enqueue_scripts', [$this, 'addAdminScripts']);
        add_shortcode('foodtrucklocator', [$this, 'createShortcode']);
        add_action('wp_ajax_save_location', [$this, 'ajaxSaveLocation']);
        load_plugin_textdomain('food-truck-locator', false, dirname(plugin_basename(__FILE__)) . '/../lang/');
    }

    private function _getCurrentView()
    {
        $currentPage = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'list';
        if (strpos($currentPage, '-') === false) {
            return 'list';
        }
        return str_replace('foodtrucklocator-', '', $currentPage);
    }

    private function _getViewPath($filePath)
    {
        $myPluginDir = plugin_dir_path(__FILE__) . '../';
        if (is_dir($myPluginDir)) {
            $pathToFile = $myPluginDir . $filePath . '.php';
            return $pathToFile;
        }
    }

    public function addScripts()
    {
        wp_enqueue_script('leaflet-foodtrucklocator', plugins_url('food-truck-locator') . '/js/leaflet_1.9.4.js', []);
        wp_enqueue_style('leaflet-foodtrucklocator', plugins_url('food-truck-locator') . '/css/leaflet_1.9.4.css', []);
        wp_enqueue_script('foodtrucklocator', plugins_url('food-truck-locator') . '/js/foodtrucklocator.js', ['leaflet-foodtrucklocator'], $this->version, true);
        wp_enqueue_style('foodtrucklocator', plugins_url('food-truck-locator') . '/css/foodtrucklocator.css', [], $this->version);
    }

    public function addAdminScripts()
    {
        $current_screen = get_current_screen();
        if (strpos($current_screen->base, 'foodtrucklocator') === false) {
            return;
        } else {
            wp_enqueue_script('leaflet-foodtrucklocator', plugins_url('food-truck-locator') . '/js/leaflet_1.9.4.js', []);
            wp_enqueue_style('leaflet-foodtrucklocator', plugins_url('food-truck-locator') . '/css/leaflet_1.9.4.css', []);
        }
    }

    public function menuEntry()
    {
        add_menu_page(
            __('Food Truck Locator', 'food-truck-locator'),
            __('Food Truck Locator', 'food-truck-locator'),
            'manage_options',
            'foodtrucklocator-list',
            '',
            'data:image/svg+xml;base64,PCFET0NUWVBFIHN2ZyBQVUJMSUMgIi0vL1czQy8vRFREIFNWRyAxLjEvL0VOIiAiaHR0cDovL3d3dy53My5vcmcvR3JhcGhpY3MvU1ZHLzEuMS9EVEQvc3ZnMTEuZHRkIj4KDTwhLS0gVXBsb2FkZWQgdG86IFNWRyBSZXBvLCB3d3cuc3ZncmVwby5jb20sIFRyYW5zZm9ybWVkIGJ5OiBTVkcgUmVwbyBNaXhlciBUb29scyAtLT4KPHN2ZyBmaWxsPSIjYTM0MTAwIiB3aWR0aD0iODAwcHgiIGhlaWdodD0iODAwcHgiIHZpZXdCb3g9IjAgMCAzMCAzMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiB4bWxuczp4bGluaz0iaHR0cDovL3d3dy53My5vcmcvMTk5OS94bGluayIgc3Ryb2tlPSIjYTM0MTAwIiBzdHJva2Utd2lkdGg9IjAuMDAwMzAwMDAwMDAwMDAwMDAwMDMiPgoNPGcgaWQ9IlNWR1JlcG9fYmdDYXJyaWVyIiBzdHJva2Utd2lkdGg9IjAiLz4KDTxnIGlkPSJTVkdSZXBvX3RyYWNlckNhcnJpZXIiIHN0cm9rZS1saW5lY2FwPSJyb3VuZCIgc3Ryb2tlLWxpbmVqb2luPSJyb3VuZCIgc3Ryb2tlPSIjQ0NDQ0NDIiBzdHJva2Utd2lkdGg9IjAuMDYiLz4KDTxnIGlkPSJTVkdSZXBvX2ljb25DYXJyaWVyIj4KDTxwYXRoIGQ9Ik0zIDdDMS44OTUgNyAxIDcuODk1IDEgOUwxIDIxIEEgMS4wMDAxIDEuMDAwMSAwIDEgMCAxIDIzTDMgMjNMMy4wNTA3ODEyIDIzQzMuMjk4MTc2MyAyNC42ODU0MTEgNC43NTAwNjYzIDI2IDYuNSAyNkM4LjI0OTkzMzUgMjYgOS43MDE4MjM1IDI0LjY4NTQxMSA5Ljk0OTIxODggMjNMMjEuMDUwNzgxIDIzQzIxLjI5ODE3NyAyNC42ODU0MTEgMjIuNzUwMDY3IDI2IDI0LjUgMjZDMjYuMjQ5OTM2IDI2IDI3LjcwMTgzNyAyNC42ODU0MTggMjcuOTQ5MjE5IDIzTDI5IDIzIEEgMS4wMDAxIDEuMDAwMSAwIDEgMCAyOSAyMUwyOSAxNkMyOSAxNC44OTUgMjguMTA1IDE0IDI3IDE0TDIyIDE0QzIxLjQ0OCAxNCAyMSAxMy41NTIgMjEgMTNMMjEgMTBDMjEgOS40NDggMjEuNDQ4IDkgMjIgOUwyNC44NTc0MjIgOUwyNC41MTk1MzEgOC4yMTI4OTA2QzI0LjIwNDUzMSA3LjQ3Nzg5MDYgMjMuNDgxNjQxIDcgMjIuNjgxNjQxIDdMMyA3IHogTSA1IDkuNSBBIDEuNSAxLjUgMCAwIDAgNi41IDExIEEgMS41IDEuNSAwIDAgMCA4IDkuNSBBIDEuNSAxLjUgMCAwIDAgOS41IDExIEEgMS41IDEuNSAwIDAgMCAxMSA5LjUgQSAxLjUgMS41IDAgMCAwIDEyLjUgMTEgQSAxLjUgMS41IDAgMCAwIDE0IDkuNSBBIDEuNSAxLjUgMCAwIDAgMTUuNSAxMSBBIDEuNSAxLjUgMCAwIDAgMTcgOS41IEEgMS41IDEuNSAwIDAgMCAxOC41IDExIEEgMS41IDEuNSAwIDAgMCAxOSAxMC45MTIxMDlMMTkgMTVDMTkgMTUuNTUyIDE4LjU1MiAxNiAxOCAxNkw0IDE2QzMuNDQ4IDE2IDMgMTUuNTUyIDMgMTVMMyAxMC45MTIxMDkgQSAxLjUgMS41IDAgMCAwIDMuNSAxMSBBIDEuNSAxLjUgMCAwIDAgNSA5LjUgeiBNIDYuNSAyMUM3LjM0MDI3MTggMjEgOCAyMS42NTk3MjggOCAyMi41QzggMjMuMzQwMjcyIDcuMzQwMjcxOCAyNCA2LjUgMjRDNS42NTk3MjgyIDI0IDUgMjMuMzQwMjcyIDUgMjIuNUM1IDIxLjY1OTcyOCA1LjY1OTcyODIgMjEgNi41IDIxIHogTSAyNC41IDIxQzI1LjM0MDI3MiAyMSAyNiAyMS42NTk3MjggMjYgMjIuNUMyNiAyMy4zNDAyNzIgMjUuMzQwMjcyIDI0IDI0LjUgMjRDMjMuNjU5NzI4IDI0IDIzIDIzLjM0MDI3MiAyMyAyMi41QzIzIDIxLjY1OTcyOCAyMy42NTk3MjggMjEgMjQuNSAyMSB6Ii8+Cg08L2c+Cg08L3N2Zz4=',
            28
        );
        add_submenu_page('foodtrucklocator-list', __('All locations', 'food-truck-locator'), __('All locations', 'food-truck-locator'), 'manage_options', 'foodtrucklocator-list', [$this, 'loadView'], 0);
        add_submenu_page('foodtrucklocator-list', __('Add a location', 'food-truck-locator'), __('Add a location', 'food-truck-locator'), 'manage_options', 'foodtrucklocator-edit', [$this, 'loadView'], 1);
        add_submenu_page('foodtrucklocator-list', __('Settings', 'food-truck-locator'), __('Settings', 'food-truck-locator'), 'manage_options', 'foodtrucklocator-settings', [$this, 'loadView'], 2);
    }

    public function loadView()
    {
        $this->current_page = $this->_getCurrentView();
        $currentView = isset($this->views[$this->current_page]) ? $this->views[$this->current_page] : $this->views['list'];
        echo '<div class="foodtrucklocator ' . esc_attr($this->current_page) . '">';
        echo '<div class="container">';
        echo '<div class="inner">';
        $this->includeView($this->_getViewPath($currentView));
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    /**
     * Print internal content (not user input) without escaping.
     */
    private function includeView($filePath)
    {
        $output = '';
        if (file_exists($filePath)) {
            ob_start();
            include $filePath;
            $output = ob_get_contents();
            ob_end_clean();
        }
        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    public function install()
    {
        FoodTruckLocator_Queries::CreateTables();
        add_option('foodtrucklocator_db_version', $this->version);
    }

    public function uninstall()
    {
        delete_option('foodtrucklocator_db_version');
        delete_option('foodtrucklocator_settings');
        FoodTruckLocator_Queries::DropTables();
    }

    public function upgrade()
    {
        FoodTruckLocator_Queries::CreateTables();
        update_option('foodtrucklocator_db_version', $this->version);
    }

    public function updateDbCheck()
    {
        if (get_site_option('foodtrucklocator_db_version') != $this->version) {
            $this->upgrade();
        }
    }

    public function settingsInit()
    {
        register_setting('foodtrucklocator', 'foodtrucklocator_settings');
        $options = get_option('foodtrucklocator_settings');
        add_settings_section(
            'foodtrucklocator_general_section',
            __('General settings', 'food-truck-locator'),
            'foodtrucklocator_settingsSectionGeneral',
            'foodtrucklocator',
            ['options' => $options]
        );
    }

    public function ajaxSaveLocation()
    {
        $locationId = sanitize_key($_POST['location']['id']);
        check_ajax_referer('edit-location_' . ($locationId ? absint($locationId) : 0));
        if (!empty($_POST['location'])) {
            $locationName = sanitize_text_field($_POST['location']['name']);
            $locationLatitude = filter_var($_POST['location']['latitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $locationLongitude = filter_var($_POST['location']['longitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            $result = 0;
            $error = [];
            if (!$locationName) {
                $error[] = __('Name is required', 'food-truck-locator');
            }
            if (!$locationLatitude && !$locationLongitude) {
                $error[] = __('Coordinates are required', 'food-truck-locator');
            }
            if (empty($error)) {
                if ($locationId) {
                    $locationId = absint($locationId);
                    $result = FoodTruckLocator_Queries::UpdateLocation($_POST['location']); // POST sanitized inside the method
                    FoodTruckLocator_Queries::removeTimeTables($locationId);
                    $result += $this->saveTimeTables($locationId, $_POST['timeTables']); // POST sanitized inside the method
                } else {
                    $result = FoodTruckLocator_Queries::CreateLocation($_POST['location']); // POST sanitized inside the method
                    $result += $this->saveTimeTables($result, $_POST['timeTables']); // POST sanitized inside the method
                }
                if ($result > 0) {
                    wp_send_json_success(['message' => $locationId ? __('Location updated.', 'food-truck-locator') : __('Location created.', 'food-truck-locator')]);
                } else {
                    global $wpdb;
                    wp_send_json_error([
                        'message' => $locationId ? __('Error while updating the location.', 'food-truck-locator') : __('Error while creating the location.', 'food-truck-locator'),
                        'details' => $wpdb->last_error,
                    ]);
                }
            } else {
                wp_send_json_error(['message' => join(', ', $error)]);
            }
        } else {
            wp_send_json_error(['message' => __('No location sent', 'food-truck-locator')]);
        }
    }

    private function saveTimeTables($locationId, $timeTables)
    {
        $result = 0;
        foreach ($timeTables as $timeTable) {
            $result += FoodTruckLocator_Queries::AddTimeTableToLocation($locationId, $timeTable);
        }
        return $result;
    }

    public function createShortcode($atts)
    {
        $default = ['height' => ''];
        $a = shortcode_atts($default, $atts);
        extract(['shortcodeOptions' => $a]);
        ob_start();
        include($this->_getViewPath($this->views['rendering']));
        return ob_get_clean();
    }
}
