<?php

namespace AlanKent\MagentoDev;

use AlanKent\MagentoDev\Environments\VagrantRsync\VagrantRsyncEnvironment;
use AlanKent\MagentoDev\Providers\GoDaddy\GoDaddyProvider;
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
            'magento-cloud' => new MagentoCloudProvider(),
            'godaddy-cloud' => new GoDaddyProvider()
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


        try {
            if (count($args) < 2) {
                throw new MdException("Insufficient arguments provided to command.\n" . $this->usage(), 1);
            }

            switch ($args[1]) {

                case 'create': {
                    $this->createCommand($args);
                    break;
                }

                case 'destroy': {
                    $this->destroyCommand($args);
                    break;
                }

                case 'connect': {
                    $this->connectCommand($args);
                    break;
                }

                case 'pull-code': {
                    $this->pullCodeCommand($args);
                    break;
                }

                case 'push-code': {
                    $this->pushCodeCommand($args);
                    break;
                }

                case 'disconnect': {
                    $this->disconnectCommand($args);
                    break;
                }

                case 'help': {
                    $this->helpCommand($args);
                    break;
                }

                default: {
                    throw new MdException("Unknown command '$args[1]'\n" . $this->usage(), 1);
                }
            }
        } catch (MdException $e) {
            echo $e->getMessage();
            return $e->getCode();
        }

        return 0;
    }

    /**
     * Return the usage message for display on the screen.
     * @return string The usage message.
     */
    private function usage()
    {
        $msg = "Usage: magento-dev <command> where 'command' is one of:\n\n";
        $msg .= "  create <environment> ...  Create new development environment.\n";
        $msg .= "  destroy [--force]         Destroy all files created for the environment.\n";
        $msg .= "  connect <provider> ...    Connect to a production host.\n";
        $msg .= "  pull-code                 Pull a copy of the code from the current provider.\n";
        $msg .= "  push-code                 Push the code and run deployment actions.\n";
        $msg .= "  disconnect                Forget about the last environment connected to.\n";
        $msg .= "  help                      Display help information.\n";
        $msg .= "\n";
        $msg .= "Environments: " . implode(", ", array_keys($this->environments)) . "\n";
        $msg .= "Providers: " . implode(", ", array_keys($this->providers)) . "\n";
        return $msg;
    }

    /**
     * Parse and invoke a 'create' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @throws MdException Thrown on error.
     */
    private function createCommand($args)
    {
        $config = Config::load();
        if (isset($config['environment'])) {
            throw new MdException("You have already created an '${config['environment']}' environment.\n", 1);
        }
        if (count($args) < 3) {
            throw new MdException("Missing environment name for 'create' command.\n" . $this->usage(), 1);
        }
        $envName = $args[2];
        $environment = $this->findEnvironment($envName);
        if ($environment == null) {
            $msg = "Unknown environment name '$envName'.\n";
            $msg .= "Supported environment names are:\n\n";
            foreach ($this->environments as $envName => $env) {
                $msg .= "    $envName\n";
            }
            $msg .= "\n";
            throw new MdException($msg, 1);
        }
        $config['environment'] = $envName;
        $environment->create($config, array_splice($args, 3));
        Config::save($config);
        echo "Environment '$envName' has been created.\n";
    }

    /**
     * Parse and invoke a 'destroy' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @throws MdException Thrown on error.
     */
    private function destroyCommand($args)
    {
        // TODO: Should do proper command line argument parsing!
        $force = false;
        if (count($args) == 3 && $args[2] == '--force') {
            $force = true;
        } elseif (count($args) != 2) {
            throw new MdException($this->usage(), 1);
        }

        $config = Config::load();
        if (!isset($config['environment'])) {
            throw new MdException("No environment currently exists, so cannot 'destroy' environment.\n", $force ? 0 : 1);
        }

        $envName = $config['environment'];
        $environment = $this->findEnvironment($envName);
        if ($environment == null) {
            $filename = Config::CONFIG_FILE_NAME;
            throw new MdException("The '$filename' file contains a reference to unknown environment '$envName'.\n", $force ? 0 : 1);
        }

        $environment->destroy($config, $force);
        unset($config['environment']);
        Config::save($config);
        echo "Environment '$envName' has been destroyed.\n";
    }

    /**
     * Parse and invoke a 'connect' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @throws MdException Thrown on error.
     */
    private function connectCommand($args)
    {
        $config = Config::load();
        if (isset($config['provider'])) {
            throw new MdException("You have already connected to '${config['provider']}'.\n", 1);
        }
        if (count($args) < 3) {
            throw new MdException("Missing provider name for 'connect' command.\n" . $this->usage(), 1);
        }
        $providerName = $args[2];
        $provider = $this->findProvider($providerName);
        if ($provider == null) {
            $msg = "Unknown provider name '$providerName'.\n";
            $msg .= "Supported provider names are:\n\n";
            foreach ($this->providers as $providerName => $provider) {
                $msg .= "    $providerName\n";
            }
            $msg .= "\n";
            throw new MdException($msg, 1);
        }
        $oldConfig = $config;
        $config['provider'] = $providerName;
        $provider->connect($config, array_splice($args, 3));
        Config::save($config);
        try {
            $provider->checkConnection($config);
        } catch (MdException $e) {
            Config::save($oldConfig);
            throw $e;
        }
        echo "Provider '$providerName' has been connected to.\n";
    }

    /**
     * Parse and invoke a 'pull-code' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @throws MdException Thrown on error.
     */
    private function pullCodeCommand($args)
    {
        if (count($args) != 2) {
            throw new MdException("Unexpected additional arguments for 'pull-code' command.\n" . $this->usage(), 1);
        }
        $config = Config::load();
        if (!isset($config['provider'])) {
            throw new MdException("Please use 'connect' to specify the provider name first.\n", 1);
        }
        $providerName = $config['provider'];
        $provider = $this->findProvider($providerName);
        if ($provider == null) {
            $msg = "Unknown provider name '$providerName'.\n";
            $msg .= "Supported provider names are:\n\n";
            foreach ($this->providers as $providerName => $provider) {
                $msg .= "    $providerName\n";
            }
            $msg .= "\n";
            throw new MdException($msg, 1);
        }

        $environment = null;
        if (isset($config['environment'])) {
            $environment = $this->findEnvironment($config['environment']);
        }

        $provider->pullCode($config, $environment);
        Config::save($config);
    }

    /**
     * Parse and invoke a 'disconnect' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @throws MdException Thrown on error.
     */
    private function disconnectCommand($args)
    {
        if (count($args) != 2) {
            throw new MdException("Unexpected additional arguments for 'disconnect' command.\n" . $this->usage(), 1);
        }

        $config = Config::load();
        if (!isset($config['provider'])) {
            echo "Not connected to a provider.\n";
            return;
        }

        $providerName = $config['provider'];
        $provider = $this->findProvider($providerName);
        if ($provider == null) {
            $filename = Config::CONFIG_FILE_NAME;
            throw new MdException("The '$filename' file contains a reference to unknown provider '$providerName'.\n", 1);
        }

        $provider->disconnect($config);
        unset($config['provider']);
        Config::save($config);
        echo "Disconnected from provider '$providerName'.\n";
    }

    /**
     * Parse and invoke a 'push-code' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @throws MdException Thrown on error.
     */
    private function pushCodeCommand($args)
    {
        if (count($args) != 2) {
            throw new MdException("Unexpected additional arguments for 'push-code' command.\n" . $this->usage(), 1);
        }
        $config = Config::load();
        if (!isset($config['provider'])) {
            throw new MdException("Please use 'connect' to specify the provider name first.\n", 1);
        }
        $providerName = $config['provider'];
        $provider = $this->findProvider($providerName);
        if ($provider == null) {
            $msg = "Unknown provider name '$providerName'.\n";
            $msg .= "Supported provider names are:\n\n";
            foreach ($this->providers as $providerName => $provider) {
                $msg .= "    $providerName\n";
            }
            $msg .= "\n";
            throw new MdException($msg, 1);
        }

        $environment = null;
        if (isset($config['environment'])) {
            $environment = $this->findEnvironment($config['environment']);
        }

        $provider->pushCode($config, $environment);
        Config::save($config);
    }

    /**
     * Parse and invoke a 'help' command.
     * @param string[] $args Optional additional command line arguments to parse.
     * @throws MdException Thrown on error.
     */
    private function helpCommand(/** @noinspection PhpUnusedParameterInspection */ $args)
    {
        echo $this->usage();
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
