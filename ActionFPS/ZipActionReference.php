<?php
namespace ActionFPS;

class ZipActionReference implements ActionReference
{
    protected $root;

    public function __construct($root)
    {
        $this->root = $root;
    }

    public function getClans()
    {
        return json_decode(file_get_contents("zip://{$this->root}#clans.json"));
    }

    public function getPlayers()
    {
        return json_decode(file_get_contents("zip://{$this->root}#players.json"));
    }

    public function getFullPlayers()
    {
        return json_decode(file_get_contents("zip://{$this->root}#players-full.json"));
    }

    public function getServers()
    {
        return json_decode(file_get_contents("zip://{$this->root}#servers.json"));
    }

    protected function process_lines($stream) {
        while ($line = stream_get_line($stream, 9000, "\n")) {
            list(, $json_game) = explode("\t", $line);
            $decoded = json_decode($json_game, true);
            yield $decoded;
        }
        fclose($stream);
    }

    public function getAllGames()
    {
        return $this->process_lines(fopen("zip://{$this->root}#all-games.tsv", "r"));
    }

}
