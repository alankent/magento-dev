<?php

namespace AlanKent\MagentoDev;

use AlanKent\MagentoDev\Environments\VagrantRsync\VagrantRsyncEnvironment;
use AlanKent\MagentoDev\Providers\MagentoCloud\MagentoCloudProvider;

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
            'magento-cloud' => new MagentoCloudProvider()
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

                case 'connect': {
                    $exitCode = $this->connectCommand($args);
                    break;
                }

                case 'pull-code': {
                    $exitCode = $this->pullCodeCommand($args);
                    break;
                }

                case 'push-code': {
                    $exitCode = $this->pushCodeCommand($args);
                    break;
                }

                case 'disconnect': {
                    $exitCode = $this->disconnectCommand($args);
                    break;
                }

                case 'help': {
                    $exitCode = $this->helpCommand($args);
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
        echo "  help                      Display help information.\n";
        echo "\n";
    }

    /**
     * Parse and invoke a 'create' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @return int Exit status code (0 = success).
     */
    private function createCommand($args)
    {
        $config = Config::load();
        if (isset($config['environment'])) {
            echo "You have already created an '${config['environment']}' environment.\n";
            return 1;
        }
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
        // TODO: Should do proper command line argument parsing!
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
     * Parse and invoke a 'connect' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @return int Exit status code (0 = success).
     */
    private function connectCommand($args)
    {
        $config = Config::load();
        if (isset($config['provider'])) {
            echo "You have already connected to '${config['provider']}'.\n";
            return 1;
        }
        if (count($args) < 3) {
            echo "Missing provider name for 'connect' command.\n";
            $this->usage();
            return 1;
        }
        $providerName = $args[2];
        $provider = $this->findProvider($providerName);
        if ($provider == null) {
            echo "Unknown environment name '$providerName'.\n";
            echo "Supported environment names are:\n\n";
            foreach ($this->providers as $providerName => $provider) {
                echo "    $providerName\n";
            }
            echo "\n";
            return 1;
        }
        $config['provider'] = $providerName;
        $exitStatus = $provider->connect($config, array_splice($args, 3));
        if ($exitStatus == 0) {
            Config::save($config);
            echo "Provider '$providerName' has been connected to.\n";
        }
        return $exitStatus;
    }

    /**
     * Parse and invoke a 'pull-code' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @return int Exit status code (0 = success).
     */
    private function pullCodeCommand($args)
    {
        if (count($args) != 2) {
            echo "Unexpected additional arguments for 'pull-code' command.\n";
            $this->usage();
            return 1;
        }
        $config = Config::load();
        if (!isset($config['provider'])) {
            echo "Please use 'connect' to specify the provider name first.\n";
            return 1;
        }
        $providerName = $config['provider'];
        $provider = $this->findProvider($providerName);
        if ($provider == null) {
            echo "Unknown provider name '$providerName'.\n";
            echo "Supported provider names are:\n\n";
            foreach ($this->providers as $providerName => $provider) {
                echo "    $providerName\n";
            }
            echo "\n";
            return 1;
        }
        $exitStatus = $provider->pullCode($config);
        if ($exitStatus == 0) {
            Config::save($config);
        }
        return $exitStatus;
    }

    /**
     * Parse and invoke a 'disconnect' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @return int Exit status code (0 = success).
     */
    private function disconnectCommand($args)
    {
        if (count($args) != 2) {
            echo "Unexpected additional arguments for 'disconnect' command.\n";
            $this->usage();
            return 1;
        }

        $config = Config::load();
        if (!isset($config['provider'])) {
            echo "Not connected to a provider.\n";
            return 0;
        }

        $providerName = $config['provider'];
        $provider = $this->findProvider($providerName);
        if ($provider == null) {
            $filename = Config::CONFIG_FILE_NAME;
            echo "The '$filename' file contains a reference to unknown provider '$providerName'.\n";
            return 1;
        }

        $exitCode = $provider->disconnect($config);
        if ($exitCode == 0) {
            unset($config['provider']);
            Config::save($config);
            echo "Disconnected from provider '$providerName'.\n";
        }
        return $exitCode;
    }

    /**
     * Parse and invoke a 'push-code' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @return int Exit status code (0 = success).
     */
    private function pushCodeCommand($args)
    {
        if (count($args) != 2) {
            echo "Unexpected additional arguments for 'push-code' command.\n";
            $this->usage();
            return 1;
        }
        $config = Config::load();
        if (!isset($config['provider'])) {
            echo "Please use 'connect' to specify the provider name first.\n";
            return 1;
        }
        $providerName = $config['provider'];
        $provider = $this->findProvider($providerName);
        if ($provider == null) {
            echo "Unknown provider name '$providerName'.\n";
            echo "Supported provider names are:\n\n";
            foreach ($this->providers as $providerName => $provider) {
                echo "    $providerName\n";
            }
            echo "\n";
            return 1;
        }
        $exitStatus = $provider->pushCode($config);
        if ($exitStatus == 0) {
            Config::save($config);
        }
        return $exitStatus;
    }

    /**
     * Parse and invoke a 'help' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @return int Exit status code (0 = success).
     */
    private function helpCommand(/** @noinspection PhpUnusedParameterInspection */ $args)
    {
        $this->usage();
        echo "Environments: " . implode(", ", array_keys($this->environments)) . "\n";
        echo "Providers: " . implode(", ", array_keys($this->providers)) . "\n";
        return 0;
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

    /**
     * The the provider handle for the specified provider name.
     * @param string $providerName The provider handler name.
     * @return ProviderInterface|null The provider, or null if not found.
     */
    private function findProvider($providerName)
    {
        if (!isset($this->providers[$providerName])) {
            return null;
        }
        return $this->providers[$providerName];
    }
}
