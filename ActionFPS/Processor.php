<?php
namespace ActionFPS;

class Processor
{
    public function processFromScratch(ActionReference $reference, ActionIterator $iterator, $elements)
    {
        $state = $iterator->initialState();
        $seen = [];
        
        foreach ($elements as $element) {
            $seen[] = $element->id;
            $state = $iterator->reduce($reference, $state, $element);
        }
        return new BasicStateResult($state, $seen);
    }
    
    public function processGamesFromScratch(ActionReference $reference, ActionIterator $iterator)
    {
        $games = $reference->getAllGames();
        return $this->processFromScratch($reference, $iterator, $games);
    }
    
    public function processNewGames(ActionReference $reference, ActionIterator $iterator, StateResult $initial_state)
    {
		$state = $initial_state->getState();
		$seen = $initial_state->getSeen();
		
        $games = $reference->getNewGames();
        if(!is_array($games))
        {
            file_put_contents('messages', 'no game found', FILE_APPEND);
        }
        foreach ($games as $game) {
            file_put_contents('messages', print_r($game, true), FILE_APPEND);
            if(!in_array($game->id, $seen))
            {
                $seen[] = $game->id;
                $game = json_decode(file_get_contents("http://api.actionfps.com/game/?id={$game->id}"));
                $state->state = $iterator->reduce($reference, $state, $game);
            }
        }
        return new BasicStateResult($state, $seen);
    }
    
    public function processNew(ActionReference $reference, ActionIterator $iterator, StateResult $initial_state, $elements)
    {
        $state = $initial_state->getState();
		$seen = $initial_state->getSeen();
		
        foreach ($elements as $element) {
            if(!in_array($element->id, $seen))
            {
                $seen[] = $element->id;
                $state->state = $iterator->reduce($reference, $state, $element);
            }
        }
        return new BasicStateResult($state, $seen);
    }
}


interface StateResult
{
    public function getState();

    public function getSeen();

}


class BasicStateResult implements StateResult
{
    private $state;
    private $seen;

    public function __construct($state, $seen)
    {
        $this->state = $state;
        $this->seen = $seen;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getSeen()
    {
        return $this->seen;
    }
    
    // FIXME: where to put this ?
    public function saveToFile($output_file)
    {
        return file_put_contents($output_file, serialize([ 'state' => $this->state, 'seen' => $this->seen ]));
    }
    
    public function loadFromFile($input_file)
    {
        $serialized_data = file_get_contents($input_file);
        if(!$serialized_data)
            return false;
            
        $data = unserialize($serialized_data);
        $this->state = $data['state'];
        $this->seen = $data['seen'];
        return true;
    }

}

