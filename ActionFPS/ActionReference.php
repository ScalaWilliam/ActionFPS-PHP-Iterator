<?php
namespace ActionFPS;
interface ActionReference
{
    public function getClans();

    public function getPlayers();

    public function getGame($id);

    public function getFullPlayers();

    public function getServers();

    public function getAllGames();
}
