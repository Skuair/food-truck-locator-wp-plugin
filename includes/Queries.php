<?php

class FoodTruckLocator_Queries
{
    const LOCATIONS_TABLE = 'foodtrucklocator_locations';
    const TIMETABLES_TABLE = 'foodtrucklocator_timetables';

    public static function CreateTables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tableLocations_name = $wpdb->prefix . self::LOCATIONS_TABLE;
        $sqlLocations = "CREATE TABLE $tableLocations_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name tinytext NOT NULL,
            description text,
            latitude DECIMAL(6, 4) NOT NULL,
            longitude DECIMAL(6, 4) NOT NULL,
            visible tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $tableTimetables_name = $wpdb->prefix . self::TIMETABLES_TABLE;
        $sqlTimeTables = "CREATE TABLE $tableTimetables_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            location_id mediumint(9) NOT NULL,
            weekday tinyint(1) NOT NULL,
            start_time TIME,
            end_time TIME,
            visible tinyint(1) NOT NULL DEFAULT 1,
            created_at datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
            updated_at datetime DEFAULT '1000-01-01 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY location_id (location_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta([$sqlLocations, $sqlTimeTables]);
    }

    public static function DropTables()
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS {$wpdb->prefix}" . self::TIMETABLES_TABLE));
        $wpdb->query($wpdb->prepare("DROP TABLE IF EXISTS {$wpdb->prefix}" . self::LOCATIONS_TABLE));
    }

    public static function GetLocations($pageNumber = 1, $rows = 10, $orderBy = '', $order = '')
    {
        global $wpdb;
        $sql = "SELECT *";
        $sql .= " FROM {$wpdb->prefix}" . self::LOCATIONS_TABLE;
        $sql .= " ORDER BY " . (!empty($orderBy) ? esc_sql($orderBy) : 'name') . (!empty($order) ? ' ' . esc_sql($order) : ' ASC');
        $sql .= " LIMIT $rows";
        $sql .= ' OFFSET ' . ($pageNumber - 1) * $rows;
        $sqlPrepared = $wpdb->prepare($sql, []);
        return $wpdb->get_results($sqlPrepared, 'ARRAY_A');
    }

    public static function GetAllPublicLocationsWithTimeTables()
    {
        global $wpdb;
        $sql = "SELECT l.id, l.name, l.description, l.latitude, l.longitude, l.created_at as 'location_created_at', l.updated_at as 'location_updated_at',";
        $sql .= " t.id as 'timetable_id', t.weekday, t.start_time, t.end_time, t.created_at as 'timetable_created_at', t.updated_at as 'timetable_updated_at'";
        $sql .= " FROM {$wpdb->prefix}" . self::LOCATIONS_TABLE . " l";
        $sql .= " LEFT JOIN {$wpdb->prefix}" . self::TIMETABLES_TABLE . " t";
        $sql .= " ON l.id = t.location_id";
        $sql .= " WHERE l.visible = %d AND t.visible = %d";
        $sqlPrepared = $wpdb->prepare($sql, [1, 1]);
        return $wpdb->get_results($sqlPrepared, 'ARRAY_A');
    }

    public static function GetLocationById($locationId)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}" . self::LOCATIONS_TABLE . " WHERE id = %d", [$locationId]));
    }

    public static function GetTimeTablesByLocationId($locationId)
    {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}" . self::TIMETABLES_TABLE . " WHERE location_id = %d", [$locationId]));
    }

    public static function CreateLocation($location)
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . self::LOCATIONS_TABLE, [
            'name' => sanitize_text_field($location['name']),
            'description' => sanitize_text_field($location['description']),
            'latitude' => filter_var($location['latitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'longitude' => filter_var($location['longitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'visible' => $location['visible'] ? filter_var($location['visible'], FILTER_SANITIZE_NUMBER_INT) : 0,
            'created_at' => date("c"),
            'updated_at' => date("c")
        ], ['%s', '%s', '%s', '%s']);
        return $wpdb->insert_id;
    }

    public static function UpdateLocation($location)
    {
        global $wpdb;
        $update = $wpdb->update($wpdb->prefix . self::LOCATIONS_TABLE, [
            'name' => sanitize_text_field($location['name']),
            'description' => sanitize_text_field($location['description']),
            'latitude' => filter_var($location['latitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'longitude' => filter_var($location['longitude'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'visible' => $location['visible'] ? filter_var($location['visible'], FILTER_SANITIZE_NUMBER_INT) : 0,
            'updated_at' => date("c")
        ], ['id' => $location['id']], ['%s', '%s', '%s'], ['%d']);
        return $update;
    }

    public static function AddTimeTableToLocation($locationId, $timeTable)
    {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . self::TIMETABLES_TABLE, [
            'location_id' => sanitize_key($locationId),
            'weekday' => filter_var($timeTable['weekday'], FILTER_SANITIZE_NUMBER_INT),
            'start_time' => sanitize_text_field($timeTable['start_time']),
            'end_time' => sanitize_text_field($timeTable['end_time']),
            'visible' => $timeTable['visible'] ? filter_var($timeTable['visible'], FILTER_SANITIZE_NUMBER_INT) : 0,
            'created_at' => date("c"),
            'updated_at' => date("c")
        ], ['%d', '%d', '%s', '%s', '%s', '%s']);
        return $wpdb->insert_id;
    }

    public static function removeTimeTables($locationId)
    {
        global $wpdb;
        return $wpdb->delete($wpdb->prefix . self::TIMETABLES_TABLE, ['location_id' => $locationId], ['%d']);
    }
}
