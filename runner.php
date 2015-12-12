<?php
require_once __DIR__ . '/vendor/autoload.php';
header("Content-Type: application/json");
require_once "GameCounter.php";
$game_counter = new GameCounter();
$reference = new ActionFPS\GamesCachedActionReference();
$proc = new ActionFPS\Processor();
echo json_encode($proc->processFromScratch($reference, $game_counter)->getState());

