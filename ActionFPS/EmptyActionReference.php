<?php
namespace ActionFPS;

class EmptyActionReference implements ActionReference
{

    public function getClans()
    {
        return [];
    }

    public function getPlayers()
    {
        return [];
    }

    public function getFullPlayers()
    {
        return [];
    }

    public function getServers()
    {
        return [];
    }

    public function getAllGames()
    {
        return [];
    }
}