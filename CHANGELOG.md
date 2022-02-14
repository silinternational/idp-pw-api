# Change Log
All notable changes to this project will (in theory) be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [Unreleased]

## [6.0.2]
### Fixed
- Remove redundant error log entry in /site/system-status

## [6.0.1]
### Fixed
- Don't send email alerts for /site/system-status errors that are likely transient

## [6.0.0]
### Changed
- Updated integrations with id-broker to support WebAuthn interactions rather than legacy U2F

## [5.5.3]
### Added
- Migration to increase length of user.employee_id to 255 

## [5.5.2]
### Fixed
- Upgrade PHP to 7.4 again (but stay with Ubuntu base O/S)
- Upgrade Ubuntu to 20.04
- Upgrade Composer to 2
- Switch from fxp's composer-asset-plugin to asset-packagist
- Stop including syslog and Logentries-related files
- Stop installing/using runny (since we don't log to syslog anymore)

## [5.5.1]
### Changed
- Reverse the order of searching for a Google user, now use ID first
- Downgraded PHP back to 7.2

## [5.5.0]
### Added
- In Google adapter, search by email as well as by external ID
### Changed
- Upgraded PHP to 7.4

## [5.4.0]
### Added
- Allow LDAP host name to be a list of hostname strings as well as a single string for backward compatibility

## [5.3.4]
### Fixed
- Improved handling of expired session on login

## [5.3.3]
### Fixed
- Updated dependencies
- Rotated credentials for CI/CD

## [5.3.2]
### Fixed
- Changed Docker credentials

## [5.3.1]
### Changed
- No caching of log messages. Helps to ensure messages are sent to log in the event
  of a crash, e.g. out of memory situation.

## [5.3.0]
### Changed
- Changed logging from syslog to stdout/stderr

## [5.2.2]
### Changed
- SAML2 security update

## [5.2.1]
### Changed
- Updated dependencies
- Removed obsolete code related to recovery methods

## [5.2.0]
### Changed
- Attempt to set password in every passwordStore component even if one fails.
### Added
- Add configurable option in Google passwordStore to look up user by employee id

## [5.1.0] - 2019-05-29
### Added
- Use `supportName` and `supportEmail` if `helpCenterUrl` is not provided
- Allow multi-line email signature

## [5.0.0] - 2019-05-10
### Added
- Use memcache for session storage

## [4.1.0] - 2019-05-06
### Fixed
- Mask subdomains in email addresses correctly
### Changed
- Renamed `fromName` (`FROM_NAME`) to `emailSignature` (`EMAIL_SIGNATURE`)
- Use `display_name` personnel property in email templates.
### Removed
- Removed ReCAPTCHA from front-end config data

## [4.0.0] - 2019-04-11
### Added
- Integrated Personnel\IdBroker component library code
- Integrated PasswordStore components library code
- Added "Hide" feature for users with increased privacy concerns
- Added `PUT /mfa/{mfaId}` endpoint to update MFA labels.
- Added `invite` option on `/auth/login` for new user invite authentication
- Added `last_login` to `GET /user/me` response.
- Added password validation to prohibit passwords disclosed in breaches
  and those given in a password help video. 
### Changed
- Limit access based on whether auth level is reset or login
- Updated Adldap2 to latest version
- Moved password recovery method storage to [ID Broker][idp-id-broker]
- Changed password reuse error response code from 400 to 409
- Changed expired method verification response code from 400 to 410
- /auth/login returns 400 for client_id missing, instead of 302
- Added `uuid` property to `/user/me` response
- /method/{uid}/verify no longer requires authentication
- Validation attempt on expired reset now issues a new reset
- Password change now clears out the auth token if `auth_type` is reset
- Only provide manager password recovery method if the user
  has not added and verified others.
- /mfa/{id}/verify returns the verified mfa object
### Removed
- Removed support for phone password recovery methods
- Removed option to use local emailer. External email service is now required.
- Removed spouse_email from user model and from password recovery. 
- Removed cron controller and container
### Fixed
- Password reset is now blocked for a locked account
- The response to /user/me would have incorrect password metadata in a new user scenario.
- Password expiration was reported incorrectly when setting a new password for a
  user with mfa enabled.

## [3.0.0] - 2018-07-31
### Added
- Added [ID Broker][idp-id-broker] support for manager and spouse email fields
### Removed
- Removed support for Insite and Multiple personnel adapters

## [2.2.0] - 2018-05-07
### Changed
- Updated Yii2 and SAML2

## [2.1.4] - 2018-01-09
### Changed
- Stop sending alerts for password validation errors

## [2.1.3] - 2017-12-14
### Changed
- Don't unnecessarily change reset code

## [2.1.2] - 2017-12-09
### Changed
- Updated LDAP password store

## [2.1.1] - 2017-12-09
### Changed
- Updated LDAP password store

## [2.1.0] - 2017-12-08
### Added
- Updated LDAP password store

## [2.0.1] - 2017-12-07
### Changed
- Password reuse error fix

## [2.0.0] - 2017-11-27
### Added
- Added support for 2-Step Verification (Multi-Factor Authentication or MFA)

## [1.0.0] - 2017-08-30
### Added
- Initial version of Password Manager Backend.

[Unreleased]: https://github.com/silinternational/idp-pw-api/compare/6.0.2...HEAD
[6.0.2]: https://github.com/silinternational/idp-pw-api/compare/6.0.1...6.0.2
[6.0.1]: https://github.com/silinternational/idp-pw-api/compare/6.0.0...6.0.1
[6.0.0]: https://github.com/silinternational/idp-pw-api/compare/5.5.3...6.0.0
[5.5.3]: https://github.com/silinternational/idp-pw-api/compare/5.5.2...5.5.3
[5.5.2]: https://github.com/silinternational/idp-pw-api/compare/5.5.1...5.5.2
[5.5.1]: https://github.com/silinternational/idp-pw-api/compare/5.5.0...5.5.1
[5.5.0]: https://github.com/silinternational/idp-pw-api/compare/5.4.0...5.5.0
[5.4.0]: https://github.com/silinternational/idp-pw-api/compare/5.3.4...5.4.0
[5.3.4]: https://github.com/silinternational/idp-pw-api/compare/5.3.3...5.3.4
[5.3.3]: https://github.com/silinternational/idp-pw-api/compare/5.3.2...5.3.3
[5.3.2]: https://github.com/silinternational/idp-pw-api/compare/5.3.1...5.3.2
[5.3.1]: https://github.com/silinternational/idp-pw-api/compare/5.3.0...5.3.1
[5.3.0]: https://github.com/silinternational/idp-pw-api/compare/5.2.2...5.3.0
[5.2.2]: https://github.com/silinternational/idp-pw-api/compare/5.2.1...5.2.2
[5.2.1]: https://github.com/silinternational/idp-pw-api/compare/5.2.0...5.2.1
[5.2.0]: https://github.com/silinternational/idp-pw-api/compare/5.1.0...5.2.0
[5.1.0]: https://github.com/silinternational/idp-pw-api/compare/5.0.0...5.1.0
[5.0.0]: https://github.com/silinternational/idp-pw-api/compare/4.1.0...5.0.0
[4.1.0]: https://github.com/silinternational/idp-pw-api/compare/4.0.0...4.1.0
[4.0.0]: https://github.com/silinternational/idp-pw-api/compare/3.0.0...4.0.0
[3.0.0]: https://github.com/silinternational/idp-pw-api/compare/2.2.0...3.0.0
[2.2.0]: https://github.com/silinternational/idp-pw-api/compare/2.1.4...2.2.0
[2.1.4]: https://github.com/silinternational/idp-pw-api/compare/2.1.3...2.1.4
[2.1.3]: https://github.com/silinternational/idp-pw-api/compare/2.1.2...2.1.3
[2.1.2]: https://github.com/silinternational/idp-pw-api/compare/2.1.1...2.1.2
[2.1.1]: https://github.com/silinternational/idp-pw-api/compare/2.1.0...2.1.1
[2.1.0]: https://github.com/silinternational/idp-pw-api/compare/2.0.1...2.1.0
[2.0.1]: https://github.com/silinternational/idp-pw-api/compare/2.0.0...2.0.1
[2.0.0]: https://github.com/silinternational/idp-pw-api/compare/1.0.0...2.0.0
[1.0.0]: https://github.com/silinternational/idp-pw-api/commit/1a833338e2995634934e9b9801f0456ec21ba9b2
[idp-id-broker]: https://github.com/silinternational/idp-id-broker
