# Magento Development Environment creation tool

This project contains my experimentation with a tool to create development environments
for Magento 2 projects. If successful, this project will get sucked into something more
permanent. For now, this is a good way to share what I am doing with any external people
who are interested.

For example, one option is to merge this work into the Magento CLI via a Composer package.
I have not done that here however as I am hoping this tool will not supposed to be tied
to specific versions of Magento (although maybe it should be in case of configuration
changes).

There are two concepts supported by this tool, *environments* and *providers*.
Environments are for local development and may be created using configuration files for
technologies such as Docker or Vagrant. 
Providers are hosting providers for when you want to push your project code changes into
production. 
Both environments and providers here are intended for simple projects. It is likely
that advanced users would build their own variation of tools and deployment processes.
The goal here is to get people going.

Currently there is no installer. You need to get this code directly from GitHub and create
a shell script or BAT file to run the `magento-dev.php` PHP script. The script takes command
line arguments as follows:

```
Usage: magento-dev <command> where 'command' is one of:

  create <environment> ...  Create new development environment.
  destroy [--force]         Destroy all files created for the environment.
  connect <provider> ...    Connect to a production host.
  pull-code                 Pull a copy of the code from the current provider.
  push-code                 Push the code and run deployment actions.
  disconnect                Forget about the last environment connected to.
```

Available environments:

- [**vagrant-rsync**](src/AlanKent/MagentoDev/Environments/VagrantRsync/README.md): 
    Creates a Vagrantfile and support files to use Vagrant to set up a M2 development
    environment. Uses `vagrant rsync-auto` to copy local file system changes into the box.
    This works well on Windows, allowing the source code to be kept on your laptop natively
    (which is generally better for an IDE) while having the full database and other tools
    run in a VM, avoiding problems with multiple versions of PHP and MySQL etc installed.

Available providers:

- [**magento-cloud**](src/AlanKent/MagentoDev/Providers/MagentoCloud/README.md): 
    Displays instructions how to push/pull code etc for Magento Cloud.


Feedback welcome. Talk to me before you plan any big contributions to avoid merge conflicts
(I am still doing reasonably dramatic changes to the code).

If successful, this code may be merged into the Magento code base and so follow standard
Magento copyright ownership and licenses. All external code contributions should keep this
in mind.

## TODO

* "Environment" might be confusing for Magento Cloud users, as it is something different.
* "Provider" might be better called "Hosting Provider" or "Connector".
