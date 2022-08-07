<?php 
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: useropt.php3,v 1.4 2000/07/18 21:51:20 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
          ,"auth" => "bk_cr_auth"
          ,"perm" => "bk_perm"
          ,"user" => "bk_user"));

$auth->login();

$tpl->set_file(array(
 standard   => "common.standard.tpl",
 body       => "useropt.body.tpl"
));

set_standard("user preferences", &$tpl);

include(LIBDIR . "bkend.inc");
?>
