<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../vendor/autoload.php';
require  __DIR__ . '/common.php';

$selected = get_clanwars(null, null, null, $_GET['id']);
echo json_encode($selected[$_GET['id']]);
