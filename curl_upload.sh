#!/bin/bash

#
# This script is a simple helper script to upload files to
# phtagr with curl via phtagr's webdav interface
#
# Use curl_upload.sh -u YOURPHTAGR -l USERNAME *.JPG
# E.g. ./curl_upload.sh -u http://demo.phtagr.org -l demo *.JPG
#
# See curl_upload.sh -h for more details
#
# (c) 2012 by xemle@phtagr.org
#

SCRIPT=$0
VERBOSE=1

# Set following defaults here
#   URL of phtagr instance (without webdav part)
URL=
#   Default directory to upload
DIR=$(date +%F)
#   Username
USER=
#   Password
PASS=

help() {
  echo -e "$(basename $SCRIPT) file
\t-u, --url URL
\t\tURL of phtagr
\t-d, --dir DIRECTORY
\t\tDirectory to updload files. By default it is the current date
\t\tin YYYY-MM-DD format.
\t-l, --login USERNAME
\t-p, --pass PASSWORD"
}

verbose() {
  if [ "$VERBOSE" -gt 0 ]; then
    echo $@
  fi
}

mkcol() {
  local _DIR=$1
  local _PARENT=$(dirname $_DIR)
  if [ -n "$_PARENT" -a "$_PARENT" != "." ]; then
    mkcol "$_PARENT"
  fi
  verbose "Create directory $_DIR"
  curl -c cookie.txt --digest --user "$USER:$PASS" -X MKCOL "$URL/webdav/$_DIR"
}

upload() {
  local _DIR=$1
  local _FILE=$2
  local _FILENAME=$(basename $_FILE)
  if [ ! -e "$_FILE" ]; then
    echo "File is missing"
  fi
  verbose "Upload $_FILENAME to $_DIR"
  curl -c cookie.txt --digest --user "$USER:$PASS" -T "$_FILE" "$URL/webdav/$_DIR/$_FILENAME"
}

readpass() {
  stty -echo
  read -p "Password: " PASS
  echo
  stty echo
}

DIR=$(date +%F)
READ=1
while [ -n "$1" -a "$READ" -gt 0 ]; do
  case "$1" in
    -u|--url) URL=$2; ;;
    -d|--dir) DIR=$2; ;;
    -l|--login) USER=$2; ;;
    -p|--pass) PASS=$2; ;;
    -h) help; exit 0; ;;
    *) READ=0; ;;
  esac
  if [ "$READ" -gt 0 ]; then
    shift; shift
  fi
done

if [ -z "$URL" ]; then
  echo "URL is missing"; help; exit 1
fi
if [ -z "$DIR" ]; then
  echo "DIR is missing"; help; exit 1
fi
if [ -z "$USER" ]; then
  echo "USER is missing"; help; exit 1
fi

if [ -z "$PASS" ]; then
  readpass
fi
mkcol "$DIR"
while [ -n "$1" ]; do
  upload "$DIR" "$1"
  shift
done
