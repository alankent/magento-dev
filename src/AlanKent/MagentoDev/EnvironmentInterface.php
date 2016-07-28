<?php

namespace AlanKent\MagentoDev;

/**
 * An environment is a set of tools for local development. Vagrant is an example technology
 * that can be used to build an environment upon. There may be several environments defined
 * using Vagrant, such as "vagrant-rsync" (use the "rsync" mode of Vagrant) and "vagrant-nfs"
 * (use Vagrant, but with NFS mounted volumes instead of using rsync).
 */
interface EnvironmentInterface
{
    /**
     * Create an environment, parsing any configuration line arguments local to environment.
     * @param array $config Configuration file settings.
     * @param array $args Command line arguments for this command to parse (with previous command line arguments stripped).
     * @throws MdException Thrown on error to report to user.
     */
    public function create(&$config, $args);

    /**
     * Tear down the environment and remove all files created.
     * @param array $config Configuration file settings.
     * @param bool $force Set to true if files should be deleted even if modified. If set to true, warnings should
     * not return a non-zero status code.
     * @throws MdException Thrown on error to report to user.
     */
    public function destroy(&$config, $force);

    /**
     * Synchronize the local files into the environment. If the file system is shared, this is a no-operation.
     * @param array $config Configuration file settings.
     * @throws MdException Thrown on error to report to user.
     */
    public function syncToEnvironment($config);

    /**
     * Run the given command inside the environment.
     * @param array $config Configuration settings to connect to environment.
     * @param string $cmd The command to run inside the environment.
     * @throws MdException Thrown on error to report to user.
     */
    public function runCommand($config, $cmd);
}