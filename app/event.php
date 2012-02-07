<?php
/**
 * Events
 */

return array(
    'init' => array(
        function(){var_dump('before init');}
    ),
    'shutdown' => array(
        function(){var_dump('shutdown0');},
        function(){var_dump('shutdown1');},
        function(){var_dump('shutdown2');},
    ),
);