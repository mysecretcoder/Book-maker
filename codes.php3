<?php 
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: codes.php3,v 1.15 2000/10/31 18:40:14 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
                ,"auth" => "bk_cr_auth"
                ,"perm" => "bk_perm"
                ,"user" => "bk_user"));

$auth->login();

$tpl->set_file(array(
  standard   => "common.standard.tpl",
  code_list  => "codes.codelist.tpl",
  select_form  => "codes.select.tpl",
  update_form  => "codes.update.tpl",
  create_form  => "codes.create.tpl",
  delete_form  => "codes.delete.tpl"
));

set_standard("code tables", &$tpl);

$username = $auth->auth["uname"];

### Get a database connection
$db   = new bk_db;

## Check if there was a submission
while ( is_array($HTTP_POST_VARS) 
     && list($key, $val) = each($HTTP_POST_VARS)) {
  switch ($key) {

  ## update canceled
  case "bk_cancel_update":
    $mode = "S";
  break;

  ## create canceled
  case "bk_cancel_create":
    $mode = "S";
  break;

  ## delete canceled
  case "bk_cancel_delete":
    $mode = "S";
  break;

  ## maintain code table
  case "bk_code_update":
    ## Do we have permission to do so?
    if (!$perm->have_perm("editor")) {
      $error_msg .= "<br>You do not have permission to update code tables.";
      break;
    }

    ## Trim space from begining and end of fields
    $name = trim($name);

    ## Do we have all necessary data?
    if (empty($name)) {
      $error_msg = "<br>Please fill out <B>Name</B>!";
      break;
    }
    
    ## Update information
    $query = sprintf("update %s set name='%s' where id=%s and username='%s'", $codetable, addslashes($name), $id, $username);
  
    $db->query($query);
    if ($db->Errno != 0) break;

    $mode = "S";
    $msg .= sprintf("<br>%s %s (%s) changed.", $codetable, htmlspecialchars(stripslashes($name)), $id) ;
  break;

  ## Delete the codes
  case "bk_code_delete":
    ## Do we have permission to do so?
    if (!$perm->have_perm("editor")) {
      $error_msg .= "<br>You do not have permission to delete codes.";
      break;
    }
    
    ## May not delete system default row
    if (($codetable == "category" || $codetable == "subcategory")
       && ($id == 0)) {
      $error_msg .= "<br>You may not delete the system default $codetable.";
      break;
    }
    
    ## when deleting a category or subcategory, we need to
    ## update related tables to maintain data integrity.
    if ($codetable == "category" || $codetable == "subcategory") {
      $query = sprintf("update bookmark set %s_id=0 where %s_id=%s and username='%s'"
                      ,$codetable
                      ,$codetable
                      ,$id
                      ,$username);
      $db->query($query);
      if ($db->Errno != 0) break;
      $msg .= "<br>bookmarks with $codetable $id changed to $codetable 0.";
    }

    ## Delete that code
    $query = sprintf("delete from %s where id=%s and username='%s'", $codetable, $id, $username);
    $db->query($query);
    if ($db->Errno != 0) break;

    $mode = "S";
    $msg .= "<br>$codetable $id deleted.";
  break;

  ## Create a code
  case "bk_code_create":

    ## Do we have permission to do so?
    if (!$perm->have_perm("editor")) {
      $error_msg .= "<br>You do not have permission to create codes.";
      break;
    }

    ## Trim space from begining and end of fields
    $name = trim($name);
    
    ## Do we have all necessary data?
    if (empty($id) || empty($name)) {
      $error_msg .= "<br>Please fill out <B>ID</B>, and <B>Name</B>!";
      break;
    }

    ## make sure ID is a number
    if (! $validate->is_allnumbers($id)) {
      $error_msg .= "<br>ID must be a number!<br><small> $validate->ERROR </small>";
      break;
    }
        
    ## Does the code already exist?
    $query = sprintf("select id from %s where id=%s and username='%s'", $codetable, $id, $username);
    $db->query($query);
    if ($db->Errno != 0) break;

    if ($db->nf()>0) {
      $error_msg .= "<br>$codetable <B>$id</B> already exists!";
      break;
    }

    ## Insert the code
    $query = sprintf("insert into %s (id, name, username) values(%s, '%s', '%s')", $codetable, $id, addslashes($name), $username);
    $db->query($query);
    if ($db->Errno != 0) break;

    $mode = "S";
    $msg .= sprintf("<br>%s %s (%s) created.", $codetable, htmlspecialchars(stripslashes($name)), $id);
  break;
  
  default:
  break;
 } /* end switch */
} /* end while */

$tpl->set_var(CODETABLE, $codetable);
$tpl->set_var(FORM_ACTION, $sess->self_url());

# if no mode specified, or mode is S (Select)
# then print html to allow user to select from
# the possible options and data on this page.

if (!isset($mode) || $mode=="S") {
  $body_tpl_name = "select_form";

  ## get records to update
  $query = "select id, name from $codetable where username='$username' order by name";
  $db->query($query);
  if ($db->Errno == 0) {
    while ($db->next_record()) {
      $id = $db->f("id");
      $url = $sess->url(sprintf("codes.php3?codetable=%s&mode=U&id=%s", $codetable, $id));

      $tpl->set_var(URL, $url);
      $tpl->set_var(NAME, htmlspecialchars(stripslashes($db->f("name"))));
      $tpl->set_var(ID, $id);
      $tpl->parse(UPDATE_CODE_LIST, "code_list", TRUE);

      if (($codetable == "category" || $codetable == "subcategory")
         && ($id == 0)) {
      } else {
        $url = $sess->url(sprintf("codes.php3?codetable=%s&mode=D&id=%s", $codetable, $id));
        $tpl->set_var(URL, $url);
        $tpl->parse(DELETE_CODE_LIST, "code_list", TRUE);
      }
    }
    $tpl->parse(BODY, "select_form");
  }

# if mode is U, present the update form
} elseif ($mode=="U") {
  $body_tpl_name = "update_form";

  ## get record to update
  $query = sprintf("select * from %s where id=%s and username='%s'", $codetable, $id, $username);
  $db->query($query);
  if ($db->Errno == 0) {
    if ($db->next_record()) {
      $tpl->set_var(array(
        ID       => $db->f("id"),
        NAME     => htmlspecialchars(stripslashes($db->f("name")))
      ));
      $tpl->parse(BODY, "update_form");
     } /* end fetch if */
  }
 
# if mode is C, present the create form
} elseif ($mode=="C") {
  $body_tpl_name = "create_form";

  ## get the max used ID so that we can default for the new row
  $query = sprintf("select max(id) as max_id from %s where username='%s'", $codetable, $username);
  $db->query($query);
  if ($db->Errno == 0) {
    if ($db->next_record()) {
      $default_id = $db->f("max_id") + 1;
    } else {
      $default_id = 0;
    }
    $tpl->set_var(DEFAULT_ID, $default_id);
  }

# if mode is D, present the are you sure delete form
} elseif ($mode=="D") {
  $body_tpl_name = "delete_form";
  $tpl->set_var(ID, $id);
}

# NOTE: we can't use bkend.inc here since we don't have
# a static name for the body template.

# standard error message, and message handler.
include(LIBDIR . "bkmsg.inc");
if (isset ($bk_output_html)) {
  $tpl->set_var(MESSAGES, $bk_output_html);
}

$tpl->parse("BODY", array($body_tpl_name, "standard"));
$tpl->p("BODY");
page_close();
?>
