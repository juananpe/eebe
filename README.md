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
