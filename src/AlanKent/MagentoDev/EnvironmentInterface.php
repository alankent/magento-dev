<?php

namespace AlanKent\MagentoDev;


interface EnvironmentInterface
{
    /**
     * Create an environment, parsing any configuration line arguments local to environment.
     * @param array $config Configuration file settings.
     * @param array $args Command line arguments for this command to parse (with previous command line arguments stripped).
     * @return int Process exit status code to be returned (0 = success).
     */
    public function create(&$config, $args);

    /**
     * Tear down the environment and remove all files created.
     * @param array $config Configuration file settings.
     * @param bool $force Set to true if files should be deleted even if modified. If set to true, warnings should
     * not return a non-zero status code.
     * @return int Process exit status code (0 = success).
     */
    public function destroy(&$config, $force);
}