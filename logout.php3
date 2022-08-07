<?php 
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: logout.php3,v 1.9 2000/10/08 22:47:05 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
          ,"auth" => "bk_cr_auth"
          ,"perm" => "bk_perm"
          ,"user" => "bk_user"));
          
# kill the perm_auth_cookie if one exists
SetCookie("bookmarker_perm_auth", "", 0, "/", $sess->$cookie_domain);

# kill the auth. this will force user
# to re authenticate.
$auth->unauth();

# set msg and send user back to start page.
$sess_msg .= "<br>You have been logged out of bookmarker.";

# reset this session var so total is recalcled if/when
# user logs back in under this session.
$sess->unregister('user_total_bookmarks');

header(sprintf("Location: index.php3"));
page_close();
?>
