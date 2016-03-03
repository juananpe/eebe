# eebe
External Evidence Based Evaluation


# Installation

Install packrat in R
Open R from the eebe directory (the .RProfile script will execute packrat)

In order to use this Shiny application you will need to install RMySQL from source.
First, install mysql client, then mysql-connector-c

Check that libmysql is installed:

Try installing:
 * deb: libmysqlclient-dev | libmariadb-client-lgpl-dev (Debian)
        libmysqlclient-dev | libmariadbclient-dev (Ubuntu)
 * rpm: mariadb-devel | mysql-devel (Fedora, CentOS, RHEL)
 * csw: mysql56_dev (Solaris)
 * brew: mysql-connector-c (OSX)
 
Finally:
install.packages('RMySQL', type='source') 

Perhaps you will also need to set this sql_mode in mysql [for disabling only_full_group_by sql mode](http://mysqlserverteam.com/mysql-5-7-only_full_group_by-improved-recognizing-functional-dependencies-enabled-by-default/):


set global sql_mode='STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'
