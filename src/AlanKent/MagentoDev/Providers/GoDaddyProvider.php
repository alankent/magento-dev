<?php

namespace AlanKent\MagentoDev\Providers;

use AlanKent\MagentoDev\ProviderInterface;

/**
 * Integration to copy code to/from a GoDaddy Cloud instance via ssh, run deployment scripts, etc.
 * TODO: Not written yet!
 */
class GoDaddyProvider implements ProviderInterface
{
    /**
     * Connect to a new hosting provider instance. Save anything pull-code etc needs in the configuation settings
     * @param string[] $args Command line arguments vary depending on provider.
     * @return int Exit status code.
     */
    public function connect(&$config, $args)
    {
        // TODO: Implement connect() method.
        return 0;
    }

    /**
     * Download a copy of the code from the remote host.
     * @return int Exit status code.
     */
    public function pullCode(&$config)
    {
        // TODO: Implement pullCode() method.
        return 0;
    }

    /**
     * Push a copy of the local code to the remote host.
     * @return int Exit status code.
     */
    public function pushCode(&$config)
    {
        // TODO: Implement pushCode() method.
        return 0;
    }

    /**
     * Disconnect from hosting provider.
     * @return int Exit status code.
     */
    public function disconnect(&$config)
    {
        // TODO: Implement disconnect() method.
        return 0;
    }
}