#!/bin/bash

set -e

mkdir -p /var/lib/ldap/accesslog
chown ldap:ldap /var/lib/ldap/accesslog  

chown ldap:ldap /etc/openldap/certs/idp.pw.api.*
chmod 600 /etc/openldap/certs/idp.pw.api.*

exec /usr/sbin/slapd -h "ldap:/// ldaps:/// ldapi:///" -u ldap -d $DEBUG_LEVEL -f /etc/openldap/slapd.conf
