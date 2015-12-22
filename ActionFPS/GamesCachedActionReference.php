<?php
namespace ActionFPS;

class GamesCachedActionReference extends ActualActionReference {
    function __construct() {
        parent::__construct();
    }
    
    function getClans()
    {
        if( !file_exists("clans.json"))
        {
            file_put_contents("clans.json", file_get_contents("{$this->root}/clans/"));
        }
        return json_decode(file_get_contents("clans.json"));
    }
    
    function getAllGames()
    {
        if ( !file_exists("all.tsv") ) {
            file_put_contents("all.tsv", file_get_contents("{$this->root}/all/"));
        }
        return $this->process_lines(fopen("all.tsv", "r"));
    }
    
    function getAllClanwars()
    {
        
    }
    
    function getNewGames()
    {
        $fp = fopen("php://stdin", "r");
        stream_set_blocking($fp, false);
        $lines = [ json_decode(stream_get_contents($fp)) ];
        fclose($stream);
        return $lines;
    }
}
