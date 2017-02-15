# Upgrading Amaysim Patched Cerb.ai 7.2.2 to 7.3.1 
  
Amaysim is maintaining a custom(patched) version of Cerb.ai and has a fork of the source code at http://github.com/amaysim/cerb

The custom code patches has now been applied to Amaysim Fork repository and is ready for roll out., also the attributes are already set in the repository to perform the instalation of 7.3.1 (Patched) on top of 7.2.2

Provisioning is provided via Chef, cookbooks, recipes and artifacts at : http://stash.amaysim.....

### Upgrade
 
The first environment to be upgraded and tested should be DEV(Battlefield).

- Backup Data (Check with DevOps their preferable method).
    (Add description here)
- SSH to battlefield/DEV
- Become root and run Chef Client 
```
user@host $> sudo -i

user@host #> chef-client
```

- Access Cerb URL: http://battlefield....au, Follow the Instructions and Cerb will detect the changes and trigger the update routine. If you come across an Apache/NGINX error page, just reload the page and it will continue with no issues. This happened in 3 out of 10.


### Rollback
- Update Chef attributes file (Add URL here) and set app.cerb.version to 7.2.2
- SSH to battlefield/DEV
- Become root and run Chef Client 
```
user@host $> sudo -i

user@host #> chef-client
```
- Restore database backup 

