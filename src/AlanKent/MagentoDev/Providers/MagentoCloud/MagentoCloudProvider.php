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
    }

    /**
     * @inheritdoc
     */
    public function checkConnection($config)
    {
    }

    /**
     * @inheritdoc
     */
    public function pullCode($config, $environment)
    {
        echo "Use 'git pull' to pull the latest source code.\n";
    }

    /**
     * @inheritdoc
     */
    public function pushCode($config, $environment)
    {
        echo "Use 'git add', 'git commit', 'git push' etc to deploy latest source code.\n";
    }

    /**
     * @inheritdoc
     */
    public function disconnect(&$config)
    {
        echo "Use the appropriate 'magento-cloud' command when tearing down environments.\n";
    }
}