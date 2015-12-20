<?php
require_once __DIR__ . '/vendor/autoload.php';
header("Content-Type: application/json");

require_once "state/PlayerStats.php";
require_once "state/Clanwars.php";
require_once "state/ClanStats.php";

//$game_counter = new GameCounter();
$playerstats = new PlayerStatsAccumulator();
$clanwars = new ClanwarsAccumulator();
$clanstats = new ClanStatsAccumulator();

$reference = new ActionFPS\GamesCachedActionReference();
$proc = new ActionFPS\Processor();

$start_time = microtime(true);
$proc->processGamesFromScratch($reference, $playerstats)->saveToFile("data/playerstats.json");
$clanwars_state = $proc->processGamesFromScratch($reference, $clanwars);
$clanwars_state->saveToFile("data/clanwars.json");

$state = $clanwars_state->getState();
$wars = $state->completed;
$proc->processFromScratch($reference, $clanstats, $wars)->saveToFile("data/clanstats.json");

echo "\n" . microtime(true) - $start_time;