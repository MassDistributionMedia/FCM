<?php

/* http://j.mp/fcm-p1 */

require_once("src/phpQuery.php");

$changeList = json_decode(str_replace('\\', '', $_POST["actions"]));

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

function fixTagShortClose($tag, $xml){ 
    $xml = preg_replace('/(<'.$tag.'[^>]*?)\/>/', '$1></'.$tag.'>', $xml);
    return $xml;
} 

$xml = $doc->getDocument();

$xml = fixTagShortClose("div", $xml);
$xml = fixTagShortClose('iframe', $xml);

file_put_contents("xml/body.xml", $xml);

/*
$doc = new DOMDocument();

$doc->load("xml/body.xml");

function getElementById($id, $context) {
        $xpath = new DomXPath($context);
        return $xpath->query("//*[@id='$id']")->item(0);
}

foreach( $actions as $action ) {
        $ele = getElementById($action->id, $doc);
        
        foreach( $action->actions as $mod ) {
                switch( $mod ) {
                        case "textModified":
                                ;
                }
        }
}
*/

?>
