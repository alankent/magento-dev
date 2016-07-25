<?php
/**
 * Created by PhpStorm.
 * User: akent
 * Date: 7/25/2016
 * Time: 10:38 AM
 */

namespace AlanKent\MagentoDev\Providers\MagentoCloud;

use AlanKent\MagentoDev\ProviderInterface;

/**
 * A sample provider for Magento Cloud. Magento Cloud provides its own CLI and integrates natively
 * with 'git' for source code management, so this provider guides you to the appropriate commands
 * to run rather than running them for you directly.
 */
class MagentoCloudProvider implements ProviderInterface
{
    /**
     * @inheritdoc
     */
    public function connect(&$config, $args)
    {
        echo "Please refer to the Magento Cloud documentation for installing\n";
        echo "and using the 'magento-cloud' CLI.\n";
        return 0;
    }

    /**
     * @inheritdoc
     */
    public function pullCode(&$config)
    {
        echo "Use 'git pull' to pull the latest source code.\n";
        return 0;
    }

    /**
     * Push a copy of the local code to the remote host.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @return int Process exit status, where 0 = success.
     */
    public function pushCode(&$config)
    {
        echo "Use 'git add', 'git commit', 'git push' etc to deploy latest source code.\n";
        return 0;
    }

    /**
     * Disconnect from hosting provider.
     * @param array $config Configuration settings that can be updated to save connection details into.
     * @return int Process exit status, where 0 = success.
     */
    public function disconnect(&$config)
    {
        echo "Use the appropriate 'magento-cloud' command when tearing down environments.\n";
        return 0;
    }
}