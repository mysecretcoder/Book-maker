<?php
# ---------------------------------------------------------------
# bookmarker
# A WWW based bookmark management, retrieval, and search tool.
# Copyright (C) 1998  Padraic Renaghan
# Licensed under terms of GNU General Public License
# (see http://www.renaghan.com/bookmarker/source/LICENSE)
# ---------------------------------------------------------------
# $Id: import.php3,v 1.7 2000/07/18 21:51:20 prenagha Exp $
# ---------------------------------------------------------------
$debug = false;
#
# possible enhancements:
#  give option, that if url already exists, update existing row
#  give option, to load from csv file
#  give option, to load all urls into unassigned unassigned
#  give option, to delete bookmarks,cat,subcat before import
#
include(dirname(__FILE__)."/lib/bkprepend.inc");

# find existing category matching name, or
# create a new one. return id.
function getCategory ($name) {
  global $db, $cat, $catNext, $auth;

  $upperName = strtoupper($name);

  if (isset($cat[$upperName])) {
    return $cat[$upperName];

  } else {

    $q  = "INSERT INTO category (id, name, username) ";
    $q .= "VALUES ('".$catNext."', '".$name."', '".$auth->auth["uname"]."') ";

    $db->query($q);
    if ($db->Errno != 0) {
      $error_msg .= "<br>Error adding category ".$name." - ".$catNext;
      return -1;
    }
    $cat[$upperName] = $catNext;
    $catNext++;
    return ($catNext - 1);
  }
}

# find existing subcategory matching name, or
# create a new one. return id.
function getSubCategory ($name) {
  global $db, $subcat, $subcatNext, $auth;

  $upperName = strtoupper($name);

  if (isset($subcat[$upperName])) {
    return $subcat[$upperName];

  } else {

    $q  = "INSERT INTO subcategory (id, name, username) ";
    $q .= "VALUES ('".$subcatNext."', '".$name."', '".$auth->auth["uname"]."') ";

    $db->query($q);
    if ($db->Errno != 0) {
      $error_msg .= "<br>Error adding subcategory ".$name." - ".$subcatNext;
      return -1;
    }
    $subcat[$upperName] = $subcatNext;
    $subcatNext++;
    return ($subcatNext - 1);
  }
}

page_open(array( "sess" => "bk_sess"
                ,"auth" => "bk_cr_auth"
                ,"perm" => "bk_perm"
                ,"user" => "bk_user"));

$auth->login();

$tpl->set_file(array(
  standard            => "common.standard.tpl",
  body                => "import.body.tpl"
));

set_standard("import", &$tpl);

### Submit Handler
### Get a database connection
$db    = new bk_db;

## Check if there was a submission
while ( is_array($HTTP_POST_VARS)
     && list($key, $val) = each($HTTP_POST_VARS)) {
  switch ($key) {

  ## import bookmarks
  case "bk_import":
  if (!$debug) print ("\n<!--\n");
  $db    = new bk_db;
  $bmark = new bmark;

  print ("<p><b>DEBUG OUTPUT:</b>\n");
  print ("<br>bkfile: " . $bkfile . "\n");
  print ("<br>bkfile_name: " . $bkfile_name . "\n");
  print ("<br>bkfile_size: " . $bkfile_size . "\n");
  print ("<br>bkfile_type: " . $bkfile_type . "\n<p><b>URLs:</b>\n");

  if (empty($bkfile) || $bkfile == "none") {
    $error_msg .= "<br>Netscape bookmark filename is required!";
    break;
  }

  $fd = @fopen($bkfile, "r");
  if($fd) {
# read current categories into an array
    $catNext = -1;
    $query = sprintf("select id, name from category where username='%s' order by id",
                        $auth->auth["uname"]);
    $db->query($query);
    if ($db->Errno != 0) break;
    while ($db->next_record()) {
      $cat[strtoupper($db->f("name"))] = $db->f("id");
      $catNext = $db->f("id");
    }
    $catNext++;
    
# read current subcategories into an array
    $subcatNext = -1;
    $query = sprintf("select id, name from subcategory where username='%s' order by id",
                        $auth->auth["uname"]);
    $db->query($query);
    if ($db->Errno != 0) break;
    while ($db->next_record()) {
      $subcat[strtoupper($db->f("name"))] = $db->f("id");
      $subcatNext = $db->f("id");
    }
    $subcatNext++;

    $inserts = 0;
    $folder_index = -1;
    $cat_index = -1;
    $scat_index = -1;
    $bookmarker->url_format_check = 0;
    $bookmarker->url_responds_check = false;

    if ($auth->auth["default_public"] == 'Y') {
      $public = "on";
    } else {
      $public = "off";
    }

    while ($line = @fgets($fd, 2048)) {
      ## URLs are recognized by A HREF tags in the NS file.
      if (eregi('<A HREF="([^"]*)[^>]*>(.*)</A>', $line, $match)) {

        $url_parts = @parse_url($match[1]);
        if ($url_parts[scheme] == "http"
          || $url_parts[scheme] == "https"
          || $url_parts[scheme] == "ftp"
          || $url_parts[scheme] == "news") {

          reset($folder_stack);
          unset($error_msg);
          $cid = 0;
          $scid = 0;
          $i = 0;
          $keyw = '';
          while ($i <= $folder_index) {
            if ($i == 0) {
              $cid = getCategory($folder_name_stack[$i]);
            } elseif ($i == 1) {
              $scid = getSubCategory($folder_name_stack[$i]);
            }
            $keyw .= ' ' . $folder_name_stack[$i];
            $i ++;
          }

          $bid = -1;
          if (!$bmark->add(&$bid, trim(addslashes($match[1])), trim(addslashes($match[2])), 
                 trim(addslashes($match[2])), trim($keyw), $cid, $scid, 0, $public)) {
            print("<br>" . $error_msg . "\n");
            $all_errors .= $error_msg;
          }

          printf("<br>%s,%s,%s,%s,<i>%s</i>\n",$cid,$scid,$match[2],$match[1],$bid);
          if ($bid > 0) 
            $inserts ++;
        }
      }

      ## folders start with the folder name inside an <H3> tag,
      ## and end with the close </DL> tag.
      ## we use a stack to keep track of where we are in the
      ## folder hierarchy.
      elseif (eregi('<H3[^>]*>(.*)</H3>', $line, $match)) {
        $folder_index ++;
        $id = -1;

        if ($folder_index == 0) {
          $cat_index ++;
          $cat_array [$cat_index] = $match[1];
          $id = $cat_index + $cat_start;

        } elseif ($folder_index == 1) {
          $scat_index ++;
          $scat_array [$scat_index] = $match[1];
          $id = $scat_index + $scat_start;
        }
        $folder_stack [$folder_index] = $id;
        $folder_name_stack [$folder_index] = $match[1];

      }
      elseif (eregi('</DL>', $line)) {
        $folder_index-- ;
      }
    }

    @fclose($fd);

  } else {
    $error_msg .= "<br>Unable to open temp file " . $bkfile . " for import.";
  }
    unset($msg);
    $msg .= sprintf("<br>%s bookmarks imported from %s successfully.", $inserts, $bkfile_name);
    if (!$debug) print ("\n-->\n");
    $error_msg = $all_errors;
    break;

  default:
    break;
 }
}

$tpl->set_var(array(
  FORM_ACTION => $sess->self_url()
));

include(LIBDIR . "bkend.inc");
?>
