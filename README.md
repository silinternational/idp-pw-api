# idp-pw-api
Backend API for Identity Provider Password Management

## Build Status
[![Codeship Status for silinternational/idp-pw-api](https://codeship.com/projects/6e239250-bed3-0133-700c-329cf2fde74f/status?branch=develop)](https://codeship.com/projects/137021)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/silinternational/idp-pw-api/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/silinternational/idp-pw-api/?branch=develop)

## Dev Requirements
 
### Linux
1. Docker >= 1.9.1
2. Docker Compose >= 1.5

### Mac
1. VirtualBox
2. Docker Toolbox >= 1.9.1

### Windows
1. VirtualBox
2. Vagrant
3. Alternative to using vagrant you can install Docker Toolbox, but Docker Compose
   still has issues with Windows and doesn't support interactive mode at this time.


## Setup
1. Clone this repo
2. Copy ```local.env.dist``` to ```local.env``` and update values as appropriate
3. Setup environment variable for ```DOCKER_UIDGID``` in the format of ```"uid:gid"```.
   This will run some of the containers as you so that they can write to your host filesystem
   and the file permissions will be owned you.
4. Setup environment variable for ```COMPOSER_CONFIG_FILE``` with the full system path
   to your composer config.json file, for example: ```/home/my/.composer/config.json```. 
   This will allow the composer container to use your github auth token when pulling dependencies.
5. Copy ```application/common/config/local.php.dist``` to ```application/common/config/local.php```
   and update with appropriate settings
6. Follow operating system specific steps below
7. You should be able to access the API using a REST client or your browser
   at http://idp-pw-api.local:8080.
8. You'll probably also want the web interface for this application which you can 
   clone at ```link coming soon```

### Additional setup for Linux
1. Add entry to ```/etc/hosts``` for ```120.0.0.1 idp-pw-api.local```
2. Run ```docker build -t idp-pw-api .```
3. Run ```make start```

### Additional setup for Mac
1. Get IP address for your default docker-machine env ```docker-machine ip default```
   and add entry to ```/etc/hosts``` for ```<docker machine ip> idp-pw-api.local```
2. Run ```docker build -t idp-pw-api .```
3. Run ```make start```

### Additional setup for Windows
1. Add entry to ```c:\windows\system32\drivers\etc\hosts``` for 
   ```192.168.37.37 idp-pw-api.local```
2. Run ```vagrant up```
3. In order to run docker commands directly, SSH into the vagrant box ```vagrant ssh```
   change to /vagrant folder ```cd /vagrant``` and run ```make start```
   
### Makefile script aliases
To simplify common tasks there is a Makefile in place. The most common tasks will likely be:

- ```make start``` - Does what is needed to get API server online
- ```make test``` - Does cleanup and restart of test instances and runs unit tests
- ```make clean``` - Remove all containers
- ```make composerupdate``` - ```make start``` will run a ```composer install```, but to update composer
    you need to run ```make composerupdate```