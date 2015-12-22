<?php
// <!-- FIXME: more elegant way to call these dependencies (class Clanwar and ActionFPS\BasicStateResult)
$processor = new ActionFPS\Processor();
require_once __DIR__ . "/../state/Clanwars.php";
require_once __DIR__ . "/../state/ClanStats.php";

$sort_param = null;

function sort_func($a, $b)
{
    global $sort_param;
    if($a->{$sort_param} == $b->{$sort_param}) return 0;
    return $a->{$sort_param} > $b->{$sort_param} ? -1 : 1;
}

function get_clans()
{
    $reference = new ActionFPS\GamesCachedActionReference();
    return $reference->getClans();
}

function find_clan($id)
{
    static $clans = get_clans();
    foreach($clans as $clan) if($clan->id == $id)
    {
        return $clan;
    }
}

function get_clanwars($count = null, $completed = null, $clan = null, $wid = null)
{
    global $sort_param;
    $sort_param = 'startTime';
    $clanwars_state = new ActionFPS\BasicStateResult([], []);

    $clanwars_state->loadFromFile(__DIR__ . "/../data/clanwars.json");
    $clanwars = $clanwars_state->getState();

    $selected = [];

    if(!$wid)
    {
        foreach($clanwars->completed as $id => $clanwar)
        {
            if($clan && $clanwar->clans[0]->clan != $clan && $clanwar->clans[1]->clan != $clan) continue;
            $selected[$id] = $clanwar;
        }

        if(!$completed) foreach($clanwars->incomplete as $id => $clanwar)
        {
            if($clan && $clanwar->clans[0]->clan != $clan && $clanwar->clans[1]->clan != $clan) continue;
            $selected[$id] = $clanwar;
        }
        usort($selected, 'sort_func');
    }
    else
    {
        if(array_key_exists($wid, $clanwars->completed))
            $selected[$wid] = $clanwars->completed[$wid];
        else if(array_key_exists($wid, $clanwars->incomplete))
            $selected[$wid] = $clanwars->incomplete[$wid];
    }

    if($count > 0)
        $selected = array_slice($selected, 0, (int)$count);
    
    foreach($selected as &$clanwar) foreach($clanwar->clans as &$clan)
    {
        $clan->name = find_clan($clan->clan)->name;
    }

    return $selected;
}

function get_clanstats($count = 15, $clan = null, $time = false)
{
    global $sort_param;
    $clanstats_state = new ActionFPS\BasicStateResult([], []);

    $clanstats_state->loadFromFile(__DIR__ . "/../data/clanstats.json");
    $clanstats = $clanstats_state->getState();
    
    $sort_param = 'elo';
    uasort($clanstats->now, 'sort_func');
    
    $i = 1;
    foreach($clanstats->now as &$_clan)
    {
        $_clan->rank = $i;
        $i++;
    }
    
    $stats = new stdClass();
    $stats->now = [];
    
    if(!$clan)
    {
        $i = 1;
        foreach($clanstats->now as $id => &$_clan)
        {
            if($count <= 0 || $i <= $count) $stats->now[$id] = $_clan;
            $i++;
        }
        
        if($time)
        {
            $stats->states = [];
            foreach($clanstats->states as $date => $state)
            {
                $stats->states[$date] = [];
                $i = 1;
                foreach($clanstats->states as $id => &$_clan)
                {
                    if($count <= 0 || $i <= $count) $stats->states[$date][$id] = $_clan;
                    $i++;
                }
            }
        }
    }
    else
    {
        if(array_key_exists($clan, $clanstats->now))
            $stats->now = $clanstats->now[$clan];
        
        if($time)
        {
            $stats->states = [];
            foreach($clanstats->states as $date => $state)
            {
                if(array_key_exists($clan, $state))
                {
                    $stats->states[$date] = $state[$clan];
                }
            }
        }
    }
    return $stats;
    
}