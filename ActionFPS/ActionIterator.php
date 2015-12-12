<?php
namespace ActionFPS;

interface ActionIterator
{

    public function initialState();

    /**
     ** Return next state. This function should be referentially transparent.
     * @param ActionReference $reference
     * @param $state
     * @param $game
     * @return
     */
    public function reduce(ActionReference $reference, $state, $game);
}