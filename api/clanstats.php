<?php require_once __DIR__ . '../vendor/autoload.php';

$clanstats_state->loadFromFile("data/clanstats.json");

print_r($clanstats_state->getState());

