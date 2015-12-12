<?php
class Clanwar implements JsonSerializable
{
    public $startTime = "";
    public $endTime = "";
    public $clans = array();
    public $games = array();
    public $server = "";
    public $teamsize = "";
    public $complete = false;
    public $winner = null;
    
    public function __construct($game)
    {
        $this->startTime = $game->gameTime;
        $this->clans = array($game->teams[0]->clan, $game->teams[1]->clan);
        for($i = 0; $i < 2; ++$i)
        {
            $this->clans[$i]->clan = '';
            $this->clans[$i]->wins = 0;
            $this->clans[$i]->won = array();
            $this->clans[$i]->score = $this->clans[$i]->flags = $this->clans[$i]->frags = 0;
            $this->clans[$i]->players = array();

        }
        sort($this->clans);
        $this->teamsize = min(count($game->teams[0]->players), count($game->teams[1]->players));
        $this->addGame($game);
    }
    
    public static function add_player(&$players, $name, $user = '')
    {
        $player = array();
        if($user) $player->user = $user;
        $player->name = $name;
        $player->score = $player->flags = $player->frags = $player->deaths = 0;
        $players[] = $player;
        return count($players)-1;
    }
    
    public static function lookup_player($players, $name, $user = '')
    {
        foreach($players as $key => $player)
        {
            if(($user && array_key_exists('user', $player) && $user == $player->user) || $name == $player->name) return $key;
        }
        return false;

    }
    
    public function timeDiff($game)
    {
        $last_game_end = new DateTime($this>endTime);
        $game_start = new DateTime($game->gameTime);
        return $game_start->getTimestamp() - $last_game_end->getTimestamp();
    }
    
    public function isNext($game)
    {
        $teamsize = min(count($game->teams[0]->players), count($game->teams[1]->players));

        $clans = array($game->teams[0]->clan, $game->teams[1]->clan);
        sort($clans);

        $time = new DateTime($game->gameTime);
        $prev_time = new DateTime($prev_game->gameTime);
        $interval = $time->getTimestamp() - $prev_time->getTimestamp();

        return ($game->server == $this->server
         && $clans == $this->clans
         && $teamsize == $this->teamsize
         && $interval <= $game->duration * 60 + 10*60);

    }
    
    public function addGame($game)
    {
        $this->games[] = $game;
        
        $tie = !isset($game->winner);
        foreach($game->teams as $n => $team)
        {
            $win = $n == 0 && !$tie;
            $id = $team->clan == $this->clans[0] ? 0 : 1;
            if($win)
            {
                $this->clans[$id]['wins']++;
                $this->clans[$id]['won'][] = $game->gameTime; // FIXME use ID even if both are = ATM
            }
            if(isset($team->flags)) $this->clans[$id]['flags'] += $team->flags;
            $this->clans[$id]->frags += $team->frags;
            $this->clans[$id]->score += sum_team_players($team, 'score');

            foreach($team->players as $player)
            {
                $user = isset($player->user) ? $player->user : '';
                $n = self::lookup_player($this->clans[$id]->players, $player->name, $user);
                if($n === false) $n = add_player($this->clans[$id]->players, $player->name, $user);
                if(isset($player->score)) $this->clans[$id]->players[$n]->score += $player->score;
                $this->clans[$id]->players[$n]->frags += $player->frags;
                $this->clans[$id]->players[$n]->deaths += $player->deaths;
            }
        }
        $this->endTime = $game->startTime + 60 * $game->duration; // FIXME
        
        awardTrophees();
        decideWinner();
    }
    
    public function awardTrophees()
    {
        $mvp_points = array(-1000, -1000);
        foreach($this->clans as $i => &$clan)
        {
            foreach($this->clans as $player)
            {
                if($player['score'] > $mvp_points[$i])
                {
                    $clan['mvp']['name'] = $player['name'];
                    if(isset($player['user'])) $clan['mvp']['user'] = $player['user']; 
                    $mvp_points[$i] = $player['score'];
                }
            }
        }

    }
    
    public function decideWinner()
    {
        $delta_wins = $this->clans[0]['wins'] - $clanwar->clans[1]['wins'];
        if($delta_wins < 0) $this->clans = array_reverse($this->clans);
        $this->winner = ($delta_wins != 0) ? $this->clans[0]['clan'] : null;
        $this->complete = $this->winner && count($this->games) > 1;
    }
    
    public function jsonSerialize() {
        for($i = 0; $i < 2; ++$i) if(!count($this->clans[$i]["won"])) unset($this->clans[$i]["won"]);
        return $this;
    }
}

class ClanwarsAccumulator implements ActionFPS\OrderedActionIterator
{
    public static protected function sum_players($players, $attribute)
    {
        $sum = 0;
        foreach($players as $player) if(isset($player->$attribute)) $sum += $player->$attribute; 
        return $sum;
    }
    
    public function reduce(ActionFPS\ActionReference $reference, $state, $game)
    {
        for($i = count($state); $i >= 0; --$i)
        {
            if($state[$i]->timeDiff($game) >= 10 * 60) break;
            else if($state[$i]->isNext($game))
            {
                $state[$i]->addGame($game);
                return $state;
            }
        }
        
        $state[] = new Clanwar($game);
        return $state;
    }

    public function initialState()
    {
        return [];
    }
}