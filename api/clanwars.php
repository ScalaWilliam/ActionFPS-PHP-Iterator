<?php
require_once __DIR__ . '/../vendor/autoload.php';
header("Content-Type: application/json");

// <!-- FIXME: more elegant way to call these dependencies (class Clanwar and ActionFPS\BasicStateResult)
require_once "state/Clanwars.php";
$processor = new ActionFPS\Processor();
// -->


$sort_params = array('startTime', 'endTime');
$sort_param = !empty($_GET['sort']) && in_array($_GET['sort'], $sort_params) ? $_GET['sort'] : 'startTime';

function sort_func($a, $b)
{
    global $sort_param;
    if($a->{$sort_param} == $b->{$sort_param}) return 0;
    return $a->{$sort_param} > $b->{$sort_param} ? -1 : 1;
}

$clanwars_state = new ActionFPS\BasicStateResult([], []);

$clanwars_state->loadFromFile("data/clanwars.json");
$clanwars = $clanwars_state->getState();

$selected = [];

foreach($clanwars->completed as $id => $clanwar)
{
    $selected[$id] = $clanwar;
}

if(empty($_GET['completed'])) foreach($clanwars->incomplete as $id => $clanwar)
{
    $selected[$id] = $clanwar;
}

usort($selected, 'sort_func');

if(!empty($_GET['count']))
    $selected = array_slice($selected, 0, (int)$_GET['count']);

echo json_encode($selected);
