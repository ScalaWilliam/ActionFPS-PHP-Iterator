<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../vendor/autoload.php';
require  __DIR__ . '/common.php';


$clan_id = $_GET['id'];

$clan = find_clan($clan_id);

$clan->wars = get_clanwars(15, null, $clan_id);
$clan->stats = get_clanstats(0, $clan_id)->now;

echo json_encode($clan);


