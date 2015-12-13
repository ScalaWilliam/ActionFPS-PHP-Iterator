<?php
require_once __DIR__ . '/vendor/autoload.php';
header("Content-Type: application/json");

require_once "state/PlayerStats.php";
require_once "state/Clanwars.php";

//$game_counter = new GameCounter();
$playerstats = new PlayerStatsAccumulator();
$clanwars = new ClanwarsAccumulator();

$reference = new ActionFPS\GamesCachedActionReference();
$proc = new ActionFPS\Processor();

$start_time = microtime(true);
$proc->processFromScratch($reference, $playerstats)->saveToFile("data/playerstats.json");
$proc->processFromScratch($reference, $clanwars)->saveToFile("data/clanwars.json");
echo "\n" . microtime(true) - $start_time;
