<?php
require_once __DIR__ . '/vendor/autoload.php';
header("Content-Type: application/json");
require_once "GameCounter.php";
$game_counter = new GameCounter();
$reference = new ActionFPS\GamesCachedActionReference();
/**
 * Support zip files
 */
//$reference = new ActionFPS\ZipActionReference($_FILES['x']['tmp_name']);

//$reference = new ActionFPS\ZipActionReference("rf/rf.zip");

$proc = new ActionFPS\Processor();
echo json_encode($proc->processFromScratch($reference, $game_counter)->getState());

