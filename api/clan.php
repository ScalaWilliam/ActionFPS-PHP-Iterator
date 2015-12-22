<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../vendor/autoload.php';
require  __DIR__ . '/common.php';


$clan_id = $_GET['id'];

$reference = new ActionFPS\GamesCachedActionReference();
$clans = $reference->getClans();

$clan = new stdClass();

foreach($clans as $_clan)
{
    if($_clan->id == $clan_id)
        $clan = $_clan;
}

$clan->clanwars = get_clanwars(10, null, $clan_id);
$clan->stats = get_clanstats(0, $clan_id)->now;

echo json_decode($clan);


