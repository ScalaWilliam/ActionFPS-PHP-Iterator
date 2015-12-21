<?php
class Clanwar implements JsonSerializable
{
    public $id = "";
    public $startTime = "";
    public $endTime = "";
    public $clans = array();
    public $games = array();
    public $server = "";
    public $teamsize = "";
    public $completed = false;
    public $winner = null;
    
    public function __construct($game)
    {
        $this->id = $this->startTime = $game->gameTime;
        $this->clans = array(new stdClass(), new stdClass());
        $this->server = $game->server;
        
        $clans = array($game->teams[0]->clan, $game->teams[1]->clan);
        sort($clans);
        
        for($i = 0; $i < 2; ++$i)
        {
            $this->clans[$i]->clan = $clans[$i];
            $this->clans[$i]->wins = 0;
            $this->clans[$i]->won = [];
            $this->clans[$i]->score = $this->clans[$i]->flags = $this->clans[$i]->frags = 0;
            $this->clans[$i]->players = [];
            $this->clans[$i]->Trophies = new stdClass();

        }
        sort($this->clans);
        $this->teamsize = min(count($game->teams[0]->players), count($game->teams[1]->players));
        $this->addGame($game);
    }
    
    public static function add_player(&$players, $name, $user = '')
    {
        $player = new stdClass();
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
        $last_game_end = new DateTime($this->endTime);
        $game_start = new DateTime($game->gameTime);
        return $game_start->getTimestamp() - $last_game_end->getTimestamp();
    }
    
    public function isNext($game)
    {
        $teamsize = min(count($game->teams[0]->players), count($game->teams[1]->players));
        
        $current_clans = array($this->clans[0]->clan, $this->clans[1]->clan);
        sort($current_clans);

        $clans = array($game->teams[0]->clan, $game->teams[1]->clan);
        sort($clans);

        $interval = $this->timeDiff($game);

        return ($game->server == $this->server
         && $clans == $current_clans
         && $teamsize == $this->teamsize
         && $interval <= 10*60
         && !$this->completed);

    }
    
    public function addGame($game)
    {
        $this->games[] = $game;
        
        $tie = !isset($game->winner);
        foreach($game->teams as $n => $team)
        {
            $win = $n == 0 && !$tie;
            $id = $team->clan == $this->clans[0]->clan ? 0 : 1;
            if($win)
            {
                $this->clans[$id]->wins++;
                $this->clans[$id]->won[] = $game->gameTime; // FIXME use ID even if both are = ATM
            }
            if(isset($team->flags)) $this->clans[$id]->flags += $team->flags;
            $this->clans[$id]->frags += $team->frags;

            foreach($team->players as $player)
            {
                $user = isset($player->user) ? $player->user : '';
                $n = self::lookup_player($this->clans[$id]->players, $player->name, $user);
                if($n === false)
                    $n = self::add_player($this->clans[$id]->players, $player->name, $user);
                if(isset($player->score))
                {
                    $this->clans[$id]->players[$n]->score += $player->score;
                    $this->clans[$id]->score += $player->score;
                }
                $this->clans[$id]->players[$n]->frags += $player->frags;
                $this->clans[$id]->players[$n]->deaths += $player->deaths;
            }
        }
        $this->endTime = $game->endTime;
        $this->decideWinner();
    }
    
    public function awardTrophy(&$clan, $trophy, $nickname, $user)
    {
        $clan->Trophies->{$trophy} = new stdClass();
        if($user) $clan->Trophies->{$trophy}->user = $user;
        $clan->Trophies->{$trophy}->name = $nickname;
    }
    
    public function awardTrophies()
    {
        $mvp_points = [ -1000, -1000 ];
        foreach($this->clans as $i => &$clan)
        {
            foreach($clan->players as $player)
            {
                // MVP
                if($player->score > $mvp_points[$i])
                { 
                    $this->awardTrophy($clan, 'mvp', $player->name, isset($player->user) ? $player->user : null);
                    $mvp_points[$i] = $player->score;
                }
                
                // Flag expert
                if($player->flags >= $clan->flags)
                {
                    $this->awardTrophy($clan, 'flag_expert', $player->name, isset($player->user) ? $player->user : null);
                }
                
                // Frag expert
                if($player->frags >= 0.75 * $clan->frags)
                {
                    $this->awardTrophy($clan, 'frag_expert', $player->name, isset($player->user) ? $player->user : null);
                }
            }
        }

    }
    
    public function decideWinner()
    {
        $delta_wins = $this->clans[0]->wins - $this->clans[1]->wins;
        if($delta_wins < 0) $this->clans = array_reverse($this->clans);
        $this->winner = ($delta_wins != 0) ? $this->clans[0]->clan : null;
        //$this->completed = $this->winner && count($this->games) > 1;
        $this->completed = ($this->winner && count($this->games) > 1) || count($this->games) >= 3;
        
        if($this->completed)
        {
            $this->awardTrophies();
        }
    }
    
    public function jsonSerialize() {
        for($i = 0; $i < 2; ++$i) if(!count($this->clans[$i]->won)) unset($this->clans[$i]->won);
        return $this;
    }
}

class ClanwarsAccumulator implements ActionFPS\OrderedActionIterator
{   
    // FIXME: $state->completed = [], $state->incomplete = [], $state->unprocessed = []
    public function reduce(ActionFPS\ActionReference $reference, $state, $game)
    {
        // ignore non-clangames
        $clangame = isset($game->teams[0]->clan) && isset($game->teams[1]->clan)
            && $game->teams[0]->clan != $game->teams[1]->clan;
        
        if(!$clangame) return $state;
        
        // go through incomplete clanwars that were played recently enough
        // find out if the new game matches one of them
        for (end($state->incomplete); key($state->incomplete)!==null; prev($state->incomplete))
        {
            $id = key($state->incomplete);
            if($state->incomplete[$id]->timeDiff($game) >= 10 * 60) break;
            else if($state->incomplete[$id]->isNext($game))
            {
                $state->incomplete[$id]->addGame($game);
                if($state->incomplete[$id]->completed)
                {
                    $state->completed[$id] = $state->incomplete[$id];
                    $state->unprocessed[] = $id;
                    unset($state->incomplete[$id]);
                }
                return $state;
            }
        }
        
        // no matching war was found for this game - a new war has begun :o
        $war = new Clanwar($game);
        $state->incomplete[$war->id] = $war;
        return $state;
    }

    public function initialState()
    {
        $state = new stdClass();
        $state->incomplete = $state->completed = $state->unprocessed = [];
        return $state;
    }
}
