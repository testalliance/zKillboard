<?php

class Tables
{
    /**
     * Ensures the table exists for a particular year and week
     *
     * @static
     * @param $year
     * @param $week
     * @return void
     */
    public static function ensureTableExist($year, $week)
    {
return;
        global $table_array, $dbPrefix;
        if (!isset($table_array)) $table_array = array();

        if (isset($table_array["$year $week"])) return;
        $currentYear = date("Y");
        $currentWeek = date("W");
        $error = false;
        if ($year < 2003 or $year > $currentYear) $error = true;
        if ($year == $currentYear and $week > $currentWeek) $error = true;
        if ($week < 1 || $week > 53) $error = true;
        if (strlen("$week") < 2) $error = true;
        if ($error) {
            throw new Exception("Invalid year/week: $year/$week (week should be in two digit format or <= $currentYear/$currentWeek)");
        }

        Db::execute("create table if not exists {$dbPrefix}kills_{$year}_{$week} like {$dbPrefix}base_kills");
        Db::execute("create table if not exists {$dbPrefix}items_{$year}_{$week} like {$dbPrefix}base_items");
        Db::execute("create table if not exists {$dbPrefix}participants_{$year}_{$week} like {$dbPrefix}base_participants");

        $table_array["$year $week"] = true;
    }
}
