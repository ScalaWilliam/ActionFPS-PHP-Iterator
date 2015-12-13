<?php
namespace ActionFPS;

class Processor
{
    public function processFromScratch(ActionReference $reference, ActionIterator $iterator)
    {
        $state = $iterator->initialState();
        $seen = [];
        $games = $reference->getAllGames();
        
        foreach ($games as $game) {
            $seen[] = $game->id;
            $state = $iterator->reduce($reference, $state, $game);
        }
        return new BasicStateResult($state, $seen);
    }
    
    public function processNew(ActionReference $reference, ActionIterator $iterator, StateResult $initial_state)
    {
		$state = $initial_state->getState();
		$seen = $initial_state->getSeen();
		
        $games = $reference->getNewGames();
        foreach ($games as $game) {
            if(!in_array($game->id, $seen))
            {
                $seen[] = $game->id;
                $state->state = $iterator->reduce($reference, $state, $game);
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

