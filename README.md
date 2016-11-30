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
1. Docker for Mac Beta

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
   and the file permissions will be owned by you.
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

### Additional setup for Linux & Mac
1. Add entry to ```/etc/hosts``` for ```120.0.0.1 idp-pw-api.local```
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

## Component Architecture
With the goal of being reusable, this application is developed with a component based architecture that allows swapping out specific components to suit your needs. All components must implement common interfaces to support this and new components can be developed to implement the interface as needed.

### Common Interfaces and Classes
The interfaces for components as well as some common classes are maintained in the [idp-pw-api-common](https://github.com/silinternational/idp-pw-api-common) repository.

### Configuration
All components must extend from [\yii\base\Component](http://www.yiiframework.com/doc-2.0/guide-structure-application-components.html) so that they can be configured in the ```components``` section of the application configuration. This also allows them to be accessed via ```\Yii::$app->componentId```. While each component has a defind interface for methods to implement, what properties it needs for configuration are up to each implementation as appropriate. See [our common/config/local.php.dist](https://github.com/silinternational/idp-pw-api/blob/develop/application/common/config/local.php.dist) for examples of configurations. 

### Authentication Component
We use SAML for authentication but this component can be replaced to support whatever method is needed. For example an auth component could be written to implement OAuth or use Google, etc. 

* Component ID: ```auth```
* Implement interface: ```Sil\IdpPw\Common\Auth\AuthnInterface```
* Example implementation: [idp-pw-api-auth-saml](https://github.com/silinternational/idp-pw-api-auth-saml)

### Password Store Component
You can store your passwords wherever you like, whether it is LDAP, Active Directory, a database, or even Redis. 

* Component ID: ```passwordstore```
* Implement interface: ```Sil\IdpPw\Common\PasswordStore\PasswordStoreInterface```
* Example implementation: [idp-pw-api-passwordstore-ldap](https://github.com/silinternational/idp-pw-api-passwordstore-ldap)

### Personnel Component
The personnel component is used to look up informaton about users from your companies personnel system. This includes verifying that they are an active employee, getting information about them like name, email, employee id, if they have a supervisor and what their supervisors email address is and if the personnel system is aware of spouses it can also provide the spouse's email address.

* Component ID: ```personnel```
* Implement interface: ```Sil\IdpPw\Common\Personnel\PersonnelInterface```
* Example implementation: [idp-pw-api-personnel-insite](https://github.com/silinternational/idp-pw-api-personnel-insite)

### Phone Verification Component
This component is used for performing phone based verification of users. 

* Component ID: ```phone```
* Implement interface: ```Sil\IdpPw\Common\PhoneVerification\PhoneVerificationInterface```
* Example implementation: [idp-pw-api-phoneverification-nexmo](https://github.com/silinternational/idp-pw-api-phoneverification-nexmo)

The Nexmo implementation supports using either Nexmo Verify or Nexmo SMS services. Nexmo Verify can send SMS messages or make phone calls so it is nice when your users may or may not understand text messaging. 
