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
            $seen[] = $game['id'];
            $state = $iterator->reduce($reference, $state, $game);
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

}

