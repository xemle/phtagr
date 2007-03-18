#!/bin/sh

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

echo "Upgrade of table ${DB_PREFIX}user ..."
sql_query "ALTER TABLE ${DB_PREFIX}user CHANGE quota_max qslice INT DEFAULT 0"
sql_query "ALTER TABLE ${DB_PREFIX}user CHANGE quota_interval qinterval INT DEFAULT 0"
sql_query "ALTER TABLE ${DB_PREFIX}user ADD creator INT DEFAULT 0"

echo -n "Upgrade of table ${DB_PREFIX}pref ..."
sql_query "ALTER TABLE ${DB_PREFIX}pref DROP groupid"
sql_query "ALTER TABLE ${DB_PREFIX}pref RENAME ${DB_PREFIX}conf"
echo " its now calling '${DB_PREFIX}conf'!"

echo "Done."
