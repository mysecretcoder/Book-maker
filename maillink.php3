<?php
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: maillink.php3,v 1.18 2000/07/18 21:51:20 prenagha Exp $
# ---------------------------------------------------------------
include(dirname(__FILE__)."/lib/bkprepend.inc");
page_open(array( "sess" => "bk_sess"
          ,"auth" => "bk_cr_auth"
          ,"perm" => "bk_perm"
          ,"user" => "bk_user"));

$tpl->set_file(array(
  standard   => "common.standard.tpl",
  msie_js    => "common.msie_js.tpl",
  body       => "maillink.body.tpl"
));

set_standard("mail-this-link", &$tpl);

# if browser is MSIE, then need to add this bit
# of javascript to the page so that MSIE correctly
# brings quik-mark and mail-this-link popups to the front.
if (check_browser() == "MSIE") {
  $tpl->parse(MSIE_JS, "msie_js");
}

### Submit Handler
### Get a database connection
$db   = new bk_db;

## get from info from database based on current PHPLIB user
## do NOT accept these as variables from the form page - big
## security hole if you do!
unset($from_name);
unset($from);
$query = sprintf("select name, email from auth_user where username = '%s'"
  , ($auth->is_nobody()?"":$auth->auth["uname"]));
$db->query($query);
if ($db->Errno == 0) {
  if ($db->next_record()){
    $from_name = $db->f("name");
    $from = $db->f("email");
  }
}

## Check if there was a submission
while ( is_array($HTTP_POST_VARS)
     && list($key, $val) = each($HTTP_POST_VARS)) {
  switch ($key) {

  ## Send button clicked
  case "bk_send":

    ## Do we have permission to do so?
    if (!$perm->have_perm($bookmarker->mail_this_link_permission_required)) {
      $error_msg .= "<br>You do not have permission to use this feature!";
      break;
    }

    ## Strip space and tab from anywhere in the To field
    $to = $validate->strip_space($to);

    ## Trim the subject
    $subject = trim($subject);

    ## Do we have all necessary data?
    if (empty($to) || empty($subject) || empty($message)) {
      $error_msg .= "<br>Please fill out <B>To E-Mail Address</B>, <B>Subject</B>, and <B>Message</B>!";
      break;
    }

    ## the To field may contain one or more email addresses
    ## separated by commas. Check each one for proper format.
    $to_array = explode(",", $to);

    while ( list( $key, $val ) = each( $to_array ) ) {
      ## Is email address in the proper format?
      if (!$validate->is_email($val))  {
        $error_msg .= "<br>To address $val invalid. Format must be <strong>user@domain</strong> and domain must exist!<br><small> $validate->ERROR </small>";
        break;
      }
    }

    if (isset ($error_msg)) {
      break;
    }

    ## if a site footer is defined, append it to the message
    if (! empty($bookmarker->site_footer)) {
      $mail_message = sprintf("%s\n\n%s", $message, $bookmarker->site_footer);
    }

    ## add additional headers to our email
    $addl_headers = sprintf("From: %s <%s>", stripslashes($from_name), $from);

    ## if site headers are defined, add them
    if (! empty($bookmarker->site_headers)) {
      $addl_headers = sprintf("%s\n%s", $addl_headers, $bookmarker->site_headers);
    }

    ## send the message
    mail($to, $subject, $mail_message, $addl_headers);

    $msg .= "<br>mail-this-link message sent to $to.";
    break;

  default:
    break;
 }
}

if (empty($subject)) {
  $subject = "Found a link you might like";
}

if (empty($message)) {
## if a bookmarker id is passed, then get title and URL
## from the database. otherwise those fields should be
## passed in.
  if ($id > 0) {
## get record
    $query = sprintf("select * from bookmark where id ='%s' 
      and (username='%s' or public_f='Y')", $id, 
      ($auth->is_nobody()?"":$auth->auth["uname"]));
    $db->query($query);
    if ($db->Errno == 0) {
      if ($db->next_record()){
        $title = htmlspecialchars(stripslashes($db->f("name")));
        $url = $db->f("url");
      }
    }
  } else {
    $url = $murl;
    $title = $mtitle;
  }
  $message = "I thought you would be interested in this website:\n$title\n$url";
}


$tpl->set_var(array(
  FORM_ACTION     => $sess->self_url(),
  FROM_NAME       => htmlspecialchars(stripslashes($from_name)),
  FROM            => $from,
  TO              => $to,
  SUBJECT         => $subject,
  MESSAGE         => $message,
  SITE_FOOTER     => nl2br($bookmarker->site_footer)
));

include(LIBDIR . "bkend.inc");
?>
