#!/bin/bash

CMD=mysql
DUMP=mysqldump
USER=phtagr
HOST=localhost
DB=phtagr
PASSWD=
DB_PREFIX=
VERSION=139

SCRIPT=$0

print_help()
{
  echo "phTagr Database Updater for svn version $VERSION"
  echo "Usage $SCRIPT [-h HOST] [-u USER] [-p PASSWD] [-pf DBPREFIX] [DATABASE]"
  echo "Defaults:
  HOST:     $HOST
  USER:     $USER
  DBPREFIX: $DB_PREFIX
  DATABASE: $DB"
}

sql_query()
{
  $CMD -h "$HOST" -u "$USER" --password="$PASSWD" "$DB" -e "$@"
  if [ "$?" -ne 0 ]; then 
    echo Error on Query "$@"
  fi
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

for t in user image tag location comment message; do
  echo -n "Renaming of table ${DB_PREFIX}$t to ${DB_PREFIX}${t}s... "
  sql_query "ALTER TABLE ${DB_PREFIX}$t RENAME ${DB_PREFIX}${t}s"
  echo "Done."
done

echo -n "Renaming of table ${DB_PREFIX}conf to ${DB_PREFIX}configs... "
sql_query "ALTER TABLE ${DB_PREFIX}conf RENAME ${DB_PREFIX}configs"
echo "Done."

echo -n "Create table ${DB_PREFIX}logs... "
sql_query "CREATE TABLE ${DB_PREFIX}logs (
        time          DATETIME,
        level         TINYINT,
        image         INT DEFAULT NULL,
        user          INT DEFAULT NULL,
        file          BLOB,
        line          INT,
        message       BLOB,

        INDEX (time),
        INDEX (level))"
echo "Done."

echo -n "Upgrade database version... "
sql_query "DELETE FROM ${DB_PREFIX}configs WHERE name='db.version'"
sql_query "INSERT INTO ${DB_PREFIX}configs (userid, name, value) VALUES (0, 'db.version', '2')"
echo "Done."
