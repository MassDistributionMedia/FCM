<?php

/* http://j.mp/fcm-p1 */

header("Content-type: application/json");

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

echo json_encode(array("success"=>true,"operations"=>$changeList->length));

/*
require_once("src/phpQuery.php");

$changeList = json_decode(stripslashes($_POST["actions"]));

function fixTagShortClose($tag, $xml){ 
    $xml = preg_replace('/(<'.$tag.'[^>]*?)\/>/', '$1></'.$tag.'>', $xml);
    return $xml;
}

$doc = phpQuery::newDocumentFileXML("xml/body.xml");

foreach( $changeList as $change ) {
        $ele = pq("#".$change->id);
        
        foreach( $change->actions as $actionType ) {
                switch($actionType) {
                        case "htmlModified":
                                $ele->html($change->newHtml);
                                break;
                        case "imgModified":
                                $ele->attr('src',$change->newSrc)->attr('alt',$change->newAlt)->attr('title',$change->newTitle);
                                break;
                        default:
                                // Unknown type.
                }
        }
}

$xml = $doc->getDocument();

$xml = fixTagShortClose("div", $xml);
$xml = fixTagShortClose('iframe', $xml);

file_put_contents("xml/body.xml", $xml);
*/

?>