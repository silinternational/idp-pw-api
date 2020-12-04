FROM centos:7

ENV DEBUG_LEVEL=256

RUN /usr/bin/yum update -y && /usr/bin/yum install -y openldap-servers openldap-clients nc

EXPOSE 389 636
CMD slapd -u ldap -g ldap -d3

COPY idp.pw.api.* /etc/openldap/certs/

COPY *.schema /etc/openldap/schema/
COPY gisGroup.ldif /etc/openldap/schema/
COPY gisPerson.ldif /etc/openldap/schema/

COPY slapd.conf /etc/openldap/

COPY run_ldap.sh /data/
COPY load_ldap.sh /data/
COPY manager.ldif /root/
COPY base.ldif /root/
COPY domain.ldif /root/
COPY fakepeople.ldif /root/
COPY fakerep.ldif /root/

CMD /data/run_ldap.sh
