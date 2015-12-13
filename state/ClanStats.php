<?php
class ClanStats implements JsonSerializable 
{
    public $clan;
    public $name;
    public $elo = 1000;
    public $wins = 0;
    public $losses = 0;
    public $ties = 0;
    public $clanwars = 0;
    public $games = 0;
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
    
    public function reduce(ActionFPS\ActionReference $reference, $state, $war)
    {
        $winner_id = $war->clans[0]->clan;
        $loser_id = $war->clans[1]->clan;
       
        $tie = !isset($war->winner);
        foreach($war->clans as $n => $clan)
        {
            $id = $clan->clan;
            $win = $n == 0;
            if(!$this->clanExists($state, $clan->clan)) $state[$id] = new ClanStats($id);
            if(!$tie) $state[$id]->{$win ? 'wins' : 'losses'}++;
            else $state[$id]->ties++;
            $state[$id]->wars++;
            $state[$id]->games += count($war->games);
            $state[$id]->gamewins += $clan->wins;
            if(isset($clan->flags)) $state[$id]->flags += $clan->flags; 
            $state[$id]->frags += $clan->frags;
            $state[$id]->score += $clan->score;
        }

        $winner = &$state[$winner_id];
        $loser = &$state[$loser_id];

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
        return $state;
    }

    public function initialState()
    {
        return [];
    }
}
