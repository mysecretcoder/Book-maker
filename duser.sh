#!/bin/sh
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: duser.sh,v 1.1 1999/10/29 21:04:27 prenagha Exp $
# ---------------------------------------------------------------
#
# duser
#       This shell script deletes a user and the user's data
#       from the database.
MYSQL=/usr/bin/mysql
DB=bookmarks
USER=test
PASS=test
TBLS="category rating subcategory search bookmark auth_user"

for tbl in $TBLS; do
  q="delete from $tbl where username = '$1';"
  echo "$q ..."
  echo $q | $MYSQL $DB -u $USER -p$PASS
done
exit 0
