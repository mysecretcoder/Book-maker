<?php
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: create.php3,v 1.30 2000/07/18 21:51:20 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
                ,"auth" => "bk_cr_auth"
                ,"perm" => "bk_perm"
                ,"user" => "bk_user"));

$auth->login();

$tpl->set_file(array(
  standard            => "common.standard.tpl",
  msie_js             => "common.msie_js.tpl",
  body                => "create.body.tpl",
  possible_dup        => "create.possible_dup.tpl",
  possible_dup_lines  => "create.possible_dup.line.tpl"
));

set_standard("create", &$tpl);

# if browser is MSIE, then need to add this bit
# of javascript to the page so that MSIE correctly
# brings quik-mark and mail-this-link popups to the front.
if (check_browser() == "MSIE") {
  $tpl->parse(MSIE_JS, "msie_js");
}

## initialize variable that holds id of newly created bookmark
$id = 0;

### Submit Handler
### Get a database connection
$db    = new bk_db;
$bmark = new bmark;


## Check if there was a submission
while ( is_array($HTTP_POST_VARS)
     && list($key, $val) = each($HTTP_POST_VARS)) {
  switch ($key) {

  ## Create a new bookmark
  case "bk_create_x":
  case "bk_create":

    if(!$bmark->add(&$id, $url, $name, $desc, $keyw, $category, $subcategory, 
                         $rating, $public)) break;
    break;

  default:
    break;
 }
}

# if dislpaying b/c of error, show previous data
if ($url > "") {
  $default_url = $url;
# otherwise default from URL if available
} elseif ($curl > "") {
  $default_url = $curl;
# otherwise just default to http://
} else {
  $default_url = "http://";
}

# if dislpaying b/c of error, show previous data
if ($name > "") {
  $default_name = $name;
# otherwise default from URL if available
} elseif ($ctitle > "") {
  $default_name = $ctitle;
}

# if dislpaying b/c of error, show previous data
if ($desc > "") {
  $default_desc = $desc;
# otherwise default from URL if available
} elseif ($ctitle > "") {
  $default_desc = $ctitle;
}

# if dislpaying b/c of error, show previous data
if ($keyw > "") {
  $default_keyw = $keyw;
}

# if dislpaying b/c of error, show previous data
if ($category > 0) {
  $default_category = $category;
} else {
  $default_category = 0;
}

# if dislpaying b/c of error, show previous data
if ($subcategory > 0) {
  $default_subcategory = $subcategory;
} else {
  $default_subcategory = 0;
}

# if dislpaying b/c of error, show previous data
if ($rating > 0) {
  $default_rating = $rating;
} else {
  $default_rating = 0;
}

## Check to see if any existing bookmarks are a "close match".
## don't do this check after a save.
if ( $id == 0 && $default_url != "http://") {
  $db_dup   = new bk_db;

## the "close match" consists of looking for other URLs at the
## hostname that match the first $bookmarker->possible_dup_chars
## after the hostname.
  $url_elements = parse_url($default_url);
  $hostname  = $url_elements[host];
  $scheme    = $url_elements[scheme];
  $path_part = substr($url_elements[path], 0, $bookmarker->possible_dup_chars);
  $look_for = $scheme."://".$hostname.$path_part."%";

  $query = sprintf("select url, name from bookmark where url like '%s' and username = '%s'", $look_for, $auth->auth["uname"]);
  $db_dup->query($query);
  if ($db->Errno == 0) {
    while ($db_dup->next_record()){
      $tpl->set_var(array(
        DUP_URL            => $db_dup->f("url"),
        DUP_NAME           => htmlspecialchars(stripslashes($db_dup->f("name")))
      ));
      $tpl->parse(POSSIBLE_DUP_LINES, "possible_dup_lines", TRUE);
      $possible_dups_found = TRUE;
    }
    if ($possible_dups_found){
      $tpl->parse(POSSIBLE_DUP, "possible_dup");
    }
  }
}

load_ddlb("category", $default_category, &$category_select, FALSE);
load_ddlb("subcategory", $default_subcategory, &$subcategory_select, FALSE);
load_ddlb("rating", $default_rating, &$rating_select, FALSE);

# only default the public checkbox the first time the
# page is shown. after that, show the value that the
# user chose.
if ($id == 0) {
  if ($auth->auth["default_public"] == "Y") {
    $default_public = "CHECKED";
  }
} else {
  if ($public == "on") {
    $default_public = "CHECKED";
  }
}

$tpl->set_var(array(
  FORM_ACTION            => $sess->self_url(),
  DEFAULT_URL            => $default_url,
  DEFAULT_NAME           => htmlspecialchars(stripslashes($default_name)),
  DEFAULT_DESC           => htmlspecialchars(stripslashes($default_desc)),
  DEFAULT_KEYW           => htmlspecialchars(stripslashes($default_keyw)),
  DEFAULT_CATEGORY       => $default_category,
  DEFAULT_SUBCATEGORY    => $default_subcategory,
  DEFAULT_RATING         => $default_rating,
  CATEGORY_SELECT        => $category_select,
  SUBCATEGORY_SELECT     => $subcategory_select,
  RATING_SELECT          => $rating_select,
  DEFAULT_PUBLIC         => $default_public
));

include(LIBDIR . "bkend.inc");
?>
