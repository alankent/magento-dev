<?php

namespace AlanKent\MagentoDev\Providers\GoDaddy;

use AlanKent\MagentoDev\MdException;
use AlanKent\MagentoDev\ProviderInterface;
use AlanKent\MagentoDev\Config;

/**
 * Integration to copy code to/from a GoDaddy Cloud instance via ssh, run deployment scripts, etc.
 */
class GoDaddyProvider implements ProviderInterface
{
    const HTDOCS_PATH = '/opt/bitnami/apps/magento/htdocs';
    const IDENTITY_FILE = 'id_godaddy';
    const EXCLUDE_LIST = [
        'Vagrantfile',     # Vagrant control file
        '.vagrant/',       # Vagrant work area
        '.git/',           # Git repository
        '.gitignore',      # Git support file
        '.gitattributes',  # Git support file
        'var/',            # Temporary files used by Magento
        'pub/media/',      # Don't wipe uploaded media files pub/media
        'pub/static/',     # Don't wipe generated assets under pub/static
        'scripts/',        # Support shell scripts
        'vendor/',         # Compose download area
        '.idea/',          # PHP Storm project files
        'app/etc/env.php', # Don't want to overwrite DB settings
        '.magento'         # Used by Magento Cloud
    ];


    /**
     * Connect to a new hosting provider instance. Save anything pull-code etc needs in the configuation settings
     * @param array $config Configuration settings to use to hold connection details (such as host name).
     * @param string[] $args Command line arguments vary depending on provider.
     * @throws MdException Thrown on error.
     */
    public function connect(&$config, $args)
    {
        echo "==== Connecting using godaddy-cloud provider.\n";

        $sshUser = "";
        $sshHost = "";
        $sshPort = "";
        $sshIdentity = "";

        while (count($args) > 0) {
            if (count($args) < 2) {
                throw new MdException($this->goDaddyConnectUsage(), 1);
            }
            switch ($args[0]) {
                case '--ssh-user': {
                    $sshUser = $args[1];
                    break;
                }
                case '--ssh-host': {
                    $sshHost = $args[1];
                    break;
                }
                case '--ssh-port': {
                    $sshPort = $args[1];
                    break;
                }
                case '--ssh-identity': {
                    $sshIdentity = $args[1];
                    break;
                }
                default: {
                    throw new MdException("Unknown option '$args[0]'.\n" . $this->goDaddyConnectUsage(), 1);
                }
            }
            $args = array_splice($args, 2);
        }

        if ($sshHost == "" || $sshUser == "") {
            throw new MdException($this->goDaddyConnectUsage(), 1);
        }

        // Save away configuration settings (saved to disk by calling code)
        $sshConfig = [];
        if ($sshHost != '') {
            $sshConfig['host'] = $sshHost;
        }
        if ($sshPort != '') {
            $sshConfig['port'] = $sshPort;
        }
        if ($sshUser != '') {
            $sshConfig['user'] = $sshUser;
        }
        if ($sshIdentity != '') {
            $sshConfig['identity'] = $sshIdentity;
        }
        $config['godaddy'] = ['ssh'=>$sshConfig];

        if ($sshIdentity == "") {
            $sshIdentity = "~/.ssh/" . self::IDENTITY_FILE;
        }
        if (!file_exists($this->expandPath($sshIdentity))) {

            // TODO: Could consider performing these files automatically.
            $msg = "The SSH identify file '$sshIdentity' does not exist.\n";
            $msg .= "Use the --ssh-identity option to the connect command to specify a different file,\n";
            $msg .= "or create a new file using a command such as (inserting your real email address):\n\n";
            $msg .= "    ssh-keygen -t rsa -C your.email@example.com -N \"\" -f $sshIdentity\n\n";
            $msg .= "To copy the public key to your production server, use a command such as\n\n";
            $msg .= "    cat $sshIdentity.pub | ssh -oStrictHostKeyChecking=no -i $sshIdentity $sshUser@$sshHost \"mkdir -p ~/.ssh; cat >> ~/.ssh/authorized_keys\"\n\n";
            $msg .= "You will be prompted for your password if the key has not been uploaded before.\n";
            $msg .= "You can test that it works (you are not prompted for a password) using\n\n";
            $msg .= "    ssh -i $sshIdentity $sshUser@$sshHost echo Working\n\n";

            throw new MdException($msg, 1);
        }
    }

