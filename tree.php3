<?php 
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: tree.php3,v 1.27 2000/07/18 21:51:20 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess_cache"
      ,"auth" => "bk_cr_auth"
      ,"perm" => "bk_perm"
      ,"user" => "bk_user"));

# if mode is not GET, then we want to redirect
# back to ourselves which will put us in GET mode.
# The reason is the javascript reload() and go()
# functions give the annoying
# "the page cannot be refreshed without resending..."
# message.
# Note: we should only be here in POST mode if
# user clicked tree view without already being
# authenticated, then the login will do a POST back
# to itself - in this case the tree.php3 page.
if (strtoupper($REQUEST_METHOD) != "GET") {

# use a 303 since http spec states only a 303,
# not a 302 or 301 is allowed after a POST.
  header("Status: 303 See Other");

# netscape bug won't do redirect a page to itself
# after a POST, therefore we add the id string to the url.
  $url = ((isset($HTTPS)&&$HTTPS=='on')?"https":"http")
    ."://"
    .$HTTP_HOST
    .$sess->self_url()
    .(strstr($sess->self_url(),"?")?"&":"?")
    ."id="
    .time();
  header("Location: ".$url);
  exit;
}

$tpl->set_file(array(
 body       => "tree.body.tpl"
));

# we keep a user variable that holds the last selection
# the user made for the groupby option
$user->register("last_groupby");
  
# if no action, then show the same list as last time
# this page was viewed. the session start variables 
# should be set by the register function
$bk_c = new bk_db;

## Check if there was a submission
if (!empty($bks_load)) {
  ## if form submitted, then if groupby has no value it
  ## means the user deselected it, set it to FALSE
  if (!isset($groupby)) {
    $groupby = FALSE;
  }

  ## Do we have all necessary data?
  ## if search isn't greater than zero, then it is "NONE"
  ## or not defined, in this case reset the where clause vars
  ## and exit the loop. this will cause no where clause to
  ## be applied - in effect resetting the query to ALL.
  if ($search > 0 ) {
    ## get the saved search
    $query = sprintf("select query from search where id=%s and username='%s'"
      ,$search, ($auth->is_nobody()?"":$auth->auth["uname"]));
    $bk_c->query($query);
    if ($bk_c->Errno == 0) {
      if ($bk_c->next_record()){
        $saved_search = $bk_c->f("query");
      } else {
        $error_msg .= "Saved Search not found in database!";
      }
    }
  } else {
    unset ($saved_search);
    unset ($where);
  }
}

# if the groupby var is still not set by now, that means that
# this was not a form submission. in that case we default the
# groupby var to whatever the user last selected (stored in a 
# user variable). if the user variable is not set yet, default
# to groupby ON.
if (!isset($groupby)) {
  if (isset($last_groupby)) {
    $groupby = $last_groupby;
  } else {
    $groupby = TRUE;
  }
}
$last_groupby = $groupby;

# you can see/search anything that you own, and anything that others
# have marked as public if you have indicated so on your auth_user record.
if ($auth->auth["include_public"] == "Y"
||  $auth->is_nobody() )
  $public_sql = " or bookmark.public_f='Y' ";


$query = sprintf("select category.name as category_name, 
  subcategory.name as subcategory_name, bookmark.id, bookmark.url, 
  bookmark.name as bookmark_name, bookmark.ldesc 
  from bookmark, category, subcategory 
  where bookmark.category_id = category.id and category.username=bookmark.username 
    and bookmark.subcategory_id = subcategory.id 
    and subcategory.username=bookmark.username 
    and ( bookmark.username = '%s' %s )"
  , ($auth->is_nobody()?"":$auth->auth["uname"]), $public_sql);

# if saved search loaded then use it first
if (isset($saved_search)) {
  $query .= " and (" . $saved_search . ")";
  $filter_msg = "Filter " . htmlspecialchars($saved_search);
} elseif (isset($where)) {
# else if a WHERE clause was specified in the URL, then use it
  $query .= " and (" . base64_decode($where) . ")";
  $filter_msg = "Filter " . htmlspecialchars(base64_decode($where));
}

