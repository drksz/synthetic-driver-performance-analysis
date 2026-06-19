<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DeliveryAnalyticsController extends Controller
{
    // give 5 minutes time-to-live for all requests
    private const TTL = 300;

    public function kpis(Request $request) {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $traffic = $request->query('traffic');
        $priority = $request->query('priority');

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "date >= :start_date";
            $params['start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "date <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($traffic) {
            $where[] = "traffic_cond = :traffic";
            $params['traffic'] = $traffic;
        }

        if ($priority) {
            $where[] = "priority = :priority";
            $params['priority'] = $priority;
        }

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }
        
        $cacheKey = 
            "kpis:" . 
            ($startDate ?? 'all') . ":" . 
            ($endDate ?? 'all') . ":" . 
            ($traffic ?? 'all') . ":" .
            ($priority ?? 'all');

        $result = Cache::remember(
            $cacheKey,
            self::TTL,
            function() use ($whereSql, $params) {
                return DB::select("
                    SELECT 
                        COUNT(*) AS total_deliveries,

                        ROUND(PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY delay)::numeric,3) AS median_delay_mins,

                        ROUND(
                            (SUM(CASE WHEN on_time = true THEN 1 ELSE 0 END) * 100.0)
                            / COUNT(*)::numeric, 2
                        ) AS on_time_percentage

                    FROM delivery_records
                    $whereSql",
                    $params
                );
            }
        );
        
        return response()->json($result);
    }

    public function weatherStats(Request $request) {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $traffic = $request->query('traffic');
        $priority = $request->query('priority');

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "date >= :start_date";
            $params['start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "date <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($traffic) {
            $where[] = "traffic_cond = :traffic";
            $params['traffic'] = $traffic;
        }

        if ($priority) {
            $where[] = "priority = :priority";
            $params['priority'] = $priority;
        }

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }
        
        $cacheKey = 
            "weather-stats:" . 
            ($startDate ?? 'all') . ":" . 
            ($endDate ?? 'all') . ":" . 
            ($traffic ?? 'all') . ":" .
            ($priority ?? 'all');

        $result = Cache::remember(
            $cacheKey,
            self::TTL,
            function() use($whereSql, $params) {
                return DB::select("
                    SELECT
                        weather,
                        ROUND(
                            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY delay)::numeric, 2
                        ) AS median_delay_mins,
            
                        ROUND(
                            AVG(est_veh_spd)::numeric, 2
                        ) AS est_vehicle_speed_kmh
            
                    FROM delivery_records
                    $whereSql
                    GROUP BY weather;", $params
                );
            }
        );

        return response()->json($result);
    }

    public function vehicleStats(Request $request) {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $traffic = $request->query('traffic');
        $priority = $request->query('priority');

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "date >= :start_date";
            $params['start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "date <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($traffic) {
            $where[] = "traffic_cond = :traffic";
            $params['traffic'] = $traffic;
        }

        if ($priority) {
            $where[] = "priority = :priority";
            $params['priority'] = $priority;
        }

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }
        
        $cacheKey = 
            "vehicle-stats:" . 
            ($startDate ?? 'all') . ":" . 
            ($endDate ?? 'all') . ":" . 
            ($traffic ?? 'all') . ":" .
            ($priority ?? 'all');

        $result = Cache::remember(
            $cacheKey,
            self::TTL,
            function() use ($whereSql, $params) {
                return DB::select("
                    WITH row_count AS (
                        SELECT COUNT(*) AS total_rows
                        FROM delivery_records
                    )

                    SELECT 
                        dr.vehicle_type,
                        COUNT(*) AS \"count\",
                        ROUND(100.0 * COUNT(*) / rc.total_rows::numeric, 2) AS proportion

                    FROM delivery_records AS dr
                    CROSS JOIN row_count AS rc
                    $whereSql
                    GROUP BY dr.vehicle_type, rc.total_rows
                    ORDER BY proportion DESC;", $params
                );
            }
        );

        return response()->json($result);
    }

    public function priorityStats(Request $request) {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $traffic = $request->query('traffic');
        $priority = $request->query('priority');

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "date >= :start_date";
            $params['start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "date <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($traffic) {
            $where[] = "traffic_cond = :traffic";
            $params['traffic'] = $traffic;
        }

        if ($priority) {
            $where[] = "priority = :priority";
            $params['priority'] = $priority;
        }

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }
        
        $cacheKey = 
            "priority-stats:" . 
            ($startDate ?? 'all') . ":" . 
            ($endDate ?? 'all') . ":" . 
            ($traffic ?? 'all') . ":" .
            ($priority ?? 'all');

        $result = Cache::remember(
            $cacheKey,
            self::TTL,
            function() use($whereSql, $params) {
                return DB::select("
                    WITH row_count AS (
                        SELECT COUNT(*) AS total_rows
                        FROM delivery_records
                    )

                    SELECT
                        dr.priority,
                        ROUND(100.0 * COUNT(*) / rc.total_rows::numeric, 2) AS proportion,
                        COUNT(*) FILTER(WHERE on_time = true) AS on_time_count,
                        COUNT(*) FILTER(WHERE on_time = false) AS late_count,

                        ROUND(
                            PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY dr.delay)::numeric, 2
                        ) AS median_delay_mins

                    FROM delivery_records AS dr
                    CROSS JOIN row_count AS rc
                    $whereSql
                    GROUP BY dr.priority, rc.total_rows;", $params
                );
            }
        );

        return response()->json($result);
    }

    public function weekdayDelay(Request $request) {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $traffic = $request->query('traffic');
        $priority = $request->query('priority');

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "date >= :start_date";
            $params['start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "date <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($traffic) {
            $where[] = "traffic_cond = :traffic";
            $params['traffic'] = $traffic;
        }

        if ($priority) {
            $where[] = "priority = :priority";
            $params['priority'] = $priority;
        }

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }
        
        $cacheKey = 
            "weekday-delay:" . 
            ($startDate ?? 'all') . ":" . 
            ($endDate ?? 'all') . ":" . 
            ($traffic ?? 'all') . ":" .
            ($priority ?? 'all');

        $result = Cache::remember(
            $cacheKey,
            self::TTL,
            function() use($whereSql, $params) {
                return DB::select("
                    SELECT 
                    dr.weekday,
                    ROUND(  
                        PERCENTILE_CONT(0.5) WITHIN GROUP (ORDER BY dr.delay)::numeric, 2
                    )AS median_delay_mins

                    FROM delivery_records AS dr
                    $whereSql
                    GROUP BY dr.weekday 
                    ORDER BY (
                        CASE dr.weekday
                            WHEN 'Monday' THEN 1
                            WHEN 'Tuesday' THEN 2
                            WHEN 'Wednesday' THEN 3
                            WHEN 'Thursday' THEN 4
                            WHEN 'Friday' THEN 5
                            WHEN 'Saturday' THEN 6
                            WHEN 'Sunday' THEN 7
                            ELSE 0 END
                        );", $params
                );
            }
        );

        return response()->json($result);
    }

    public function weekTotalDelay(Request $request) {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $traffic = $request->query('traffic');
        $priority = $request->query('priority');

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "date >= :start_date";
            $params['start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "date <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($traffic) {
            $where[] = "traffic_cond = :traffic";
            $params['traffic'] = $traffic;
        }

        if ($priority) {
            $where[] = "priority = :priority";
            $params['priority'] = $priority;
        }

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }
        
        $cacheKey = 
            "week-total-delay:" . 
            ($startDate ?? 'all') . ":" . 
            ($endDate ?? 'all') . ":" . 
            ($traffic ?? 'all') . ":" .
            ($priority ?? 'all');

        $result = Cache::remember(
            $cacheKey,
            self::TTL,
            function() use($whereSql, $params) {
                return DB::select("
                    SELECT
                    DATE_TRUNC('week', date) AS week_start,
                    ROUND(SUM(delay)::numeric, 2) AS total_delay_mins

                    FROM delivery_records
                    $whereSql
                    GROUP BY week_start 
                    ORDER BY week_start;", $params
                );
            }
        );

        return response()->json($result);
    }



    public function monthTotalDelay(Request $request) {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $traffic = $request->query('traffic');
        $priority = $request->query('priority');

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "date >= :start_date";
            $params['start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "date <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($traffic) {
            $where[] = "traffic_cond = :traffic";
            $params['traffic'] = $traffic;
        }

        if ($priority) {
            $where[] = "priority = :priority";
            $params['priority'] = $priority;
        }

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }
        
        $cacheKey = 
            "month-total-delay:" . 
            ($startDate ?? 'all') . ":" . 
            ($endDate ?? 'all') . ":" . 
            ($traffic ?? 'all') . ":" .
            ($priority ?? 'all');

        $result = Cache::remember(
            $cacheKey,
            self::TTL,
            function() use($whereSql, $params) {
                return DB::select("
                    SELECT
                    DATE_TRUNC('month', date) AS month_start,
                    ROUND(SUM(delay)::numeric, 2) AS total_delay_mins

                    FROM delivery_records
                    $whereSql
                    GROUP BY month_start 
                    ORDER BY month_start;", $params
                );
            }
        );

        return response()->json($result);
    }

    public function ratingSummary(Request $request) {

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $traffic = $request->query('traffic');
        $priority = $request->query('priority');

        $where = [];
        $params = [];

        if ($startDate) {
            $where[] = "date >= :start_date";
            $params['start_date'] = $startDate;
        }

        if (!empty($endDate)) {
            $where[] = "date <= :end_date";
            $params['end_date'] = $endDate;
        }

        if ($traffic) {
            $where[] = "traffic_cond = :traffic";
            $params['traffic'] = $traffic;
        }

        if ($priority) {
            $where[] = "priority = :priority";
            $params['priority'] = $priority;
        }

        $whereSql = "";

        if (!empty($where)) {
            $whereSql = "WHERE " . implode(" AND ", $where);
        }
        
        $cacheKey = 
            "rating-summary:" . 
            ($startDate ?? 'all') . ":" . 
            ($endDate ?? 'all') . ":" . 
            ($traffic ?? 'all') . ":" .
            ($priority ?? 'all');

        $result = Cache::remember(
            $cacheKey,
            self::TTL,
            function() use($whereSql, $params) {
                return DB::select("
                    SELECT
                        r.rating_name,
                        ROUND(AVG(r.vals)::numeric, 2) AS average
                    FROM delivery_records, 
                    LATERAL (
                        VALUES
                            ('Attitude', attitude),
                            ('Package Care', pkg_care),
                            ('Responsiveness', responsiveness),
                            ('Delivery Speed', delivery_spd)
                            ) AS r(rating_name, vals)
                    $whereSql
                    GROUP BY r.rating_name;", $params
                );
            }
        );

        return response()->json($result);
    }
}



