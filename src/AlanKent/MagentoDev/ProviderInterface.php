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
     * @throws MdException Thrown on error.
     */
    public function connect(&$config, $args);

    /**
     * Check that the saved configuration works, throwing an exception if not.
     * E.g. try to ssh to the remote production server.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @throws MdException Thrown on error.
     */
    public function checkConnection($config);

    /**
     * Download a copy of the code from the remote host.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @param EnvironmentInterface $environment The currently configured environment, or null if not set.
     * @throws MdException Thrown on error.
     */
    public function pullCode($config, $environment);

    /**
     * Push a copy of the local code to the remote host.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @param EnvironmentInterface $environment The currently configured environment, or null if not set.
     * @throws MdException Thrown on error.
     */
    public function pushCode($config, $environment);

    /**
     * Disconnect from hosting provider.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @throws MdException Thrown on error.
     */
    public function disconnect(&$config);
}