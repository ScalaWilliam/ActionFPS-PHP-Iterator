<?php
namespace ActionFPS;

class GamesCachedActionReference extends ActualActionReference {
    function __construct() {
        parent::__construct();
    }
    function getAllGames()
    {
        if ( !file_exists("all.tsv") ) {
            file_put_contents("all.tsv", file_get_contents("{$this->root}/all/"));
        }
        return $this->process_lines(fopen("all.tsv", "r"));
    }
    
    function getNewGames()
    {
        
    }
}