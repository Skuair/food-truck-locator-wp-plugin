<?php

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class FoodTruckLocator_Locations_List extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Location', 'food-truck-locator'),
            'plural' => __('Locations', 'food-truck-locator'),
            'ajax' => false
        ]);
    }

    /**
     * Listof locations
     *
     * @param int $rows
     * @param int $pageNumber
     *
     * @return mixed
     */
    public static function getLocations($rows = 5, $pageNumber = 1)
    {
        return FoodTruckLocator_Queries::GetLocations($pageNumber, $rows, $_REQUEST['orderby'], $_REQUEST['order']);
    }

    /**
     * Total number of locations
     *
     * @return null|string
     */
    public static function recordCount()
    {
        global $wpdb;
        $sql = "SELECT COUNT(*) FROM {$wpdb->prefix}" . FoodTruckLocator_Queries::LOCATIONS_TABLE;
        return $wpdb->get_var($sql);
    }

    /**
     * Delete a location and its timetables
     *
     * @param int $id Location ID
     */
    public static function deleteLocation($id)
    {
        global $wpdb;
        $wpdb->delete(
            "{$wpdb->prefix}" . FoodTruckLocator_Queries::TIMETABLES_TABLE,
            ['location_id' => $id],
            ['%d']
        );
        $wpdb->delete(
            "{$wpdb->prefix}" . FoodTruckLocator_Queries::LOCATIONS_TABLE,
            ['id' => $id],
            ['%d']
        );
    }

    public function no_items()
    {
        _e('No location.', 'food-truck-locator');
    }

    /**
     * Name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    public function column_name($item)
    {
        $title = sprintf('<a href="?page=%s&locationId=%d"><strong>%s</strong></a>', esc_attr('foodtrucklocator-edit'), absint($item['id']), $item['name']);
        $actions = [
            'edit' => sprintf('<a href="?page=%s&locationId=%d">%s</a>', esc_attr('foodtrucklocator-edit'), absint($item['id']), __('Edit', 'food-truck-locator')),
            'delete' => sprintf('<a href="?page=%s&action=%s&location=%s&_wpnonce=%s">%s</a>', esc_attr($_REQUEST['page']), 'delete', absint($item['id']), wp_create_nonce('foodtrucklocator_delete_location'), __('Delete', 'food-truck-locator'))
        ];
        return $title . $this->row_actions($actions);
    }

    /**
     * Checkbox rendering
     *
     * @param array $item
     *
     * @return string
     */
    public function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="bulk-delete[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * Columns
     *
     * @return array
     */
    public function get_columns()
    {
        $columns = [
            'cb' => '<input type="checkbox" />',
            'name' => __('Location', 'food-truck-locator'),
            'visible' => __('Visible', 'food-truck-locator'),
            'created_at' => __('Created on', 'food-truck-locator'),
            'updated_at' => __('Updated on', 'food-truck-locator')
        ];
        return $columns;
    }

    /**
     * Rendering of column value
     *
     * @param array $item
     * @param string $columnName
     *
     * @return mixed
     */
    public function column_default($item, $columnName)
    {
        if ($columnName === 'visible') {
            return $item[$columnName] == 1 ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no"></span>';
        }
        return $item[$columnName];
    }

    /**
     * Column sort
     *
     * @return array
     */
    public function get_sortable_columns()
    {
        $sortable_columns = array(
            'name' => array('name', false),
            'created_at' => array('created_at', false),
            'updated_at' => array('updated_at', false)
        );
        return $sortable_columns;
    }

    /**
     * Bulk actions
     *
     * @return array
     */
    public function get_bulk_actions()
    {
        $actions = [
            'bulk-delete' => __('Delete', 'food-truck-locator')
        ];
        return $actions;
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {
        $this->_column_headers = [
            $this->get_columns(),
            [], // hidden columns
            $this->get_sortable_columns(),
            $this->get_primary_column_name(),
        ];
        $this->process_bulk_action();
        $perPage = $this->get_items_per_page('locations_per_page', 10);
        $currentPage = $this->get_pagenum();
        $totalItems = self::record_count();
        $this->set_pagination_args([
            'total_items' => $totalItems,
            'per_page' => $perPage
        ]);
        $this->items = self::getLocations($perPage, $currentPage);
    }

    public function process_bulk_action()
    {
        //Detect when a bulk action is being triggered...
        if ('delete' === $this->current_action()) {
            // In our file that handles the request, verify the nonce.
            $nonce = esc_attr($_REQUEST['_wpnonce']);
            if (!wp_verify_nonce($nonce, 'foodtrucklocator_delete_location')) {
                die('Unauthorized');
            } else {
                self::deleteLocation(absint($_GET['location']));
            }
        }

        // If the delete bulk action is triggered
        if ((isset($_POST['action']) && $_POST['action'] == 'bulk-delete')
            || (isset($_POST['action2']) && $_POST['action2'] == 'bulk-delete')
        ) {
            $delete_ids = esc_sql($_POST['bulk-delete']);
            // loop over the array of record IDs and delete them
            foreach ($delete_ids as $id) {
                self::deleteLocation($id);
            }
        }
    }
}
