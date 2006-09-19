#!/bin/sh

CMD=mysql
DUMP=mysqldump
USER=phtagr
HOST=phtagr
DB=phtagr
PASSWD=
DB_PREFIX=
VERSION=102

SCRIPT=$0

print_help()
{
  echo "Usage $SCRIPT [-h HOST] [-u USER] [-p PASSWD] [-pf DBPREFIX] [DATABASE]"
}

sql_query()
{
  $CMD -h "$HOST" -u "$USER" --password="$PASSWD" "$DB" -e "$@"
}

while [ -n "$1" ]; do
  case "$1" in 
  -h)
    shift;
    HOST=$1;
    ;;
  -u)
    shift;
    USER=$1;
    ;;
  -p)
    shift;
    PASSWD=$1;
    ;;
  -pf)
    shift
    DB_PREFIX=$1;
    ;;
  --help)
    print_help;
    exit 0;
    ;;
  *)
    DB=$1
    ;;
  esac
  shift;
done

if [ -z "$PASSWD" ]; then
  echo "Password is missing!"
  print_help;
  exit 1;
fi;

echo "Upgrade database to SVN version $VERSION:"

BACKUP=backup-`date +%FT%T`.sql
echo "Backup database to $BACKUP ..."
$DUMP -h "$HOST" -u "$USER" --password="$PASSWD" "$DB" > "$BACKUP"

echo "Upgrade of table ${DB_PREFIX}user ..."
sql_query "ALTER TABLE ${DB_PREFIX}user CHANGE password password VARCHAR(32) NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}user CHANGE lastname lastname VARCHAR(32) NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}user CHANGE created created DATETIME NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}user ADD quota_max INT"

echo "Upgrade of table ${DB_PREFIX}groups ..."
sql_query "ALTER TABLE ${DB_PREFIX}groups CHANGE userid owner INT NOT NULL"

echo "Upgrade of table ${DB_PREFIX}usergroup ..."
sql_query "ALTER TABLE ${DB_PREFIX}usergroup CHANGE userid userid INT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}usergroup CHANGE groupid groupid INT NOT NULL"

echo "Upgrade of table ${DB_PREFIX}image ..."
sql_query "ALTER TABLE ${DB_PREFIX}image CHANGE lastview lastview DATETIME NOT NULL DEFAULT '2006-01-08 11:00:00'"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD voting    FLOAT DEFAULT 0.0"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD votes     INT   DEFAULT 0"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD latitude  INT"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD longitude INT"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD INDEX (voting)"

echo "Upgrade of table ${DB_PREFIX}imagetag ..."
sql_query "ALTER TABLE ${DB_PREFIX}imagetag CHANGE imageid imageid INT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}imagetag CHANGE tagid tagid INT NOT NULL"

echo "Upgrade of table ${DB_PREFIX}imageset ..."
sql_query "ALTER TABLE ${DB_PREFIX}imageset CHANGE imageid imageid INT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}imageset CHANGE setid setid INT NOT NULL"

echo "Upgrade of table ${DB_PREFIX}imagelocation ..."
sql_query "ALTER TABLE ${DB_PREFIX}imagelocation CHANGE imageid imageid INT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}imagelocation CHANGE locationid locationid INT NOT NULL"

echo "Upgrade of table ${DB_PREFIX}comment ..."
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE user name VARCHAR(32) NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE url url VARCHAR(128) NOT NULL DEFAULT ''"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE email email VARCHAR(64) NOT NULL DEFAULT ''"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE date date DATETIME NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE comment comment TEXT NOT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}comment ADD userid  INT DEFAULT 0"

echo "Done."
