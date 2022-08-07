<?php 
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: list.php3,v 1.25 2000/10/08 22:47:05 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
          ,"auth" => "bk_cr_auth"
          ,"perm" => "bk_perm"
          ,"user" => "bk_user"));
include(LIBDIR . "plist.inc");

$tpl->set_file(array(
  standard   => "common.standard.tpl",
  body       => "list.body.tpl",
  first      => "list.first.tpl",
  prev       => "list.prev.tpl",
  next       => "list.next.tpl",
  last       => "list.last.tpl"
));

# get/set the $user_last_page as a user variable.
# we use this to keep the last page nbr that the user
# was looking at so we can default in the future.
if (isset($user))
  $user->register("user_last_page");

$total_public = 0;
if ($auth->auth["include_public"] == 'Y'
||  $auth->is_nobody() ) {
  # need to find out how many public bookmarks exist from
  # users other than this user. need this to get an accurate
  # total of bookmarks being displayed by this page.
  $db = new bk_db;
  $query = sprintf("select sum(total_public_bookmarks) as total_public from auth_user where username != '%s'", 
    ($auth->is_nobody()?"":$auth->auth["uname"]) );
  $db->query($query);
  if ($db->Errno == 0) {
    if ($db->next_record()) $total_public = $db->f("total_public");
  }
}
$bmark = new bmark;
$total_bookmarks = $total_public + $bmark->getUserTotalBookmarks();

$tpl->set_var(array(
  TOTAL_BOOKMARKS  => $total_bookmarks,
  IMAGE_URL_PREFIX => $bookmarker->image_url_prefix,
  IMAGE_EXT        => $bookmarker->image_ext
));

# get the user defined nbr of bookmarks per page
# the local admin can set this to 0 if the database
# doesn't support the use of the "LIMIT offset, nbr"
# statement.
$limit = $bookmarker->urls_per_page;

# the first page is page one
$first_page = 1;

# calculate the page number of the last page
# (divide and round UP)
if ( $limit > 0 ) {
  $last_page = ceil($total_bookmarks / $limit);
} else {
  $last_page = $first_page;
}

# if page specified in URL, then use it
if ( $page > 0 ) {

# otherwise try and bring up the last page
# this user looked at.
} elseif ( $user_last_page > 0 && $user_last_page <= $last_page ) {
  $page = $user_last_page;

# as a last resort, start at page 1
} else {
  $page = 1;
}

# if page greater than one then set first and prev page stuff
if ( $page > 1 ) {
  $first_url = $sess->url(sprintf("%s?page=%s", "list.php3", $first_page));
  $tpl->set_var(FIRST_URL, $first_url);
  $tpl->parse(FIRST_LINK, "first");

  $prev_page = $page - 1;
  $prev_url = $sess->url(sprintf("%s?page=%s", "list.php3", $prev_page));
  $tpl->set_var(PREV_URL, $prev_url);
  $tpl->parse(PREV_LINK, "prev");

# otherwise prev page stuff is null
} else {
  unset($prev_page);
}

$tpl->set_var(PAGE_NBR, $page);
$tpl->set_var(TOTAL_PAGES, $last_page);

# calculate the row offset (what row number do
# we start printing for this page)
$offset = ( ($page - 1) * $limit ) + $bk_db_callout->db_first_row_offset;

# if we are on the last page, set the limit to
# the max so that we can be sure we get everything
if ($page < $last_page ) {
  $last_url = $sess->url(sprintf("%s?page=%s", "list.php3", $last_page));
  $tpl->set_var(LAST_URL, $last_url);
  $tpl->parse(LAST_LINK, "last");
  
  $next_page = $page + 1;
  $next_url = $sess->url(sprintf("%s?page=%s", "list.php3", $next_page));
  $tpl->set_var(NEXT_URL, $next_url);
  $tpl->parse(NEXT_LINK, "next");
} else {
  unset($next_page);
  $limit = $total_bookmarks;
}

# store the last page this user looked at in
# a PHPLIB user var.
$user_last_page = $page;

print_list ($where_clause, $limit, $offset, sprintf("list.php3?page=%s", $page) , &$bookmark_list, &$error_msg);

$tpl->set_var(BOOKMARK_LIST, $bookmark_list);

set_standard("list ($page of $last_page)", &$tpl);

include(LIBDIR . "bkend.inc");
?>
