#!/bin/bash
#
# the bookmark verification takes a while to run
# , I have 700 URLs.
# so I run this script via nohup to let it run in the
# background - it emails me when it is complete.
#
HTML="/path/vbk.htm"
ERR="/path/vbk.err"

rm -f $HTML $ERR 2>/dev/null

/path/vbk.pl 1>$HTML 2>$ERR

cat $ERR | /bin/mail -s "Bookmarker Verification Report Complete" user@domain

exit 0
