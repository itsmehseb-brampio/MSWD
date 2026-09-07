<?php
/**
 * Computes $dchart for disaster report pages.
 * Requires: $conn and $dchart_scope ('admin'|'barangay'), $dchart_where (SQL WHERE for the page scope).
 * Optional: $dchart_barangay_id (for barangay scope).
 */
if (!isset($dchart_where)) $dchart_where = '1=1';
$dchart_scope = $dchart_scope ?? 'admin';

$dchart_status = ['pending' => 0, 'approved' => 0, 'declined' => 0, 'cancelled' => 0, 'reedit' => 0];
if ($dchart_scope === 'barangay' && !empty($dchart_barangay_id)) {
    $r = $conn->query("SELECT status, COUNT(*) AS c FROM disaster_reports WHERE barangay_id=" . intval($dchart_barangay_id) . " GROUP BY status");
    if ($r) while ($x = $r->fetch_assoc()) if (isset($dchart_status[$x['status']])) $dchart_status[$x['status']] = intval($x['c']);
} else {
    $r = $conn->query("SELECT status, COUNT(*) AS c FROM disaster_reports GROUP BY status");
    if ($r) while ($x = $r->fetch_assoc()) if (isset($dchart_status[$x['status']])) $dchart_status[$x['status']] = intval($x['c']);
}

$dchart_months = []; $dchart_monthly = [];
$r = $conn->query("SELECT DATE_FORMAT(created_at,'%Y-%m') AS ym, COUNT(*) AS c FROM disaster_reports WHERE $dchart_where GROUP BY ym ORDER BY ym");
if ($r) while ($x = $r->fetch_assoc()) { $dchart_months[] = date('M y', strtotime($x['ym'] . '-01')); $dchart_monthly[] = intval($x['c']); }

$dchart_barangays = []; $dchart_barangay_reports = [];
$dchart_status_by_brgy = ['labels' => [], 'pending' => [], 'approved' => [], 'declined' => [], 'cancelled' => [], 'reedit' => []];
if ($dchart_scope === 'admin') {
    $r = $conn->query("SELECT b.barangay_name AS n, COUNT(*) AS c FROM disaster_reports dr LEFT JOIN barangays b ON dr.barangay_id=b.barangay_id WHERE $dchart_where GROUP BY dr.barangay_id ORDER BY c DESC");
    if ($r) while ($x = $r->fetch_assoc()) { $dchart_barangays[] = $x['n'] ?: 'Unknown'; $dchart_barangay_reports[] = intval($x['c']); }
    $dchart_status_by_brgy['labels'] = $dchart_barangays;
    $map = [];
    $r = $conn->query("SELECT b.barangay_name AS n, dr.status AS s, COUNT(*) AS c FROM disaster_reports dr LEFT JOIN barangays b ON dr.barangay_id=b.barangay_id WHERE $dchart_where GROUP BY dr.barangay_id, dr.status");
    if ($r) while ($x = $r->fetch_assoc()) { $n = $x['n'] ?: 'Unknown'; if (!isset($map[$n])) $map[$n] = []; if (isset($dchart_status_by_brgy[$x['s']])) $map[$n][$x['s']] = intval($x['c']); }
    foreach (['pending', 'approved', 'declined', 'cancelled', 'reedit'] as $s) {
        $out = [];
        foreach ($dchart_barangays as $n) $out[] = $map[$n][$s] ?? 0;
        $dchart_status_by_brgy[$s] = $out;
    }
}

$dchart_damage = ['Totally' => 0, 'Partially' => 0];
$r = $conn->query("SELECT SUM(damage_extent='Totally') AS t, SUM(damage_extent='Partially') AS p FROM disaster_reports WHERE $dchart_where");
if ($r) { $d = $r->fetch_assoc(); $dchart_damage['Totally'] = intval($d['t']); $dchart_damage['Partially'] = intval($d['p']); }

$dchart = [
    'scope' => $dchart_scope,
    'months' => $dchart_months,
    'monthly' => $dchart_monthly,
    'barangays' => $dchart_barangays,
    'barangay_reports' => $dchart_barangay_reports,
    'status_by_brgy' => $dchart_status_by_brgy,
    'status' => $dchart_status,
    'damage' => $dchart_damage
];
