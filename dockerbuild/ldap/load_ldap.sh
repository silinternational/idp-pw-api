#!/bin/bash

# Simple replication of whenavail script
TIMEOUT=10;

while true; do
  if nc -z -v ldap 389; then
    break
  fi

  if [[ $TIMEOUT == 0 ]]; then
    exit 42
  else
    TIMEOUT=$(( $TIMEOUT-1 ))
  fi

  sleep 1
done


ldapadd -h ldap -p 389 -f /root/fakerep.ldif -x -c -D 'cn=Manager,dc=acme,dc=org' -w admin
ldapadd -h ldap -p 389 -f /root/fakepeople.ldif -x -c -D 'cn=Manager,dc=acme,dc=org' -w admin
