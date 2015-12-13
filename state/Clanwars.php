<?php
class Clanwar implements JsonSerializable
{
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
        $this->startTime = $game->gameTime;
        $this->clans = array(new stdClass(), new stdClass());
        for($i = 0; $i < 2; ++$i)
        {
            $this->clans[$i]->clan = '';
            $this->clans[$i]->wins = 0;
            $this->clans[$i]->won = [];
            $this->clans[$i]->score = $this->clans[$i]->flags = $this->clans[$i]->frags = 0;
            $this->clans[$i]->players = [];
            $this->clans[$i]->mvp = new stdClass(); // ugh

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

        $clans = array($game->teams[0]->clan, $game->teams[1]->clan);
        sort($clans);

        $interval = $this->timeDiff($game);

        return ($game->server == $this->server
         && $clans == $this->clans
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
            $id = $team->clan == $this->clans[0] ? 0 : 1;
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
        $game_start = new DateTime($game->gameTime);
        $this->endTime = date('Y-m-d\TH:i:s', $game_start->getTimestamp() + 60 * $game->duration); // FIXME
        $this->decideWinner();
    }
    
    public function awardTrophees()
    {
        $mvp_points = [ -1000, 1000 ];
        foreach($this->clans as $i => &$clan)
        {
            foreach($clan->players as $player)
            {
				// MVP
                if($player->score > $mvp_points[$i])
                {
                    $clan->mvp->name = $player->name;
                    if(isset($player->user)) $clan->mvp->user = $player->user; 
                    $mvp_points[$i] = $player->score;
                }
                
                // Flag expert
                if($player->flags >= $clan->flags)
                {
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
			$this->awardTrophees();
		}
    }
    
    public function jsonSerialize() {
        for($i = 0; $i < 2; ++$i) if(!count($this->clans[$i]->won)) unset($this->clans[$i]->won);
        return $this;
    }
}

class ClanwarsAccumulator implements ActionFPS\OrderedActionIterator
{   
	// FIXME: $state->completed = [], $state->incomplete = []
    public function reduce(ActionFPS\ActionReference $reference, $state, $game)
    {
        $clangame = isset($game->teams[0]->clan) && isset($game->teams[1]->clan)
            && $game->teams[0]->clan != $game->teams[1]->clan;
        
        if(!$clangame) return $state;
        
        for($i = count($state)-1; $i >= 0; --$i)
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
