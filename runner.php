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
echo json_encode($proc->processFromScratch($reference, $playerstats)->getState());
echo json_encode($proc->processFromScratch($reference, $clanwars)->getState());
