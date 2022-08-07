<?php 
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: user.php3,v 1.24 2000/07/18 21:51:20 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
              ,"auth" => "bk_cr_auth"
              ,"perm" => "bk_perm"
              ,"user" => "bk_user"));

$auth->login();

$tpl->set_file(array(
  standard   => "common.standard.tpl",
  user_list  => "user.userlist.tpl",
  select_form  => "user.select.tpl",
  update_form  => "user.update.tpl",
  create_form  => "user.create.tpl",
  delete_form  => "user.delete.tpl"
));

set_standard("users", &$tpl);

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

  ## maintain user
  case "bk_user_update":
    ## Do we have permission to do so?
    if ( ($perm->have_perm("admin"))
      || (($perm->have_perm("editor") 
       && ($username == $auth->auth["uname"]) )) ) {
    } else {
      $error_msg .= "<br>You do not have permission to update this user.";
      break;
    }

    ## trim form fields
    $perms = trim($perms);
    $name = trim($name);
    $email = $validate->strip_space($email);
        
    ## Do we have all necessary data?
    if (empty($name) || empty ($email)) {
      $error_msg .= "<br>Please fill out <B>Name</B> and <B>Email Address</B>.";
      break;
    }
    if ($perm->have_perm("admin") && empty($perms)) {
      $error_msg .= "<br>Please fill out <B>Permission</B>.";
      break;
    }

  ## Is email address in the proper format?
    if (!$validate->is_email($email))  { 
      $error_msg .= "<br>From address invalid. Format must be 
                     <strong>user@domain</strong> and domain must exist!
                     <br><small> $validate->ERROR </small>";
      break;
    }
    
    if ($default_public == "on") {
      $default_public = "Y";
    } else {
      $default_public = "N";
    }

    if ($include_public == "on") {
      $include_public = "Y";
    } else {
      $include_public = "N";
    }

    ## Update information
    $perm_auth_cookie = MD5(uniqid(time().$username));
    $query = sprintf("update auth_user set name='%s', email='%s', 
                      perm_auth_cookie='%s', default_public='%s', include_public='%s'
                      where username='%s'", 
                      addslashes($name), $email, $perm_auth_cookie, 
                      $default_public, $include_public, $username);

    $db->query($query);
    if ($db->Errno == 0) {
    ## update password if specified
      if (!empty($password)) {
        $query = sprintf("update auth_user set password='%s' where username='%s'", 
                      md5($password), $username);
        $db->query($query);
      }
    ## update perms if specified
      if (!empty($perms)) {
        $query = sprintf("update auth_user set perms='%s' where username='%s'", 
                      $perms, $username);
        $db->query($query);
      }
      $mode = "S";
      $msg .= "<br>User &quot;$username&quot; changed.
               <br>Changes are effective upon next login.";
    }
  break;

  ## Delete the user
  case "bk_user_delete":
    ## Do we have permission to do so?
    if (!$perm->have_perm("admin")) {
      $error_msg .= "<br>You do not have permission to delete users.";
      break;
    }
    
    ## Delete that user
    $query = sprintf("delete from auth_user where username='%s'", $username);
    $db->query($query);
    if ($db->Errno == 0) {
      $mode = "S";
      $msg .= "<br>User &quot;$username&quot; deleted.";
    }
  break;

  ## Create a new user
  case "bk_user_create":

    ## Do we have permission to do so?
    if (!$perm->have_perm("admin")) {
      $error_msg .= "<br>You do not have permission to create users.";
      break;
    }

    ## trim form fields
    $perms = trim($perms);
    $name = trim($name);
    $email = $validate->strip_space($email);
    $username = trim($username);
        
    ## Do we have all necessary data?
    if (empty($password) || empty($perms) || empty($username) 
        || empty($name) || empty($email)) {
      $error_msg .= "<br>Please fill out <B>User Name</B>, <B>Name</B>, 
                 <B>E-Mail</B>, <B>Password<B>, and <B>Permissions</B>!";
      break;
    }

    ## Limit characters that can be used in user ID
    if (!ereg("^[A-Za-z0-9]+$", $username)) {
      $error_msg .= "<br>Username may only contain alphanumeric characters.";
      break;
    }

  ## Is email address in the proper format?
    if (!$validate->is_email($email))  { 
      $error_msg .= "<br>From address invalid. Format must be 
        <strong>user@domain</strong> and domain must exist!
        <br><small> $validate->ERROR </small>";
      break;
    }
    
    ## Does the user already exist?
    $query = sprintf("select username from auth_user where username = '%s'", $username);
    $db->query($query);
    if ($db->Errno == 0) {
      if ($db->nf()>0) {
        $error_msg .= "<br>User <B>$username</B> already exists!";
        break;
      }
    }

    if ($default_public == "on") {
      $default_public = "Y";
    } else {
      $default_public = "N";
    }

    if ($include_public == "on") {
      $include_public = "Y";
    } else {
      $include_public = "N";
    }

    ## Insert the user
    $unique_uid = MD5(uniqid($user->user["magic"]));
    $perm_auth_cookie = MD5(uniqid(time().$username));
    $query = sprintf("insert into auth_user (user_id, username, password, perms, name, email, 
                      perm_auth_cookie, default_public, include_public) 
                      values('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')", 
                      $unique_uid, $username, md5($password), $perms, addslashes($name), 
                      $email, $perm_auth_cookie, $default_public, $include_public);
    $db->query($query);
    if ($db->Errno == 0) {
      $mode = "S";
      $msg = "<br>User &quot;$username&quot; created.";
    
      ## insert default rows for codes tables for this user.
      ## we don't really care if the inserts fail or not, so
      ## don't bother checking the return codes
      $query = "insert into category (id, name, username) values(0, '--', '$username')";
      $db->query($query);
      $query = "insert into subcategory (id, name, username) values(0, '--', '$username')";
      $db->query($query);

      $query = "insert into rating (id, name, username) values(0, '--', '$username')";
      $db->query($query);
      $query = "insert into rating (id, name, username) values(1, 'weak', '$username')";
      $db->query($query);
      $query = "insert into rating (id, name, username) values(2, 'good', '$username')";
      $db->query($query);
      $query = "insert into rating (id, name, username) values(3, 'excellent', '$username')";
      $db->query($query);

      ## Get the next available ID key
      $id = $db->nextid('bookmark');
      $query = "insert into bookmark (id, url, name, ldesc, category_id, subcategory_id, rating_id, username) values($id, 'http://renaghan.com/pcr/bookmarker.html', 'bookmarker home', 'bookmarker home page',0,0,3, '$username')";
      $db->query($query);
    }

    break;
  
  ## set all bookmarks for a user to private
  case "bk_set_private":
    ## Do we have permission to do so?
    if ($perm->have_perm("admin")) {
    } elseif ($perm->have_perm("editor") && ($username == $auth->auth["uname"])) {
    } else {
      $error_msg .= "<br>You do not have permission to set bookmarks to private for this user.";
      break;
    }
    
    ## set all bookmarks for a user to private
    $query = sprintf("update bookmark set public_f='N' where username='%s'", $username);
    $db->query($query);
    if ($db->Errno == 0) {
      $mode = "S";
      $msg .= sprintf("<br>All %s's bookmarks set to private.", $username);
    }

    # update to public bookmark count for this user.
    $bmark = new bmark;
    $bmark->update_user_total_bookmarks($username);

  break;

  ## set all bookmarks for a user to public
  case "bk_set_public":
    ## Do we have permission to do so?
    if ($perm->have_perm("admin")) {
    } elseif ($perm->have_perm("editor") && ($username == $auth->auth["uname"])) {
    } else {
      $error_msg .= "<br>You do not have permission to set bookmarks to public for this user.";
      break;
    }
    
    ## set all bookmarks for a user to public
    $query = sprintf("update bookmark set public_f='Y' where username='%s'", $username);
    $db->query($query);
    if ($db->Errno == 0) {
      $mode = "S";
      $msg .= sprintf("<br>All %s's bookmarks set to public.", $username);
    }

    # update to public bookmark count for this user.
    $bmark = new bmark;
    $bmark->update_user_total_bookmarks($username);

  break;

  default:
    break;
 } /* end switch */
} /* end while */

