#!/bin/bash

ldapadd -h ldap -p 389 -f /root/fakerep.ldif -x -c -D 'cn=Manager,dc=acme,dc=org' -w admin
ldapadd -h ldap -p 389 -f /root/fakepeople.ldif -x -c -D 'cn=Manager,dc=acme,dc=org' -w admin
