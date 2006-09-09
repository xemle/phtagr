#!/bin/sh

CMD=mysql
USER=phtagr
HOST=localhost
DB=phtagr
PASSWD=phtagr
DB_PREFIX=

sql_query()
{
  $CMD -h "$HOST" -u "$USER" --password="$PASSWD" "$DB" -e "$@"
}

sql_query "ALTER TABLE ${DB_PREFIX}user CHANGE password password VARCHAR(32) NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}user CHANGE lastname lastname VARCHAR(32) NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}user CHANGE created created DATETIME NOT NULL DEFAULT NOW()"
sql_query "ALTER TABLE ${DB_PREFIX}user ADD quota_max INT"

sql_query "ALTER TABLE ${DB_PREFIX}group CHANGE userid owner INT NOT NULL"

sql_query "ALTER TABLE ${DB_PREFIX}usergroup CHANGE userid userid INT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}usergroup CHANGE groupid groupid INT NOT NULL"

sql_query "ALTER TABLE ${DB_PREFIX}image CHANGE lastview lastview DATETIME NOT NULL DEFAULT '2006-01-08 11:00:00'"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD voting    FLOAT DEFAULT 0.0"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD votes     INT   DEFAULT 0"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD INDEX voting"

sql_query "ALTER TABLE ${DB_PREFIX}image ADD latitude  INT"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD longitude INT"

sql_query "ALTER TABLE ${DB_PREFIX}imagetag CHANGE imageid imageid INT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}imagetag CHANGE tagid tagid INT NOT NULL"

sql_query "ALTER TABLE ${DB_PREFIX}imageset CHANGE imageid imageid INT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}imageset CHANGE setid setid INT NOT NULL"

sql_query "ALTER TABLE ${DB_PREFIX}imagelocation CHANGE imageid imageid INT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}imagelocation CHANGE locationid locationid INT NOT NULL"

sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE user name VARCHAR(32) NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE url url VARCHAR(128) NOT NULL DEFAULT ''"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE email email VARCHAR(64) NOT NULL DEFAULT ''"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE data data DATETIME NOT NULL DEFAULT NOW()"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE comment comment TEXT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}comment ADD userid  INT DEFAULT 0"

