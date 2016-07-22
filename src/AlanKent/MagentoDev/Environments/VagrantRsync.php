<?php

namespace AlanKent\MagentoDev\Environments;

use AlanKent\MagentoDev\EnvironmentInterface;

/**
 * Creates/destroys an environment using 'vagrant rsync-auto' for Magento 2.
 */
class VagrantRsync implements EnvironmentInterface
{
    const SOURCE_DIRNAME = __DIR__.'/VagrantRsync';

    const VAGRANTFILE_FILENAME = 'Vagrantfile';

    const SCRIPTS_DIRNAME = 'scripts';

    const SCRIPTS = [
        'install-gulp', 'install-gulp-run-gulp.sh',
        'install-magento', 'install-magento-apache2.conf', 'install-magento-magento.conf',
        'install-nodejs'
    ];

    /**
     * Create the set of files for the Vagrant based environment.
     * @param array $config
     * @param array $args Currently no command line options are supported.
     * @return int Exit status code.
     */
    public function create(&$config, $args)
    {
        if (count($args) != 0) {
            echo "Unexpected additional arguments were found.\n";
            return 1;
        }

        if (file_exists(self::VAGRANTFILE_FILENAME)) {
            echo "Please remove the existing '".self::VAGRANTFILE_FILENAME."' before running this command.\n";
            echo "Or use the 'destroy' command to tear down the current environment.\n";
            return 1;
        }

        file_put_contents(self::VAGRANTFILE_FILENAME, file_get_contents(self::SOURCE_DIRNAME.'/'.self::VAGRANTFILE_FILENAME));
        echo "Created '".self::VAGRANTFILE_FILENAME."'.\n";

        if (!file_exists(self::SCRIPTS_DIRNAME)) {
            mkdir(self::SCRIPTS_DIRNAME);
        }
        if (is_file(self::SCRIPTS_DIRNAME)) {
            echo "Tried to create a directory called '".self::SCRIPTS_DIRNAME."' but a file with that name already exists.\n";
            return 1;
        }
        echo "Created directory '".self::SCRIPTS_DIRNAME."'.\n";

        foreach (self::SCRIPTS as $scriptName) {
            file_put_contents(self::SCRIPTS_DIRNAME.'/'.$scriptName, file_get_contents(self::SOURCE_DIRNAME.'/'.$scriptName));
            echo "Created '".self::SCRIPTS_DIRNAME.'/'.$scriptName."'.\n";
        }

        echo "\n";
        echo "Review the Vagrantfile configuration settings before using this box (e.g.\n";
        echo "network settings). Then run\n\n";
        echo "    vagrant up           Starts and initializes the Vagrant box.\n";
        echo "    vagrant rsync-auto   Watches for local filesystem changes and copies them\n";
        echo "                         into the VM.\n";
        echo "\n";

        return 0;
    }

    /**
     * Destroy the current environment.
     * @param array $config Configuration settings.
     * @param bool $force Set to true if should continue even if something strange occurs.
     * @return int Exit status code to return.
     */
    public function destroy(&$config, $force)
    {
        echo "Running 'vagrant destroy'.\n";
        $exitCode = 0;
        system('vagrant destroy', $exitCode);
        if (!$force && $exitCode != 0) {
            return $exitCode;
        }

        $exitCode = self::removeIfUnchanged(self::VAGRANTFILE_FILENAME,
                                            self::SOURCE_DIRNAME.'/'.self::VAGRANTFILE_FILENAME,
                                            $force);
        if ($exitCode != 0) {
            return $exitCode;
        }

        foreach (self::SCRIPTS as $scriptName) {
            $exitCode = self::removeIfUnchanged(self::SCRIPTS_DIRNAME.'/'.$scriptName,
                                                self::SOURCE_DIRNAME.'/'.$scriptName,
                                                $force);
            if ($exitCode != 0) {
                return $exitCode;
            }
        }

        echo "Removing directory '".self::SCRIPTS_DIRNAME."'.\n";
        rmdir(self::SCRIPTS_DIRNAME);

        return 0;
    }

    /**
     * Helper function to remove a file if there have been no local changes to the file.
     * E.g. you might have made some changes to the 'Vagrantfile' which you don't want to lose, so make the
     * user use --force if they really want to lose the file changes.
     * A problem with this approach however is a new version of the tool with new file contents will also
     * report it as a possible local change, when there is none.
     * @param string $localFilename The real filename on disk.
     * @param string $sourceFilename The name of the original file, to compare to the real file.
     * @param bool $force If true, ignore any differences.
     * @return int The return exit status, where 0 = success.
     */
    private static function removeIfUnchanged($localFilename, $sourceFilename, $force)
    {
        if (!file_exists($localFilename)) {
            return 0;
        }
        if (!$force) {
            $localContents = file_get_contents($localFilename);
            $originalContents = file_get_contents($sourceFilename);
            if ($localContents != $originalContents) {
                echo "Not removing '$localFilename' as it may contain local changes. (Use --force to override.)\n";
                return 1;
            }
        }
        echo "Removing '$localFilename'.\n";
        unlink($localFilename);
        return 0;
    }
}