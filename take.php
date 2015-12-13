<?php
require_once __DIR__ . '/vendor/autoload.php';
header("Content-Type: application/json");

require_once "state/PlayerStats.php";
require_once "state/Clanwars.php";

file_put_contents('messages', print_r($_SERVER['argv'], true), FILE_APPEND);

$game = json_decode($_SERVER['argv'][1]);

$playerstats = new PlayerStatsAccumulator();
$clanwars = new ClanwarsAccumulator();

$reference = new ActionFPS\GamesCachedActionReference();
$proc = new ActionFPS\Processor();

$playerstats_state = new ActionFPS\BasicStateResult([], []);
$clanwars_state = new ActionFPS\BasicStateResult([], []);

$playerstats_state->loadFromFile("data/playerstats.json");
$clanwars_state->loadFromFile("data/clanwars.json");

$proc->processNew($reference, $playerstats, $playerstats_state)->saveToFile("data/playerstats.json");
$proc->processNew($reference, $clanwars, $clanwars_state)->saveToFile("data/clanwars.json");
?>
