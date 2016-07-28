<?php

use AlanKent\MagentoDev\CommandParser;

include __DIR__ . '/' . 'autoloader.php';

$playing = false;
if ($playing) {
    // TODO: Convert into unit tests - but useful for now.
    echo "===========================\n";
    (new CommandParser())->main(['main.php', 'connect', 'godaddy-cloud',
        '--ssh-user', 'admin', '--ssh-host', '107.180.107.179']);
    echo "===========================\n";
    (new CommandParser())->main(['main.php', 'destroy', '--force']);
    echo "===========================\n";
    (new CommandParser())->main(['main.php', 'create', 'vagrant-rsync']);
    echo "===========================\n";
    (new CommandParser())->main(['main.php', 'destroy']);
    echo "===========================\n";
    (new CommandParser())->main(['main.php', 'destroy']);
    echo "===========================\n";
    exit(0);
}

global $argv;
$parser = new CommandParser();
exit($parser->main($argv));

