<?php
namespace ActionFPS;

class ActualActionReference implements ActionReference
{
    protected $root;

    public function __construct($root = 'http://api.actionfps.com')
    {
        $this->root = $root;
    }

    public function getClans()
    {
        return json_decode(file_get_contents("{$this->root}/clans/"));
    }

    public function getPlayers()
    {
        return json_decode(file_get_contents("{$this->root}/users/"));
    }

    public function getFullPlayers()
    {
        return json_decode(file_get_contents("{$this->root}/users/full/"));
    }

    public function getServers()
    {
        return json_decode(file_get_contents("{$this->root}/servers/"));
    }

    protected function process_lines($stream) {
        while ($line = stream_get_line($stream, 9000, "\n")) {
            list(, $json_game) = explode("\t", $line);
            $decoded = json_decode($json_game);
            yield $decoded;
        }
        fclose($stream);
    }

    public function getAllGames()
    {
        return $this->process_lines(fopen("{$this->root}/all/", "r"));
    }

    public function getGame($id)
    {
        return json_decode(file_get_contents("{$this->root}/game/".rawurlencode($id)));
    }
}