    /**
     * Return usage message with all the command line options.
     * @return string
     */
    private function goDaddyConnectUsage()
    {
        $idFile = self::IDENTITY_FILE;
        $msg = "GoDaddy Cloud provider connection command line options:\n\n";
        $msg .= "  --ssh-user      - username to connect with on production host\n";
        $msg .= "  --ssh-host      - hostname or IP address of production host\n";
        $msg .= "  --ssh-port      - SSH port number to use if not 22 (optional)\n";
        $msg .= "  --ssh-identity  - SSH identity file if not ~/.ssh/$idFile (optional)\n";
        $msg .= "\n";
        $msg .= "You must set at least --ssh-user and --ssh-host.\n";
        return $msg;
    }

    /**
     * Expand "~" at front of path for PHP file operations, allowing "~" to be kept when
     * passing to "ssh" etc commands to make the path more portable. E.g. cygwin does not
     * like C:\Users\foo - it wants /cygdrive/c/home/foo. But other shells use /c/home/foo.
     * So using "~" is good when possible.
     * @param $path
     * @return string The expanded path, suitable for passing to PHP functions.
     * @throws MdException
     */
    private function expandPath($path)
    {
        if ($path[0] == '~') {
            $home = $this->getHomeDir();
            if ($home == "") {
                throw new MdException("Unable to determine user's home directory, aborting.\n", 1);
            }
            return $home . "/" . substr($path, 1);
        } else {
            return $path;
        }
    }

    /**
     * Return the user's home directory. (Borrowed from Drush.)
     */
    private function getHomeDir()
    {
        // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
        // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');
        if (!empty($home)) {
            // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        }
        elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
        }
        return empty($home) ? NULL : $home;
    }

