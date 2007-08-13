#!/bin/bash
#
# Rename indices to cake convention
#

CMD=mysql
DUMP=mysqldump
USER=phtagr
HOST=localhost
DB=phtagr
PASSWD=
DB_PREFIX=
VERSION=218

SCRIPT=$0
LOG=$SCRIPT.log

print_help()
{
  echo "phTagr Database Updater for svn version $VERSION"
  echo "Usage $SCRIPT [-h HOST] [-u USER] [-pf DBPREFIX] [DATABASE] -p PASSWD"
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
  echo "$@" >> "$SCRIPT.log"
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

# Drop 
echo -n "Drop table and fields. "
sql_query "DROP TABLE ${DB_PREFIX}messages"
sql_query "ALTER TABLE ${DB_PREFIX}users DROP updated"
sql_query "ALTER TABLE ${DB_PREFIX}configs DROP INDEX userid"
echo "Done."

# Add fields
echo -n "Add fields. "
sql_query "ALTER TABLE ${DB_PREFIX}users ADD modified DATETIME"
sql_query "ALTER TABLE ${DB_PREFIX}comments ADD modified DATETIME"
echo "Done."

# Rename tables
echo -n "Rename tables. "
sql_query "ALTER TABLE ${DB_PREFIX}sets RENAME ${DB_PREFIX}categories"
sql_query "ALTER TABLE ${DB_PREFIX}imagetag RENAME ${DB_PREFIX}images_tags"
sql_query "ALTER TABLE ${DB_PREFIX}imageset RENAME ${DB_PREFIX}categories_images"
sql_query "ALTER TABLE ${DB_PREFIX}imagelocation RENAME ${DB_PREFIX}images_locations"
sql_query "ALTER TABLE ${DB_PREFIX}usergroup RENAME ${DB_PREFIX}groups_users"
echo "Done."

# Rename indices
echo -n "Rename indices. "
sql_query "ALTER TABLE ${DB_PREFIX}categories_images CHANGE imageid image_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}categories_images CHANGE setid category_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}configs CHANGE userid user_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}comments CHANGE imageid image_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}comments CHANGE userid user_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}groups CHANGE owner user_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}groups_users CHANGE userid user_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}groups_users CHANGE groupid group_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}images CHANGE groupid group_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}images CHANGE userid user_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}images_locations CHANGE imageid image_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}images_locations CHANGE locationid location_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}images_tags CHANGE imageid image_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}images_tags CHANGE tagid tag_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}logs CHANGE imageid image_id INT"
sql_query "ALTER TABLE ${DB_PREFIX}logs CHANGE userid user_id INT"
echo "Done."

# Rename 
echo -n "Rename fields. "
sql_query "ALTER TABLE ${DB_PREFIX}users CHANGE name username VARCHAR(32)"
sql_query "ALTER TABLE ${DB_PREFIX}users CHANGE type role TINYINT UNSIGNED"
echo "Done."


# Add index
echo -n "Add indices. "
sql_query "ALTER TABLE ${DB_PREFIX}configs ADD INDEX(user_id)"
echo -n "Done. "

echo -n "Upgrade database version... "
sql_query "DELETE FROM ${DB_PREFIX}configs WHERE name='db.version'"
sql_query "INSERT INTO ${DB_PREFIX}configs (user_id, name, value) VALUES (0, 'db.version', '5')"
echo "Done."

echo "See $LOG for details"
