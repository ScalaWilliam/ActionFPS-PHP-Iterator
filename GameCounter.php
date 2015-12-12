<?php
class GameCounter implements ActionFPS\UnorderedActionIterator
{
    public function reduce(ActionFPS\ActionReference $reference, $state, $game)
    {
        foreach ($game['teams'] as $team) {
            foreach ($team['players'] as $player) {
                if (isset($player['user'])) {
                    $state[$player['user']] = ($state[$player['user']] ?? 0) + 1;
                }
            }
        }
        return $state;
    }

    public function initialState()
    {
        return [];
    }
}