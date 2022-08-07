<?php
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: faq.php3,v 1.8 2000/06/29 15:43:37 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess_cache"));

$tpl->set_file(array(
  standard   => "common.standard.tpl",
  body       => "faq.body.tpl",
  msie_qm    => "faq.msie.quik-mark.tpl",
  ns_qm      => "faq.ns.quik-mark.tpl",
  msie_ml    => "faq.msie.mail-this-link.tpl",
  ns_ml      => "faq.ns.mail-this-link.tpl"
));

set_standard("faq", &$tpl);

$tpl->set_var(array(
  CREATE_URL         => $bookmarker->create_url,
  MAIL_THIS_LINK_URL => $bookmarker->maillink_url,
  IMAGE_URL_PREFIX   => $bookmarker->image_url_prefix,
  USER_AGENT         => $HTTP_USER_AGENT
));

if (check_browser() == "MSIE") {
  $tpl->parse(QUIK_MARK_LINK, msie_qm);
  $tpl->parse(MAIL_THIS_LINK, msie_ml);
} else {
  $tpl->parse(QUIK_MARK_LINK, ns_qm);
  $tpl->parse(MAIL_THIS_LINK, ns_ml);
}

include(LIBDIR . "bkend.inc");
?>
