# Magento Development Environment creation tool

This project contains my experimentation with a tool to create development environments
for Magento 2 projects. If successful, this project will get sucked into something more
permanent. For now, this is a good way to share what I am doing with any external people
who are interested.

For example, one option is to merge this work into the Magento CLI via a Composer package.
I have not done that here however as I am hoping this tool will not supposed to be tied
to specific versions of Magento (although maybe it should be in case of configuration
changes).

First step is to build an 'rsync' based Vagrantfile. Another variation might be a Docker
based setup on Mac with native file sharing (available in Docker 1.12). By keeping such
files out of the 'git' repository for a project, this should allow different developers
on a project to use their own environment of choice. (That is part of the experiment - 
to see if this is actually useful.)

The first environment is 'vagrant-rsync'.
 
- **magento-dev create vagrant-rsync**
    Creates a Vagrantfile and support files to use Vagrant to set up a M2 development
    environment. Uses 'vagrant rsync-auto' to copy local file system changes into the box.
    This works well on Windows, allowing the source code to be kept on your laptop natively
    (which is generally better for an IDE) while having the full database and other tools
    run in a VM, avoiding problems with multiple versions of PHP and MySQL etc installed.

Feedback welcome. Talk to me before you plan any big contributions to avoid merge conflicts
(I am still doing reasonably dramatic changes to the code).