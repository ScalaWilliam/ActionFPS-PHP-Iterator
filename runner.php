<?php
require_once __DIR__ . '/vendor/autoload.php';
header("Content-Type: application/json");

require_once "state/PlayerStats.php";
require_once "state/Clanwars.php";
require_once "state/Clanstats.php";

//$game_counter = new GameCounter();
$playerstats = new PlayerStatsAccumulator();
$clanwars = new ClanwarsAccumulator();

$reference = new ActionFPS\GamesCachedActionReference();
$proc = new ActionFPS\Processor();

$start_time = microtime(true);
$proc->processFromScratch($reference, $playerstats)->saveToFile("data/playerstats.json");
$clanwars_state = $proc->processFromScratch($reference, $clanwars);
$clanwars_state->saveToFile("data/clanwars.json");

$state = $clanwars_state->getState();
$clanwars = [];
foreach($state->unprocessed as $war)
{
    $clanwars[] = $state->completed[$war];
}
$proc->processNew($reference, $clanstats, new ActionFPS\BasicStateResult([], []), $clanwars)->saveToFile("data/clanstats.json");

echo "\n" . microtime(true) - $start_time;