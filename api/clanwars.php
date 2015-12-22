<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../vendor/autoload.php';
require  __DIR__ . '/common.php';

/*$sort_params = array('startTime', 'endTime');
$sort_param = !empty($_GET['sort']) && in_array($_GET['sort'], $sort_params) ? $_GET['sort'] : 'startTime';*/

$selected = get_clanwars(!empty($_GET['count']) ? $_GET['count'] : null,
                         !empty($_GET['completed']),
                         !empty($_GET['clan']) ? $_GET['clan'] : null);

echo json_encode($selected);
