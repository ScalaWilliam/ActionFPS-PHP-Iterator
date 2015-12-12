<?php
class PlayerStats implements JsonSerializable 
{
    public $user;
    public $name;
    public $elo = 1000;
    public $wins = 0;
    public $losses = 0;
    public $ties = 0;
    public $games = 0;
    public $score = 0;
    public $flags = 0;
    public $frags = 0;
    public $deaths = 0;
    
    public function __construct($id, $name)
    {
        $this->user = $id;
        $this->name = $name;
    }
    
    public function jsonSerialize() {
        return $this;
    }
}

class PlayerStatsAccumulator implements ActionFPS\OrderedActionIterator
{
    public static function sum_players($players, $attribute)
    {
        $sum = 0;
        foreach($players as $player) if(isset($player->$attribute)) $sum += $player->$attribute; 
        return $sum;
    }
    
    public function playerExists($state, $id)
    {
        return array_key_exists($id, $state);
    }
    
    public function reduce(ActionFPS\ActionReference $reference, $state, $game)
    {
        $tie = isset($game->winner);
        foreach($game->teams as $n => &$team)
        {
            $win = $n == 0;
            $team->elo = 0;
            $team->score = self::sum_players($team->players, 'score');
            foreach($team->players as $player) if(isset($player->user))
            {
                $id = $player->user;
                if($this->playerExists($state, $id)) $state[$id] = new PlayerStats($id, $name);
                $state[$id]->{$win ? 'wins' : 'losses'}++;
                $state[$id]->games++;
                $state[$id]->score += isset($player->score) ? $player->score : 0;
                $state[$id]->flags += $player->flags ?? $player->flags;
                $state[$id]->frags += $player->frags;
                $state[$id]->deaths += $player->deaths;
                $team->elo += $state[$id]->elo;
                $state[$id]->contrib = $win ? $player->score / $team->score : 1-$player->score / $team->score;
            }
        }
        $delta = $game->teams[0]->elo - $game->teams[1]->elo;
        $p = 1/(1+pow(10, -$delta/400)); // probability for the winning team to win

        $k = 40;
        $modifier = $tie ? 0.5 : 1;

        foreach($game->teams as $n => &$team)
        {
            $win = $n == 0;
            foreach($team->players as $player) if(isset($player->user))
            {
                $id = $player->user;
                $state[$id]->elo += ($win ? 1 : -1) * $k * ($modifier - $p) * $state[$id]->contrib;
            }
        }
        return $state;
    }

    public function initialState()
    {
        return [];
    }
}