FROM silintl/openldap

RUN yum install -y nc
    
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