    /**
     * @inheritdoc
     */
    public function pullCode($config, $environment)
    {
        $this->checkConnection($config);

        echo "==== Make sure 'magento' CLI is in PATH.\n";
        $htdocs = self::HTDOCS_PATH;
        $output = $this->runOnProd($config, "cat ~/.bashrc");
        if (strpos(implode($output), "magento") === false) {
            $this->runOnProd($config, "echo export PATH=\${PATH}:$htdocs/bin >> ~/.bashrc");
            $this->runOnProd($config, "echo umask 002 >> ~/.bashrc");
        }

        echo "==== Fetching code from production.\n";
        // Note: The trailing "/" on "./" is important for correct operation.
        $this->copyRemoteToLocal($config, "/opt/bitnami/apps/magento/htdocs/", "./", self::EXCLUDE_LIST);
        foreach (self::EXCLUDE_LIST as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0777, true);
            }
        }

        echo "==== Downloading any next patches or extensions.\n";
        // Tell composer not to override local changes.
        // Eventually this step will not be required.
        echo "Adding Magento deployment strategy to composer.json\n";
        $oldComposerJson = file_get_contents('composer.json');
        // This could parse the file as JSON, but this works.
        $newComposerJson = str_replace('extra": {', 'extra": { "magento-deploystrategy": "none",', $oldComposerJson);
        file_put_contents('composer.json', $newComposerJson);
        echo "> sh -c 'composer update'\n";
        system("sh -c 'composer update'");
        echo "Restoring composer.json file\n";
        file_put_contents('composer.json', $oldComposerJson);
        echo "> sh -c 'cd update; composer update'\n";
        system("sh -c 'cd update; composer update'");

        echo "==== Refreshing development environment with changes.\n\n";
        if ($environment == null) {
            echo "Warning - no environment has been configured, so skipping this step.\n";
        } else {
            $environment->syncToEnvironment($config);
            $environment->runCommand($config, "cd /vagrant; composer install");
            $environment->runCommand($config, "cd /vagrant; bin/magento cache:clean");
            $environment->runCommand($config, "cd /vagrant; bin/magento setup:upgrade");
            $environment->runCommand($config, "cd /vagrant; bin/magento indexer:reindex");
            $environment->runCommand($config, "cd /vagrant; bin/magento maintenance:disable");

            // TODO: Should be able to remove this one day.
            // Above commands result in 'localhost' being in cached files - clear
            // the cache to lose that setting.
            $environment->runCommand($config, "cd /vagrant; rm -rf var/cache");
        }
    }

    /**
     * @inheritdoc
     */
    public function pushCode($config, $environment)
    {
        $this->checkConnection($config);

        echo "==== Put production store into mainenance mode.\n";
        $htdocs = self::HTDOCS_PATH;
        $this->runOnProd($config, "cd $htdocs; sudo -u daemon bin/magento maintenance:enable");

        echo "==== Merge development changes on production.\n";
        $this->copyLocalToRemote($config, ".", $htdocs, array_merge(self::EXCLUDE_LIST, $environment->excludeFiles()));
        $this->runOnProd($config, "cd $htdocs; sudo chgrp -R daemon .; sudo chmod -R g+w .");

        echo "==== Refresh any composer installed libraries.\n";
        // This turns off the Magento installer installing 'base' package changes
        // over the top of any locally committed changes. Eventually this will
        // no longer be required. For now, do not do this in production.
        $this->runOnProd($config, "cd $htdocs; mv composer.json composer.json.original");
        $this->runOnProd($config, "cd $htdocs; sed <composer.json.original >composer.json -e \"/extra.:/ a\\
                \\\"magento-deploystrategy\\\": \\\"none\\\",
        \"");
        $this->runOnProd($config, "cd $htdocs; composer install");
        $this->runOnProd($config, "cd $htdocs; mv composer.json.original composer.json");
        $this->runOnProd($config, "cd $htdocs; sudo chown -R daemon:daemon var pub/static");

        echo "==== Update the database schema.\n";
        $this->runOnProd($config, "cd $htdocs; sudo -u daemon bin/magento setup:upgrade");

        echo "==== Switching production mode, triggering compile and content deployment.\n";
        $this->runOnProd($config, "cd $htdocs; sudo -u daemon bin/magento deploy:mode:set production");
        $this->runOnProd($config, "cd $htdocs; sudo -u daemon bin/magento maintenance:disable");
        $this->runOnProd($config, "cd $htdocs; sudo chmod -R g+ws var pub/static");

        echo "==== Turning off bitnami banner\n";
        $this->runOnProd($config, "sudo /opt/bitnami/apps/magento/bnconfig --disable_banner 1");
        $this->runOnProd($config, "sudo /opt/bitnami/ctlscript.sh restart apache");

        echo "==== Ready for use.\n";
    }

    /**
     * Disconnect from hosting provider.
     * @throws MdException Thrown on error.
     */
    public function disconnect(&$config)
    {
        if (!isset($config['godaddy'])) {
            echo "Not currently connected.\n";
            return;
        }
        unset($config['godaddy']);
    }

    /**
     * Run a command on the remote host using SSH, capturing output.
     * @param array $config Configuration details to connect to server.
     * @param string $cmd Command to run on remote server. Be careful with quotes and escaping rules!
     * @return string[] The output of the command that was run
     * @throws MdException Thrown on error.
     */
    private function runOnProd(&$config, $cmd)
    {
        $host = $this->getSshHost($config);
        $user = $this->getSshUser($config);
        $port = $this->getSshPort($config);
        $identity = $this->getSshIdentity($config);

        $opts = '';

        if ($port != '22') {
            $opts .= " -p $port";
        }

        if ($identity != '') {
            $opts .= " -i $identity";
        }

        $cmd = "sh -c 'ssh$opts $user@$host \"$cmd\"'";
        echo "> $cmd\n";
        exec($cmd, $output, $exitStatus);
        if ($exitStatus != 0) {
            throw new MdException("Failed to execute SSH command on production server.", 1);
        }
        return $output;
    }

    /**
     * Copy files from production server to the local environment.
     * @param array $config Configuration settings.
     * @param string $fromDir The source directory (typically "htdocs") on the production server.
     * @param string $toDir The destination directory (typically ".").
     * @param string[] $excludes A list of files and directories to not copy.
     * @throws MdException Thrown on error for user to see.
     */
    private function copyRemoteToLocal($config, $fromDir, $toDir, $excludes)
    {
        $host = $this->getSshHost($config);
        $user = $this->getSshUser($config);
        $port = $this->getSshPort($config);
        $identity = $this->getSshIdentity($config);

        $opts = '';

        if ($port != '22') {
            $opts .= " -p $port";
        }

        if ($identity != '') {
            $opts .= " -i $identity";
        }

        $rsyncOpts = "";
        if (!empty($excludes)) {
            $rsyncOpts .= " --exclude " . implode(" --exclude ", $excludes);
        }

        $cmd = "sh -c 'rsync -r -e \"ssh$opts\"$rsyncOpts $user@$host:$fromDir $toDir'";
        echo "> $cmd\n";
        system($cmd, $exitStatus);
        if ($exitStatus != 0) {
            throw new MdException("Failed to execute scp command on production server.", 1);
        }
    }

    /**
     * Copy files from local environment to the production environment.
     * @param array $config Configuration settings.
     * @param string $fromDir The source directory (typically ".").
     * @param string $toDir The destination directory (typically "htdocs") on the production server.
     * @param string[] $excludes A list of files and directories to not copy.
     * @throws MdException Thrown on error for user to see.
     */
    private function copyLocalToRemote($config, $fromDir, $toDir, $excludes)
    {
        $host = $this->getSshHost($config);
        $user = $this->getSshUser($config);
        $port = $this->getSshPort($config);
        $identity = $this->getSshIdentity($config);

        $opts = '';

        if ($port != '22') {
            $opts .= " -p $port";
        }

        if ($identity != '') {
            $opts .= " -i $identity";
        }

        $rsyncOpts = "";
        if (!empty($excludes)) {
            $rsyncOpts .= " --exclude " . implode(" --exclude ", $excludes);
        }

        $cmd = "sh -c 'rsync -r -e \"ssh$opts\"$rsyncOpts $user@$host:$fromDir $toDir'";
        echo "> $cmd\n";
        system($cmd, $exitStatus);
        if ($exitStatus != 0) {
            throw new MdException("Failed to execute scp command on production server.", 1);
        }
    }

    /**
     * Get the specified path out of the configuration file.
     * @param array $config Configuration file loaded from disk.
     * @param string[] $path Path to requested configuration settings (e.g. ['ssh', 'port']).
     * @return string Configuration setting for the specified path, or empty string if not found.
     * @throws MdException Thrown on error.
     */
    private function getGoDaddyConfig($config, $path)
    {
        if (!isset($config['godaddy'])) {
            throw new MdException("Please use the 'connect' command to provide connection details.\n", 1);
        }
        $c = $config['godaddy'];
        foreach ($path as $name) {
            if (!isset($c[$name])) {
                return '';
            }
            $c = $c[$name];
        }
        return $c;
    }

    /**
     * @param $config
     * @return string
     * @throws MdException
     */
    private function getSshHost($config)
    {
        $host = $this->getGoDaddyConfig($config, ['ssh', 'host']);
        if ($host == '') {
            throw new MdException("godaddy.ssh.host is not set\n", 1);
        }
        return $host;
    }

    /**
     * @param $config Configuration file loaded from disk.
     * @return string SSH user name to use to connect.
     * @throws MdException Thrown on error.
     */
    private function getSshUser($config)
    {
        $user = $this->getGoDaddyConfig($config, ['ssh', 'user']);
        if ($user == '') {
            throw new MdException("godaddy.ssh.user is not set\n", 1);
        }
        return $user;
    }

    /**
     * @param $config
     * @return string
     */
    private function getSshPort($config)
    {
        $port = $this->getGoDaddyConfig($config, ['ssh', 'port']);
        if ($port == '') {
            $port = '22';
        }
        return $port;
    }

    /**
     * @param $config
     * @return string
     */
    private function getSshIdentity($config)
    {
        $identity = $this->getGoDaddyConfig($config, ['ssh', 'identity']);
        if ($identity == '') {
            $identity = "~/.ssh/" . self::IDENTITY_FILE;
        }
        return $identity;
    }

    /**
     * Check the connection to GoDaddy and the UID of the logged in account due to some timing
     * issues with fast, automated deployments.
     * @para array $config Holds connection details.
     * @throws MdException Thrown on error.
     */
    public function checkConnection($config)
    {
        echo "==== Checking SSH connection to production server.\n";

        // Make sure we can run commands remotely successfully
        //$this->runOnProd($config, "true");

        // I don't understand, but the user id changes after a while.
        // If we jump in too early, the file permissions won't work.
        // So wait until the user id flip occurs.
        // (This also checks the connection to the remote server is working.)
        $retryLimit = 10;
        while ($retryLimit-- > 0) {
            $output = $this->runOnProd($config, "id");
            if (strpos(implode("", $output), "bitnami") !== false) {
                break;
            }
            echo "User ID is not bitnami yet, retrying...\n";
            sleep(2);
        }
    }
}
