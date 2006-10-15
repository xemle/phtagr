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
sql_query "ALTER TABLE ${DB_PREFIX}user DROP fsroot"
sql_query "ALTER TABLE ${DB_PREFIX}user ADD expire DATETIME DEFAULT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}user ADD type TINYINT UNSIGNED"
sql_query "ALTER TABLE ${DB_PREFIX}user ADD cookie VARCHAR(64) DEFAULT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}user ADD cookie_expire DATETIME DEFAULT NULL"

echo "Upgrade of table ${DB_PREFIX}image..."
sql_query "ALTER TABLE ${DB_PREFIX}image ADD duration INT DEFAULT -1"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD hue FLOAT"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD saturation FLOAT"
sql_query "ALTER TABLE ${DB_PREFIX}image ADD luminosity FLOAT"

echo "Upgrade of table ${DB_PREFIX}comment..."
sql_query "ALTER TABLE ${DB_PREFIX}comment ADD id INT"
sql_query "ALTER TABLE ${DB_PREFIX}comment ADD PRIMARY KEY (id)"
sql_query "ALTER TABLE ${DB_PREFIX}comment CHANGE id id INT NOT NULL AUTO_INCREMENT"
sql_query "ALTER TABLE ${DB_PREFIX}comment ADD reply INT DEFAULT 0"
sql_query "ALTER TABLE ${DB_PREFIX}comment ADD auth VARCHAR(64) DEFAULT NULL"
sql_query "ALTER TABLE ${DB_PREFIX}comment ADD notify TINYINT UNSIGNED DEFAULT 0"

echo "Add table ${DB_PREFIX}message..."
sql_query "CREATE TABLE ${DB_PREFIX}message (
        id            INT NOT NULL AUTO_INCREMENT,
        fromid        INT NOT NULL,
        toid          INT NOT NULL,
        date          DATETIME NOT NULL,
        expire        DATETIME DEFAULT NULL,
        type          TINYINT UNSIGNED DEFAULT 0,
        private       BLOB,
        subject       VARCHAR(128),
        body          BLOB,

        INDEX (toid),
        PRIMARY KEY (id))"
echo "Done."
