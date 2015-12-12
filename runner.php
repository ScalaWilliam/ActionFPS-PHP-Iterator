<?php
require_once __DIR__ . '/vendor/autoload.php';
header("Content-Type: application/json");
require_once "state/PlayerStats.php";

//$game_counter = new GameCounter();
$playerstats = new PlayerStatsAccumulator();

$reference = new ActionFPS\GamesCachedActionReference();
$proc = new ActionFPS\Processor();
echo json_encode($proc->processFromScratch($reference, $playerstats)->getState());

