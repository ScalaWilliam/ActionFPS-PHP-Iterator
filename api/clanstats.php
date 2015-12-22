<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../vendor/autoload.php';
require  __DIR__ . '/common.php';

$stats = get_clanstats(15);

echo json_encode($stats);