if ($groupby) {
  $query .= " order by category_name, subcategory_name, bookmark_name";
  $groupby_default = "checked";
} else {
  $query .= " order by bookmark.name, bookmark.url";
  $groupby_default = "";
}

if ($bookmarker->show_bk_in_tree && ($groupby || !(isset($groupby)))) {
  $output .= sprintf("dbAdd( true , \"bookmarker\" , \"\" , 0 , \"bk_app\" , 0, 0)\n");
  $output .= sprintf("dbAdd( false , \"start\" , \"index.php3\" , 1 , \"bk_app\" , 0, 0)\n");
  $output .= sprintf("dbAdd( false , \"plain list\" , \"list.php3\" , 1 , \"bk_app\" , 0, 0)\n");
  $output .= sprintf("dbAdd( false , \"create\" , \"create.php3\" , 1 , \"bk_app\" , 0, 0)\n");
  $output .= sprintf("dbAdd( false , \"search\" , \"search.php3\" , 1 , \"bk_app\" , 0, 0)\n");
}

$bk_c->query($query);
if ($bk_c->Errno == 0) {

  $prev_category = " ";
  $prev_subcategory = " ";
  $first_time = 1;

  while ($bk_c->next_record()) {

  # only do the category subcategory breaks if the user wants them
    if ($groupby) {
       $category_break = 0;
      $subcategory_break = 0;

      if ($bk_c->f("category_name") != $prev_category) {
        $prev_category = $bk_c->f("category_name");
        $category_break = 1;
      }
       if ($bk_c->f("subcategory_name") != $prev_subcategory) {
        $prev_subcategory = $bk_c->f("subcategory_name");
        $subcategory_break = 1;
      }

      if ($category_break or $subcategory_break and !$first_time) {
        $first_time = 0;
      }

      if ($category_break) {
        $output .= sprintf("dbAdd( true , \"%s\" , \"\" , 0 , \"bk_target\", 0,%s)\n",htmlspecialchars(stripslashes($prev_category)), $bk_c->f("id"));
        $output .= sprintf("dbAdd( true , \"%s\" , \"\" , 1 , \"bk_target\", 0,%s)\n",htmlspecialchars(stripslashes($prev_subcategory)), $bk_c->f("id"));
      } elseif ($subcategory_break) {
        $output .= sprintf("dbAdd( true , \"%s\" , \"\" , 1 , \"bk_target\", 0,%s)\n",htmlspecialchars(stripslashes($prev_subcategory)), $bk_c->f("id"));
      }    

      $output .= sprintf("dbAdd( false , \"%s\" , \"%s\" , 2 , \"bk_target\", 0,%s)\n",htmlspecialchars(stripslashes($bk_c->f("bookmark_name"))), $bk_c->f("url"), $bk_c->f("id"));
    } else {
  # the user doesn't want category/subcategory breaks, so just print the
  # urls on the first level
      $output .= sprintf("dbAdd( false , \"%s\" , \"%s\" , 0 , \"bk_target\", 0,%s)\n",htmlspecialchars(stripslashes($bk_c->f("bookmark_name"))), $bk_c->f("url"), $bk_c->f("id"));
    }
  }
}
# load the list of previously saved searches
# and prepare the save search form

if ($search > 0) {
  $default_search = $search;
} else {
  $default_search = "NONE";
}

load_ddlb("search", $default_search, &$search_select, TRUE);
$tpl->set_var(array(
  SEARCH_SELECT => $search_select,
  FORM_ACTION   => $sess->self_url()
));

$tpl->set_var(array(
  FILTER_MSG       => $filter_msg,
  GROUPBY_DEFAULT  => $groupby_default,
  BOOKMARK_JS      => $output,
  SESSION_ID       => $sess->id,
  IMAGE_URL_PREFIX => $bookmarker->image_url_prefix,
  IMAGE_EXT        => $bookmarker->image_ext
));


# standard error message, and message handler.
include(LIBDIR . "bkmsg.inc");
if (isset ($bk_output_html)) {
  $tpl->set_var(MESSAGES, $bk_output_html);
}

$tpl->parse("BODY", "body");
$tpl->p("BODY");

page_close();
?>
