<?php 
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: maintain.php3,v 1.25 2000/07/18 21:51:20 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
            ,"auth" => "bk_cr_auth"
            ,"perm" => "bk_perm"
            ,"user" => "bk_user"));

$auth->login();

function return_to () {
  global $returnto, $sess, $msg, $error_msg, $sess_msg, $sess_error_msg;

  if (!empty($returnto)) {
    $sess_msg       = $msg;
    $sess_error_msg = $error_msg;
    header(sprintf("Location: %s", $sess->url(base64_decode($returnto))));
    page_close();
    exit;
  }
}

$tpl->set_file(array(
  standard   => "common.standard.tpl",
  body       => "maintain.body.tpl"
));

set_standard("maintain", &$tpl);

### Submit Handler
### Get a database connection, instantiate a bookmark
$db    = new bk_db;
$bmark = new bmark;

## Check if there was a submission
while ( is_array($HTTP_POST_VARS) 
     && list($key, $val) = each($HTTP_POST_VARS)) {
  switch ($key) {

  ## Change bookmark
  case "bk_edit":
  case "bk_edit_x":
    if (!$bmark->update($id, $url, $name, $ldesc, $keywords, $category, $subcategory, 
                        $rating, $public)) break;

    return_to();
  break;

  ## Delete the bookmark
  case "bk_delete":
  case "bk_delete_x":
    if (!$bmark->delete($id)) break;
    return_to();
  break;
  
  ## Cancel the changes, send user back to referring page.
  case "bk_cancel":
  case "bk_cancel_x":
    $msg .= "Bookmark maintain cancelled.";
    return_to();
  break;

  default:
  break;
 }
}

if (empty($error_msg)) {
## get record to update
  $query = sprintf("select * from bookmark where id ='%s' and username='%s'", $id, $auth->auth["uname"]);
  $db->query($query);

  if ($db->Errno == 0) {
    if ($db->next_record()) {
      $url         = $db->f("url");
      $name        = $db->f("name");
      $ldesc       = $db->f("ldesc");
      $keywords    = $db->f("keywords");
      $rating      = $db->f("rating_id");
      $category    = $db->f("category_id");
      $subcategory = $db->f("subcategory_id");
      $added       = $db->f("added");
      $public      = $db->f("public_f");

if ($public == "on" || $public == "Y") {
  $public_selected = "CHECKED";
}

load_ddlb("category", $category, &$category_select, FALSE);
load_ddlb("subcategory", $subcategory, &$subcategory_select, FALSE);  
load_ddlb("rating", $rating, &$rating_select, FALSE);

if (!empty($returnto)) {
  $cancel_button = sprintf("<input type=\"image\" name=\"bk_cancel\" title=\"Cancel Maintain\" src=\"%scancel.%s\" border=0 width=24 height=24>", $bookmarker->image_url_prefix, $bookmarker->image_ext);
}

$tpl->set_var(array(
  FORM_ACTION        => $sess->self_url(),
  MAIL_THIS_LINK_URL => $sess->url("maillink.php3?id=".$id),
  ID              => $id,
  URL             => $url,
  NAME            => htmlspecialchars(stripslashes($name)),
  LDESC           => htmlspecialchars(stripslashes($ldesc)),
  KEYWORDS        => htmlspecialchars(stripslashes($keywords)),
  CATEGORY        => $category_select,
  SUBCATEGORY     => $subcategory_select,
  RATING          => $rating_select,
  ADDED           => $added,
  PUBLIC_SELECTED => $public_selected,
  CANCEL_BUTTON   => $cancel_button
));


$tpl->parse(BODY, "body");
    } else {
      $error_msg .= "<br>Bookmark $id not found.";
    }
  }
}

include(LIBDIR . "bkend.inc");
?>
