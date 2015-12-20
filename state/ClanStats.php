<?php
class ClanStats implements JsonSerializable 
{
    public $clan;
    public $name;
    public $elo = 1000;
    public $wins = 0;
    public $losses = 0;
    public $ties = 0;
    public $wars = 0;
    public $games = 0;
    public $gamewins = 0;
    public $score = 0;
    public $flags = 0;
    public $frags = 0;
    public $deaths = 0;
    
    public function __construct($id)
    {
        $this->clan = $id;
    }
    
    public function jsonSerialize() {
        return $this;
    }
}

class ClanStatsAccumulator implements ActionFPS\OrderedActionIterator
{   
    public function clanExists($state, $id)
    {
        return array_key_exists($id, $state);
    }
    
    public static function sortFunc($a, $b)
    {
        if($a->elo = $b->elo) return 0;
        return $a->elo > $b->elo ? -1 : 1;
    }
    
    public function sortClans(&$clans)
    {
        usort($clans, 'ClansStatsAccumulator::sortFunc');
        
        $i = 1;
        foreach($clans as &$clan)
        {
            $clan->rank = $i;
            $i++;
        }
    }
    
    public function reduce(ActionFPS\ActionReference $reference, $state, $war)
    {
        $time = new DateTime($war->startTime);
        $time = $time->getTimestamp();
        if($time >= $state->lastupdate)
        {
            $state->states[$state->lastupdate] = [];
            foreach($state->now as $clan => $clan_state)
            {
                $state->states[$state->lastupdate][$clan] = clone $clan_state;
            }
            $this->sortClans($state->states[$state->lastupdate]);
            $state->lastupdate = strtotime("tomorrow", $time);
        }
    
        $winner_id = $war->clans[0]->clan;
        $loser_id = $war->clans[1]->clan;
       
        $tie = !isset($war->winner);
        foreach($war->clans as $n => $clan)
        {
            $id = $clan->clan;
            $win = $n == 0;
            if(!$this->clanExists($state->now, $clan->clan)) $state->now[$id] = new ClanStats($id);
            if(!$tie) $state->now[$id]->{$win ? 'wins' : 'losses'}++;
            else $state->now[$id]->ties++;
            $state->now[$id]->wars++;
            $state->now[$id]->games += count($war->games);
            $state->now[$id]->gamewins += $clan->wins;
            if(isset($clan->flags)) $state->now[$id]->flags += $clan->flags; 
            $state->now[$id]->frags += $clan->frags;
            $state->now[$id]->score += $clan->score;
        }

        $winner = &$state->now[$winner_id];
        $loser = &$state->now[$loser_id];

        $delta = $winner->elo - $loser->elo;
        $p = 1/(1+pow(10, -$delta/400)); // probability for the winning clan to win

        $k = 40 * count($war->games);

        if($tie)
        {
            $winner->elo += $k * (0.5 - $p);
            $loser->elo  -= $k * (0.5 - $p);
        }
        else
        {
            $winner->elo += $k * (1 - $p);
            $loser->elo  -= $k * (1 - $p);
        }
        //$this->sortClans($state->now);
        return $state;
    }

    public function initialState()
    {
        $state = new stdClass();
        $state->lastupdate = 0;
        $state->now = [];
        $state->stats = [];
        return $state;
    }
}
