<?php
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: index.php3,v 1.14 2000/07/18 21:51:20 prenagha Exp $
# ---------------------------------------------------------------
# test for proper PHP config
if (!isset($HTTP_GET_VARS)) {
  $phpE = "<br>Turn on track_vars in your php or apache config file.";
}
if (get_magic_quotes_gpc() == 1) {
  $phpE .= "<br>Turn magic_quotes_gpc OFF in your php or apache config file.";
}
if (get_magic_quotes_runtime() == 1) {
  $phpE .= "<br>Turn magic_quotes_runtime OFF in your php or apache config file.";
}
if (!empty($phpE)) {
  print("<html><head><title>bookmarker PHP Configuration Error</title></head><body
bgcolor=white><h1>bookmarker PHP Configuration Error</h1><p><font color=red><strong>$phpE</strong></font></body></html>");
  exit;
}

include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
  ,"auth" => "bk_cr_auth"
));

if ($login == "YES") {
  $auth->login();
}

$tpl->set_file(array(
 standard   => "common.standard.tpl",
 body       => "index.body.tpl"
));

set_standard("home", &$tpl);

include(LIBDIR . "bkend.inc");
?>