# if no mode specified, or mode is S (Select)
# then print html to allow user to select from
# the possible options and data on this page.
$tpl->set_var(FORM_ACTION, $sess->self_url());

if (!isset($mode) || $mode=="S") {
  $body_tpl_name = "select_form";

  ## get records to update and delete
  $query = "select username from auth_user order by username";
  $db->query($query);
  if ($db->Errno == 0) {
    while ($db->next_record()) {

      if ( ($perm->have_perm("admin"))
        || (($perm->have_perm("editor") && ($db->f("username") == $auth->auth["uname"]))) ) {
        $url = $sess->url(sprintf("user.php3?mode=U&username=%s", $db->f("username")));
        $tpl->set_var(URL, $url);
        $tpl->set_var(NAME, $db->f("username"));
        $tpl->parse(UPDATE_USER_LIST, "user_list", TRUE);
      }
      if ($perm->have_perm("admin")) {
        $url = $sess->url( sprintf("user.php3?mode=D&username=%s", $db->f("username")));
        $tpl->set_var(URL, $url);
        $tpl->parse(DELETE_USER_LIST, "user_list", TRUE);
      }

    }
  }

# if mode is U, present the update form
} elseif ($mode=="U") {
  $body_tpl_name = "update_form";

  ## get record to update
  $query = sprintf("select * from auth_user where username='%s'", $username);
  $db->query($query);
  if ($db->Errno == 0) {
    if ($db->next_record()) {
      if ($db->f("default_public") == "Y") {
        $default_public_checked = "CHECKED";
      }
      if ($db->f("include_public") == "Y") {
        $include_public_checked = "CHECKED";
      }
      if ($perm->have_perm("admin")) {
        $perms_html =  "<tr><td>Permissions</td>\n";
        $perms_html .= sprintf("<td><input type=\"text\" name=\"perms\" size=32 maxlength=255 value=\"%s\"></td></tr>\n", $db->f("perms"));
      } else {
        $perms_html =  "<tr><td>Permissions</td>\n";
        $perms_html .= sprintf("<td><strong>%s</strong></td></tr>\n", $db->f("perms"));             }

      $tpl->set_var(array(
        USERNAME       => $db->f("username"),
        NAME           => htmlspecialchars(stripslashes($db->f("name"))),
        EMAIL          => $db->f("email"),
        PERMS          => $perms_html,
        DEFAULT_PUBLIC_CHECKED => $default_public_checked,
        INCLUDE_PUBLIC_CHECKED => $include_public_checked
      ));
    } 
  }
 
# if mode is C, present the create form
} elseif ($mode=="C") {
  $body_tpl_name = "create_form";

  $default_public_checked = "";
  $include_public_checked = "CHECKED";

  $tpl->set_var(array(
      USERNAME       => $username,
      NAME           => $name,
      EMAIL          => $email,
      PERMS          => $perms,
      DEFAULT_PUBLIC_CHECKED => $default_public_checked,
      INCLUDE_PUBLIC_CHECKED => $include_public_checked
    ));

# if mode is D, present the are you sure delete form
} elseif ($mode=="D") {
  $body_tpl_name = "delete_form";
  $tpl->set_var(USERNAME , $username);
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
