<?php

/* http://j.mp/fcm-p1 */

//header("Content-type: application/json");

require_once('config.php');
require_once('./src/facebook.php');

global $config;

$facebook = new Facebook(array(
    'appId'  => $config['appId'],
    'secret' => $config['appSecret'],
    'cookie' => true,
));

function parse_signed_request($signed_request, $secret) {
  list($encoded_sig, $payload) = explode('.', $signed_request, 2); 

  // decode the data
  $sig = base64_url_decode($encoded_sig);
  $data = json_decode(base64_url_decode($payload), true);

  if (strtoupper($data['algorithm']) !== 'HMAC-SHA256') {
    //echo 'Unknown algorithm. Expected HMAC-SHA256';
    return null;
  }

  // check sig
  $expected_sig = hash_hmac('sha256', $payload, $secret, $raw = true);
  if ($sig !== $expected_sig) {
    //echo 'Bad Signed JSON signature!';
    return null;
  }

  return $data;
}

function base64_url_decode($input) {
  return base64_decode(strtr($input, '-_', '+/'));
}

function isAdmin($page_id=null) {
	global $facebook, $config, $signed_session;
	
	$signed_request = $facebook->getSignedRequest();
	$isAdmin = false;
	$user_id = $facebook->getUser();
	
	if( !$signed_request && $signed_session )
		$signed_request = $signed_session;
	
	if( !$signed_request && !$page_id && $config )
		$page_id = $config['page']['id'];
	
	if($page_id && $user_id && !($signed_request && $signed_request['page']['id'] != $page_id)) {
		$fql = "SELECT uid FROM page_admin WHERE page_id = $page_id";
		$page_admins = $facebook->api(array(
			'method'	=>	'fql.query',
			'query'		=>	$fql,
			'callback'	=>	''
		));
		
		foreach( $page_admins as $admin ) {
			if( $admin['uid'] == $user_id ) {
				$isAdmin = true;
				break;
			}
		}
	} else if( $signed_request ) {
		$isAdmin = $signed_request['page']['admin'];
	}
	
	return $isAdmin;
}

$signed_session = null;

session_start();
if( !$facebook->getSignedRequest() ) {
	if( isset($_SESSION['signed_request']) ) {
		$signed_session = parse_signed_request($_SESSION['signed_request'], $config['appSecret']);
	}
}

$page_admin = isAdmin();

if( !$page_admin ) {
	if( $facebook->getSignedRequest() || $facebook->getUser() ) {
		$error_code = 1;
		$error_msg = "Only admins may edit this page.";
	}
	else {
		$error_code = 2;
		$error_msg = "Not authenticated. Please log in.";
	}
	
	echo json_encode(array(
		"success"=>"false",
		"error_code"=>$error_code,
		"error_msg"=>$error_msg
	));
	exit;
}

$changeList = json_decode(stripslashes($_POST["actions"]));

function xml2xhtml($xml) {
    return preg_replace_callback('#<(\w+)([^>]*)\s*/>#s', create_function('$m', '
        $xhtml_tags = array("br", "hr", "input", "frame", "img", "area", "link", "col", "base", "basefont", "param");
        return in_array($m[1], $xhtml_tags) ? "<$m[1]$m[2] />" : "<$m[1]$m[2]></$m[1]>";
    '), $xml);
}

function setInnerHtml(&$node, $html) {
	//for( $i=0; $i<$node->childNodes->length; $i++ )
	//	$node->removeChild($node->childNodes->item($i));
	$node->nodeValue = '';
	
	$fragment = $node->ownerDocument->createDocumentFragment();
	$fragment->appendXML(str_replace('<br>', '<br />', $html));
	$node->appendChild($fragment);
}

function getElementByAttr($attr, $value, &$context) {
	$xpath = new DomXPath($context);
	return $xpath->query("//*[@$attr='$value']")->item(0);
}

function getElementById($id, &$context) {
	return getElementByAttr('id', $id, $context);
}

$doc = new DOMDocument();
$doc->load("xml/body.xml");

foreach( $changeList as $change ) {
        $ele = getElementById($change->id, $doc);
        
        foreach( $change->actions as $actionType ) {
                switch($actionType) {
					case "htmlModified":
						setInnerHtml($ele, $change->newHtml);
						break;
					case "imgModified":
						$ele->setAttribute('src',$change->newSrc);
						$ele->setAttribute('alt',$change->newAlt);
						$ele->setAttribute('title',$change->newTitle);
						break;
					default:
						// Unknown type.
                }
        }
}

$xml = '';

//echo $doc->saveXML($doc->childNodes->item(1));

for( $i=0; $i<$doc->childNodes->length; $i++ ) {
	$xml .= $doc->saveXML($doc->childNodes->item($i));
}
//$xml = $doc->saveXML($doc->documentElement);

//$xml = fixTagShortClose("div", $xml);
//$xml = fixTagShortClose('iframe', $xml);

$xml = xml2xhtml($xml);

file_put_contents("xml/body.xml", $xml);

echo json_encode(array("success"=>true,"operations"=>$changeList));
?>