<?php

namespace AlanKent\MagentoDev;

/**
 * All hosting provider connectors implement this interface.
 */
interface ProviderInterface
{
    /**
     * Connect to a new hosting provider instance.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @param string[] $args Command line arguments vary depending on provider.
     * @return int Process exit status, where 0 = success.
     */
    public function connect(&$config, $args);

    /**
     * Download a copy of the code from the remote host.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @return int Process exit status, where 0 = success.
     */
    public function pullCode(&$config);

    /**
     * Push a copy of the local code to the remote host.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @return int Process exit status, where 0 = success.
     */
    public function pushCode(&$config);

    /**
     * Disconnect from hosting provider.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @return int Process exit status, where 0 = success.
     */
    public function disconnect(&$config);
}