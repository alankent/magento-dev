# Environment: vagrant-rsync

The "vagrant-rsync" environment creates a Vagrant box (VM) for running a web server,
grunt/gulp, MySQL, etc. All of the tools are preinstalled and run within the VM.
The source files are left on the host OS and copied into the box via an rsync command.

Assumptions:
* It is assumed you will use git locally to download the project before using this
  environment.
* It is assumed you will use an IDE or editor locally.
* As a result of the above, it is also assumed you will run composer locally before using
  this box to download all package dependencies. This allows IDEs like PHP Storm to browse
  Magento source code locally (not just your project's source code).
* The box does an rsync of the users ~/.composer directory into the box, on the assumption
  that 'composer install' has already been run. This makes the box much faster as it gets
  a copy of all the cached download packages from the user's account. This can be
  particularly useful if you delete and recreate boxes frequently - it avoids unnecessary
  downloads.
* The above works best if the user puts their Magento Marketplace repository keys in the
  ~/.composer/auth.json file, which will cause that to be copied into the box as well.

Advantages of this approach include:
* Source files are kept outside the box, making it safe to delete the box at any time
  (for exmple, if you want to rebuild it.)
* Using rsync to copy files into the box means inside the box you can use tools like
  grunt and gulp to watch for local file system changes, which does not always work
  with mounted volumes (for example on Windows).
* PHP Storm users can leverage the built in support for Vagrant. This means you can do
  things like easily register a "remote debugger" to debug code running inside the box.
* PHP Storm can also be easily configured to run unit tests inside the VM.
  
Disadvantages of this approach include:
* You have to remember to run 'vagrant rsync-auto' in a window to have a process to watch
  for local file changes and copy them into the box.
* On Windows, I have found using ^C to stop the 'vagrant rsync-command' frequently does not
  kill the Ruby executable watching for file system changes. The prompt comes back, but the
  process is not actually killed, triggering rsyncs even after you thought you had killed
  it.
* If using PHP storm on your local environment, you still need to install PHP in order
  to run Composer to download packages. You will also need to install your IDE and any
  other tools you decide you want to run locally (such as git).
  
## Typical Installation Steps

Definitive instructions are difficult as there are variations between projects, the but
following outlines a typical set of steps to use this environment.

* Install your favorite IDE (such as PHP Storm).
* Install git and other favorite local command line tools.
* Install Vagrant.
* Install PHP and Composer (Composer needs PHP to run).
* Check out your project source code locally, however you normally do that. (E.g. git clone).
* Run **composer install** to download all external dependencies. This will interactively
  prompt for the Magento Marketplace credentials and cache them in **~/.composer/auth.json**.
* Run **magento-dev create vagrant-rsync** to create a Vagrantfile.
* Run **vagrant up** to start up the box.
* Run **vagrant rsync-auto** to start watching the local file system and copy files into the box.
  You need to keep this command always running during development.
* Run **vagrant ssh** (in another window) if you want to log into the box.
* Configure a "remote interpreter" in PHP Storm so it knows how to run the PHP code in Vagrant.
* Configure PHP Storm unit testing to use the remote interpreter.
