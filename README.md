### GETTING STARTED ####

Checkout the source code from git into your working directory:
- git clone https://<username>@bitbucket.org/vocalizr/app.git .
- git checkout dev 

Vagrant
Install Vagrant  - https://www.vagrantup.com/
You'll need Virtual Box installed

The Vagrant file is attached (I've only used on my MAC so might need updates for Windows / Linux)
The code should sit in the same directory as the Vagrantfile
Update the vagrantfile with the correct file paths if necessary run 
- vagrant up

Then to ssh into the vagrant machine use
- vagrant ssh

Install GIT
- sudo apt-get install git

Code is at 
- cd /data

Copy /data/app/config/parameters.template.yml to /data/app/config/parameters.yml
- cp /data/app/config/parameters.template.yml /data/app/config/parameters.yml

Enable opcache for PHP
- sudo /etc/php5/apache2/php.ini

opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=128
opcache.save_comments=1
opcache.load_comments=1

then restart apache: 
- sudo service apache2 restart


Install database and load fixtures
- php app/console doctrine:schema:create
- php app/console doctrine:fixtures:load


Update your hosts file:
192.168.33.10 local.vocalizr.com


Load website!
http://local.vocalizr.com





If website is running slow, you can install virtualbox nfs
Windows:
- vagrant plugin install vagrant-winnfsd



## OTHER COMMANDS


Database is MySQL

- mysql -h local -u root vocalizr


To turn off the machine use 
- vagrant halt

SYMFONY
We are using symfony 2.2 :(
https://symfony.com/doc/2.2/index.html

Run symfony commands on the vagrant machine from the project root directory

Clear cache
- php app/console cache:clear

Regenerate seed data
- php app/console doctrine:fixtures:load


To see a list of all symfony commands
- php app/console