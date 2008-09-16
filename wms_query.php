<?php
/**
 * A straight WMS proxy
 *
 * @package  geoserver
 * @version  $Header: /home/cvs/bwpkgs/geoserver/wms_query.php,v 1.4 2008/09/16 18:58:24 waterdragon Exp $
 * @author   spider <nick@sluggardy.net>
 */

require_once( '../bit_setup_inc.php' );

// TODO: Move to a library
/**
 *
 * Outputs an exception document
 *
 * @param string $exception The exception message to send
 */
function geoserver_exception($exception) {
  global $gBitSmarty, $gBitSystem;
  $gBitSmarty->assign('exception', $exception);
  $gBitSystem->display('bitpackage:geoserver/wfs_exception.tpl', '', array( 'format' => 'xml'  ));
}

/**
 *
 * Makes a wfs request to the specified geoserver and outputs the document returned
v *
 * @param string $url The url to send the request to
 * @param string $args Additional parameters to send along in the post (if any)
 */
function geoserver_fetch($url, $args = NULL) {
  global $gBitSystem, $gBitSmarty;

  $query_url = $url;

  $post = '';
  if( !empty( $args ) ) {
    foreach ($args as $arg => $val) {
      if (strtolower($arg) == 'wms_path') {
	$query_url .= $val;
      }
      else {
	$post .= '&'.$arg.'='.$val;
      }
    }
  }

  if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    $query_url .= '?'.$post;
  }
  
  // create a new cURL resource
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $query_url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, false);
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  }
  $result = curl_exec($ch);

  if( !$result ) {
    geoserver_exception(curl_error($ch));
  }

  $header = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
  if( !empty($header) ) {
    // Hack for geoserver stupidity.
    if (strstr($header, 'xml')) {
	header('Content-Type: application/xml');
    } else {
      header('Content-Type: ' . $header);
    }
  }

  curl_close($ch);

  // Trick out any URLs in the result
  $result = str_replace($gBitSystem->getConfig('geoserver_url', 'http://localhost:8080/geoserver/'), GEOSERVER_PKG_URI, $result);

  echo $result;
}

$url = $gBitSystem->getConfig('geoserver_url', 'http://localhost:8080/geoserver/').'wms';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $args = $_POST;
} else {
    $args = $_GET;
}
geoserver_fetch($url, $args);
