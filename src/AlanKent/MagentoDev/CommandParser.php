<?php

namespace AlanKent\MagentoDev;

use \AlanKent\MagentoDev\Environments\VagrantRsync\VagrantRsyncEnvironment;

/**
 * Parse the command line arguments and process the requested command.
 * Finds the write environment or provider to go and invoke.
 */
class CommandParser
{
    /**
     * @var EnvironmentInterface[]
     */
    private $environments;

    /**
     * @var ProviderInterface[]
     */
    private $providers;

    /**
     * CommandParser constructor.
     */
    public function __construct()
    {
        $this->environments = [
            'vagrant-rsync' => new VagrantRsyncEnvironment()
        ];
        $this->providers = [
            //TODO: Add providers here.
        ];
    }

    /**
     * The main program. Parse command line options and process the command appropriately.
     * @param string[] $args Script command line arguments.
     * @return int Exit status code, 0 = success. Returning the exit code rather than calling exit($status)
     * makes testing easier.
     */
    public function main($args)
    {
        // TODO: Consider using Symfony parser for better command line parsing and error message etc.

        if (count($args) < 2) {

            echo "Insufficient arguments provided to command.\n";
            $this->usage();
            $exitCode = 1;

        } else {

            switch ($args[1]) {

                case 'create': {
                    $exitCode = $this->createCommand($args);
                    break;
                }

                case 'destroy': {
                    $exitCode = $this->destroyCommand($args);
                    break;
                }

                default: {
                    echo "Unknown command '$args[1]'\n";
                    $this->usage();
                    $exitCode = 1;
                    break;
                }
            }
        }

        return $exitCode;
    }

    /**
     * Print the usage message to the screen.
     */
    private function usage()
    {
        echo "Usage: magento-dev <command> where 'command' is one of:\n\n";
        echo "  create <environment> ...  Create new development environment.\n";
        echo "  destroy [--force]         Destroy all files created for the environment.\n";
        echo "  connect <provider> ...    Connect to a production host.\n";
        echo "  pull-code                 Pull a copy of the code from the current provider.\n";
        echo "  push-code                 Push the code and run deployment actions.\n";
        echo "  disconnect                Forget about the last environment connected to.\n";
        echo "\n";
    }

    /**
     * Parse and invoke a 'create' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @return int Exit status code (0 = success).
     */
    private function createCommand($args)
    {
        if (count($args) < 3) {
            echo "Missing environment name for 'create' command.\n";
            $this->usage();
            return 1;
        }
        $envName = $args[2];
        $environment = $this->findEnvironment($envName);
        if ($environment == null) {
            echo "Unknown environment name '$envName'.\n";
            echo "Supported environment names are:\n\n";
            foreach ($this->environments as $envName => $env) {
                echo "    $envName\n";
            }
            echo "\n";
            return 1;
        }
        $config = Config::load();
        $config['environment'] = $envName;
        $exitStatus = $environment->create($config, array_splice($args, 3));
        if ($exitStatus == 0) {
            Config::save($config);
            echo "Environment '$envName' has been created.\n";
        }
        return $exitStatus;
    }

    /**
     * Parse and invoke a 'destroy' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @return int Exit status code (0 = success).
     */
    private function destroyCommand($args)
    {
        // TODO: Should do propper command line argument parsing!
        $force = false;
        if (count($args) == 3 && $args[2] == '--force') {
            $force = true;
        } elseif (count($args) != 2) {
            $this->usage();
            return 1;
        }

        $config = Config::load();
        if (!isset($config['environment'])) {
            echo "No environment currently exists, so cannot 'destroy' environment.\n";
            return $force ? 0 : 1;
        }

        $envName = $config['environment'];
        $environment = $this->findEnvironment($envName);
        if ($environment == null) {
            $filename = Config::CONFIG_FILE_NAME;
            echo "The '$filename' file contains a reference to unknown environment '$envName'.\n";
            return $force ? 0 : 1;
        }

        $exitCode = $environment->destroy($config, $force);
        if ($exitCode == 0) {
            unset($config['environment']);
            Config::save($config);
            echo "Environment '$envName' has been destroyed.\n";
        }
        return $exitCode;
    }

    /**
     * The the environment handle for the specified environment name.
     * @param string $envName The environment handler name.
     * @return EnvironmentInterface|null The environment, or null if not found.
     */
    private function findEnvironment($envName)
    {
        if (!isset($this->environments[$envName])) {
            return null;
        }
        return $this->environments[$envName];
    }
}
