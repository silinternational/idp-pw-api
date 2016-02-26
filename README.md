# idp-pw-api
Backend API for Identity Provider Password Management

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
3. Follow operating system specific steps below
4. You should be able to access the API using a REST client or your browser
   at http://idp-pw-api.local:8080.
5. You'll probably also want the web interface for this application which you can 
   clone at ```link coming soon```

### Additional setup for Linux
1. Add entry to ```/etc/hosts``` for ```120.0.0.1 idp-pw-api.local```
2. Run ```docker build -t insite-pw-api .```
3. Run ```docker-compose up -d```

### Additional setup for Mac
1. Get IP address for your default docker-machine env ```docker-machine ip default```
   and add entry to ```/etc/hosts``` for ```<docker machine ip> idp-pw-api.local```
2. Run ```docker build -t insite-pw-api .```
3. Run ```docker-compose up -d```

### Additional setup for Windows
1. Add entry to ```c:\windows\system32\drivers\etc\hosts``` for 
   ```192.168.37.37 idp-pw-api.local```
2. Run ```vagrant up```
3. In order to run docker commands directly, SSH into the vagrant box ```vagrant ssh```
   change to /vagrant folder ```cd /vagrant``` and run ```docker <cmd>```
   
