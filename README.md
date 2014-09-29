pimcore-setup
==========================

Overview / Build Process
--------------------------

The build system is works for these scenarios:

* Development on a **Local System** (Ubuntu/Linux host, no virtualization)
* Development via a **Virtual Machine** "Virtualbox" (Ubuntu, OSX, Windows hosts)

Scenario: Local System
--------------------------

_A developer uses a native Ubuntu 14.04 LTS System and wants work on the project._

**Requirements:**

* Ubuntu 14.04 LTS
* PHP development environment with Phing

**Usage:**

* execute `phing setup` in the `/build` directory

**Notes:**

* See below ("Phing Project Setup") for a detailed description of the process


Scenario: Virtual Machine
--------------------------

_A developer uses a Windows or OSX workstation and wants work on the project.
This setup is recommended for Linux users, too, as it provides the most stable
environment._

**Requirements:**

* Installed VirtualBox on host (developer machine)
* Installed [Vagrant](http://www.vagrantup.com/) on host (developer machine)

**Usage:**

* execute `vagrant up` in the `/build` directory

**Notes:**

This setup uses three distinct consecutive steps:

1. Setting up a virtual machine and configuring the hardware - _See below "Vagrant VirtualBox Setup" for a detailed description of the vagrant process_
1. Installing and configuring the OS and the software - _See below "Ansible Software Setup" for a detailed description of the ansible process_
1. Installing the project - _See below "Phing Project Setup" for a detailed description of the phing process_


Vagrant VirtualBox Setup
--------------------------

* Vagrant version 1.4.3 with Virtualbox 4.3.14 is known to work
* `build/Vagrantfile` contains the description of the virtual hardware
* Default base image is a vanilla Ubuntu Server 14.04 LTS 64bit
* NAT access via Portmapping: 8080(host) to 80(guest)
* Dedicated Host-Only access via private net, Fixed IP address of guest is `192.168.50.10`, host has `192.168.50.1`
* Project directory is mounted on /var/www/sites/project, executable flag is not set (which means that for example `./run_task.php` will not work,  use `php -f ./run_task.php` instead)!
* OS/Software setup (aka "provisioning") is done by `build/ansible/boostrap.sh` - this script installs the ansible software on the guest an then runs the ansible setup - see below for *Ansible Software Setup*
* Please note, the following notices and messages (allthough they appear in red or yellow) are not relevant and can be ignored:
    * `dpkg-preconfigure: unable to re-open stdin: No such file or directory`
    * `stdin: is not a tty`

Ansible Software Setup
--------------------------

Ansible is a system for configuring machines: Software packages, config file, etc.
It uses _"inventories"_ which contain lists of hosts to operate on and _"playbooks"_
which list "tasks" to perform. "tasks" in "playbooks" are usually grouped in "roles".
Inventories can contain specific configuration settings (variables).

Our system uses two different inventory files:

1. `inventory-local`
1. `inventory-vagrant`

And a set of playbooks:

* `system.yml` - Installs the software packages (apache, php ..) and configures the OS. For production systems, shell access via SSH keys / www-data user is configured, too. Change `build/ansible/roles/shell-access/templates/authorized_keys.j2`  to configure who has access to this project.
* `project.yml` - Clones the Git project repository (only for production systems) and then installs the project via `phing setup`
* `site.yml` - this book just includes `system.yml` and `project.yml` running them both. Usage: Setting up a vagrant machine.

**Notes:**

* a statically compiled version of ffmpeg is download from our Amazon AWS S3 account
* Ansible version 1.6.8 is known to work


Phing Project Setup
--------------------------

Phing is able do a set of actions groups in the form of _targets_. Its description
file is `build/build.xml`. Run phing by changing/cd'ing to `build` and run
`phing TARGET`. Example: `phing setup` (Installs the project). A Target is
made up out of _tasks_ which perform different action - have a look at the
file and the phing documentation.

Our build file uses a set of configuration variables (called `properties` in phing):

* Default settings are directly set at the top in the `build.xml` file
* System (machine) wide settings in `/etc/phing-build.properties` - here, things like the mysql root password can be configured
* Project specific defaults `build/build.properties`
* `/var/build/build.properties` for local overrides - this file is a copy of `build/build.properties` - create it manually prior to calling `phing setup` in order to override things for a specific install

The default project setup target (invoked via `phing setup`) performs the
following actions:

1. Creating a mysql database (if it does not exists, yet)
1. Integrating pimcore by:
    1. Downloading a pimcore.zip (the latest, see property `pimcore.downloadUrl`)
    1. Unpacking the ZIP in `var/build/tmp`
    1. "merging" the Pimcore files from `var/build/tmp` into the `htdocs` directory via rsync - ommitting all already existing files (such as `htdocs/website/controllers/DefaultController.php`)
    1. removing the unpacked pimcore files
1. Installing pimcore via the `build/bin/pimcore_install.php` script (basically the same process as using the web bases installer). Default admin password is "password".
1. Creating a set of config files by copying `build/etc/samples/*` to `var/build/` and replace a set of tokens in the process
1. Adding an Apache VHOST config
1. Adding a crontab entry for the pimcore maintenance script

**Notes:**

* Phing version 2.5.0 is known to work


Sample "vagrant up" run
==========================

    $ vagrant up

    Bringing machine 'default' up with 'virtualbox' provider...
    [default] Importing base box 'trusty64'...
    [default] Matching MAC address for NAT networking...
    [default] Setting the name of the VM...
    [default] Clearing any previously set forwarded ports...
    [default] Clearing any previously set network interfaces...
    [default] Preparing network interfaces based on configuration...
    [default] Forwarding ports...
    [default] -- 22 => 2222 (adapter 1)
    [default] -- 80 => 8080 (adapter 1)
    [default] -- 3306 => 3307 (adapter 1)
    [default] Running 'pre-boot' VM customizations...
    [default] Booting VM...
    [default] Waiting for machine to boot. This may take a few minutes...
    [default] Machine booted and ready!
    [default] Setting hostname...
    [default] Configuring and enabling network interfaces...
    [default] Mounting shared folders...
    [default] -- /vagrant
    [default] -- /var/www/sites/project
    [default] Running provisioner: shell...
    [default] Running: /tmp/vagrant-shell20140929-10708-y06slw
    stdin: is not a tty
    ====================================================================
    PREPARING ANSIBLE - installing ansible on guest machine
    --------------------------------------------------------------------
    Updating apt cache
    Ign http://archive.ubuntu.com trusty InRelease
    Ign http://archive.ubuntu.com trusty-updates InRelease
    Hit http://archive.ubuntu.com trusty Release.gpg
    Get:1 http://archive.ubuntu.com trusty-updates Release.gpg [933 B]
    Ign http://security.ubuntu.com trusty-security InRelease
    Hit http://archive.ubuntu.com trusty Release
    Get:2 http://archive.ubuntu.com trusty-updates Release [59.7 kB]
    Get:3 http://security.ubuntu.com trusty-security Release.gpg [933 B]
    Get:4 http://archive.ubuntu.com trusty/main Sources [1,064 kB]
    Get:5 http://security.ubuntu.com trusty-security Release [59.7 kB]
    Get:6 http://archive.ubuntu.com trusty/universe Sources [6,399 kB]
    Get:7 http://security.ubuntu.com trusty-security/main Sources [45.3 kB]
    Get:8 http://security.ubuntu.com trusty-security/universe Sources [10.8 kB]
    Get:9 http://security.ubuntu.com trusty-security/main amd64 Packages [144 kB]
    Get:10 http://security.ubuntu.com trusty-security/universe amd64 Packages [48.9 kB]
    Get:11 http://security.ubuntu.com trusty-security/main Translation-en [70.2 kB]
    Get:12 http://security.ubuntu.com trusty-security/universe Translation-en [28.6 kB]
    Hit http://archive.ubuntu.com trusty/main amd64 Packages
    Hit http://archive.ubuntu.com trusty/universe amd64 Packages
    Hit http://archive.ubuntu.com trusty/main Translation-en
    Hit http://archive.ubuntu.com trusty/universe Translation-en
    Get:13 http://archive.ubuntu.com trusty-updates/main Sources [121 kB]
    Get:14 http://archive.ubuntu.com trusty-updates/universe Sources [85.1 kB]
    Get:15 http://archive.ubuntu.com trusty-updates/main amd64 Packages [324 kB]
    Get:16 http://archive.ubuntu.com trusty-updates/universe amd64 Packages [205 kB]
    Get:17 http://archive.ubuntu.com trusty-updates/main Translation-en [146 kB]
    Get:18 http://archive.ubuntu.com trusty-updates/universe Translation-en [104 kB]
    Ign http://archive.ubuntu.com trusty/main Translation-en_US
    Ign http://archive.ubuntu.com trusty/universe Translation-en_US
    Fetched 8,917 kB in 4s (1,974 kB/s)
    Reading package lists...
    Installing Ansible, sshpass
    Reading package lists...
    Building dependency tree...
    Reading state information...
    The following extra packages will be installed:
      python-jinja2 python-markupsafe
    Suggested packages:
      ansible-doc python-jinja2-doc
    The following NEW packages will be installed:
      ansible python-jinja2 python-markupsafe sshpass
    0 upgraded, 4 newly installed, 0 to remove and 74 not upgraded.
    Need to get 604 kB of archives.
    After this operation, 3,903 kB of additional disk space will be used.
    Get:1 http://archive.ubuntu.com/ubuntu/ trusty/main python-markupsafe amd64 0.18-1build2 [14.3 kB]
    Get:2 http://archive.ubuntu.com/ubuntu/ trusty/main python-jinja2 all 2.7.2-2 [161 kB]
    Get:3 http://archive.ubuntu.com/ubuntu/ trusty/universe ansible all 1.5.4+dfsg-1 [418 kB]
    Get:4 http://archive.ubuntu.com/ubuntu/ trusty/universe sshpass amd64 1.05-1 [10.5 kB]
    dpkg-preconfigure: unable to re-open stdin: No such file or directory
    Fetched 604 kB in 1s (366 kB/s)
    Selecting previously unselected package python-markupsafe.
    (Reading database ... 60914 files and directories currently installed.)
    Preparing to unpack .../python-markupsafe_0.18-1build2_amd64.deb ...
    Unpacking python-markupsafe (0.18-1build2) ...
    Selecting previously unselected package python-jinja2.
    Preparing to unpack .../python-jinja2_2.7.2-2_all.deb ...
    Unpacking python-jinja2 (2.7.2-2) ...
    Selecting previously unselected package ansible.
    Preparing to unpack .../ansible_1.5.4+dfsg-1_all.deb ...
    Unpacking ansible (1.5.4+dfsg-1) ...
    Selecting previously unselected package sshpass.
    Preparing to unpack .../sshpass_1.05-1_amd64.deb ...
    Unpacking sshpass (1.05-1) ...
    Processing triggers for man-db (2.6.7.1-1) ...
    Setting up python-markupsafe (0.18-1build2) ...
    Setting up python-jinja2 (2.7.2-2) ...
    Setting up ansible (1.5.4+dfsg-1) ...
    Setting up sshpass (1.05-1) ...
    ====================================================================
    RUNNING ANSIBLE - configuring OS/software & executing 'phing setup'
    please be patient! (this task will finish in a couple of minutes)
    --------------------------------------------------------------------

    PLAY [Install OS + Project] ***************************************************

    GATHERING FACTS ***************************************************************
    ok: [192.168.50.10]

    PLAY [Install LAMP Stack with PHP 5.5 and MySQL] ******************************

    GATHERING FACTS ***************************************************************
    ok: [192.168.50.10]

    TASK: [upgrade | Running apt-get update] **************************************
    ok: [192.168.50.10]

    TASK: [upgrade | Running apt-get upgrade] *************************************
    changed: [192.168.50.10]

    TASK: [common | Common software] **********************************************
    changed: [192.168.50.10] => (item=python-software-properties,build-essential,curl,screen,vim,ntp,jpegoptim,pngcrush,git-core,ufw)

    TASK: [common | Set default locale] *******************************************
    changed: [192.168.50.10]

    TASK: [apache | Install Apache] ***********************************************
    changed: [192.168.50.10]

    TASK: [apache | Enable Apache2 rewrite module] ********************************
    changed: [192.168.50.10]

    TASK: [apache | Enable Apache2 unique_id module] ******************************
    changed: [192.168.50.10]

    TASK: [apache | Enable Apache2 expires module] ********************************
    changed: [192.168.50.10]

    TASK: [apache | Enable Apache2 headers module] ********************************
    changed: [192.168.50.10]

    TASK: [apache | Disable server signature] *************************************
    changed: [192.168.50.10]

    TASK: [apache | Disable server tokens] ****************************************
    changed: [192.168.50.10]

    TASK: [apache | Set a global ServerNameserver tokens] *************************
    changed: [192.168.50.10]

    TASK: [apache | Set max request workers to 20] ********************************
    changed: [192.168.50.10]

    TASK: [pagespeed | Download mod_pagespeed] ************************************
    changed: [192.168.50.10]

    TASK: [pagespeed | Install mod_pagespeed] *************************************
    changed: [192.168.50.10]

    TASK: [mysql | Install MySQL server] ******************************************
    changed: [192.168.50.10]

    TASK: [mysql | Start MySQL Server] ********************************************
    ok: [192.168.50.10]

    TASK: [memcached | Memcached] *************************************************
    changed: [192.168.50.10] => (item=memcached)

    TASK: [beanstalkd | Install beanstalkd] ***************************************
    changed: [192.168.50.10] => (item=beanstalkd)

    TASK: [beanstalkd | beanstalk - listen only on localhost interface] ***********
    ok: [192.168.50.10]

    TASK: [beanstalkd | Enable beanstalkd] ****************************************
    changed: [192.168.50.10]

    TASK: [mongodb | MongoDB] *****************************************************
    changed: [192.168.50.10] => (item=mongodb)

    TASK: [php | Install PHP5] ****************************************************
    changed: [192.168.50.10] => (item=php5-common,php5-mysqlnd,php5-xmlrpc,php5-mcrypt,php5-curl,php5-gd,php5-cli,php-pear,php5-dev,php5-imap,php5-imagick,php5-memcache,php5-mongo,libapache2-mod-php5)

    TASK: [php | Enable PHP5 opcode cache] ****************************************
    changed: [192.168.50.10]

    TASK: [php | Enable PHP5 mcrypt] **********************************************
    changed: [192.168.50.10]

    TASK: [php | Raise PHP memory Limit for Apache] *******************************
    changed: [192.168.50.10]

    TASK: [php | Allow short open tags for Apache PHP] ****************************
    changed: [192.168.50.10]

    TASK: [php | Allow short open tags for CLI PHP] *******************************
    changed: [192.168.50.10]

    TASK: [php | Allow PCNTL for Apache PHP] **************************************
    ok: [192.168.50.10]

    TASK: [php | Allow PCNTL for CLI PHP] *****************************************
    changed: [192.168.50.10]

    TASK: [php | Allow writeable PHAR archives] ***********************************
    changed: [192.168.50.10]

    TASK: [php | Do not expose Apache PHP] ****************************************
    changed: [192.168.50.10]

    TASK: [php | 8MB max upload size] *********************************************
    changed: [192.168.50.10]

    TASK: [php | 12MB max POST size] **********************************************
    changed: [192.168.50.10]

    TASK: [composer | Add Composer PPA] *******************************************
    changed: [192.168.50.10]

    TASK: [composer | Install Composer] *******************************************
    changed: [192.168.50.10]

    TASK: [composer | Composer self update] ***************************************
    changed: [192.168.50.10]

    TASK: [php-pear | Install the system PEAR package] ****************************
    ok: [192.168.50.10]

    TASK: [php-pear | Discover PEAR channels] *************************************
    changed: [192.168.50.10] => (item=pear.phing.info)

    TASK: [php-pear | Install PEAR packages] **************************************
    changed: [192.168.50.10] => (item=phing/phing)
    changed: [192.168.50.10] => (item=HTTP_Request2)

    TASK: [vagrant | Set apache run user to vagrant in /etc/apache2/envvars] ******
    changed: [192.168.50.10]

    TASK: [vagrant | Configure system wide phing build properties] ****************
    changed: [192.168.50.10]

    TASK: [vagrant | Disable apache default site] *********************************
    changed: [192.168.50.10]

    NOTIFIED: [apache | restart apache2] ******************************************
    changed: [192.168.50.10]

    NOTIFIED: [beanstalkd | restart beanstalkd] ***********************************
    changed: [192.168.50.10]

    PLAY [Set up project] *********************************************************

    GATHERING FACTS ***************************************************************
    ok: [192.168.50.10]

    TASK: [pimcore | Pimcore setup] ***********************************************
    changed: [192.168.50.10]

    TASK: [pimcore | debug var=pimcoresetup.stdout_lines] *************************
    ok: [192.168.50.10] => {
        "item": "",
        "pimcoresetup.stdout_lines": [
            "Buildfile: /var/www/sites/project/build/build.xml",
            " [property] Loading /var/www/sites/project/build/build.properties",
            " [property] Loading /etc/phing-build.properties",
            "",
            "project > setup:",
            "",
            "     [echo] ==========================================================",
            "     [echo] PHING PROJECT SETUP - installing on: vagrant ",
            "     [echo] ----------------------------------------------------------",
            "[phingcall] Calling Buildfile '/var/www/sites/project/build/build.xml' with target 'dbcreate'",
            " [property] Loading /var/www/sites/project/build/build.properties",
            " [property] Loading /etc/phing-build.properties",
            "",
            "project > dbcreate:",
            "",
            "     [echo] Creating Database / Access - [dbcreate]",
            "[pdosqlexec] Executing commands",
            "[pdosqlexec] 5 of 5 SQL statements executed successfully",
            "[pdosqlexec] Executing commands",
            "[pdosqlexec] test_message",
            "[pdosqlexec] SUCCESS",
            "[pdosqlexec] 1 of 1 SQL statements executed successfully",
            "[phingcall] Calling Buildfile '/var/www/sites/project/build/build.xml' with target 'pimcoremerge'",
            " [property] Loading /var/www/sites/project/build/build.properties",
            " [property] Loading /etc/phing-build.properties",
            "",
            "project > pimcoremerge:",
            "",
            "     [echo] Download and unpack pimcore distribution - [pimcoremerge]",
            "  [httpget] Fetching http://storage.apprunner.de/packages/pimcore-2_3_0.zip",
            "  [httpget] Contents from http://storage.apprunner.de/packages/pimcore-2_3_0.zip saved to var/build/pimcore-distribution.zip",
            "    [mkdir] Created dir: /var/www/sites/project/var/build/pimcore-distribution",
            "    [unzip] Extracting zip: /var/www/sites/project/var/build/pimcore-distribution.zip to /var/www/sites/project/var/build/pimcore-distribution",
            "   [delete] Deleting directory /var/www/sites/project/var/build/pimcore-distribution",
            "[phingcall] Calling Buildfile '/var/www/sites/project/build/build.xml' with target 'pimcoreinstall'",
            " [property] Loading /var/www/sites/project/build/build.properties",
            " [property] Loading /etc/phing-build.properties",
            "",
            "project > pimcoreinstall:",
            "",
            "     [echo] Installing Pimcore - [pimcoreinstall]",
            "     [exec] PIMCORE INSTALL SUCCESSFUL",
            "[phingcall] Calling Buildfile '/var/www/sites/project/build/build.xml' with target 'configcreate'",
            " [property] Loading /var/www/sites/project/build/build.properties",
            " [property] Loading /etc/phing-build.properties",
            "",
            "project > configcreate:",
            "",
            "     [echo] Creating set of configs in var/build - [configcreate]",
            "     [copy] Copying 3 files to /var/www/sites/project/var/build",
            "     [copy] Copying 1 file to /var/www/sites/project/htdocs/website/var/config",
            "    [chmod] Total files changed to 666: 1",
            "    [chmod] Total directories changed to 666: 0",
            "[phingcall] Calling Buildfile '/var/www/sites/project/build/build.xml' with target 'apacheconfig'",
            " [property] Loading /var/www/sites/project/build/build.properties",
            " [property] Loading /etc/phing-build.properties",
            "",
            "project > apacheconfig:",
            "",
            "     [echo] Configure Apache - [apacheconfig]",
            "     [exec] Enabling site pimcore.",
            "     [exec] To activate the new configuration, you need to run:",
            "     [exec]   service apache2 reload",
            "[phingcall] Calling Buildfile '/var/www/sites/project/build/build.xml' with target 'apacherestart'",
            " [property] Loading /var/www/sites/project/build/build.properties",
            " [property] Loading /etc/phing-build.properties",
            "",
            "project > apacherestart:",
            "",
            "     [exec]  * Restarting web server apache2",
            "     [exec]    ...done.",
            "[phingcall] Calling Buildfile '/var/www/sites/project/build/build.xml' with target 'installcron'",
            " [property] Loading /var/www/sites/project/build/build.properties",
            " [property] Loading /etc/phing-build.properties",
            "",
            "project > installcron:",
            "",
            "     [echo] Installing pimcore maintenance cron job - [installcron]",
            "",
            "BUILD FINISHED",
            "",
            "Total time: 2 minutes  25.30 seconds"
        ]
    }

    TASK: [pimcore | Disable Apache default site] *********************************
    ok: [192.168.50.10]

    PLAY RECAP ********************************************************************
    192.168.50.10              : ok=51   changed=41   unreachable=0    failed=0


Voila, the pimcore site is available at: http://192.168.50.10/