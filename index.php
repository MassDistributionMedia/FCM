<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">

<?php

/* index.php
http://j.mp/fcm-p1 */

require_once('./src/facebook.php');

$appId = '203223123047060';
$appSecret = 'bfe919cd733f844342af3267b818ad95';


$facebook = new Facebook(array(
    'appId'  => $appId,
    'secret' => $appSecret,
    'cookie' => true,
));

$session = $facebook->getSession();

$signed_request = $_REQUEST["signed_request"];
list($encoded_sig, $payload) = explode('.', $signed_request, 2);
$data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

$visitorAdmin = false;
$visitorLike = false;

if( $data['page'] ) {
    $visitorAdmin = $data['page']['admin'];
    $visitorLiked = $data['page']['liked'];
}

$url = 'http';
if( $_SERVER['HTTPS'] == 'on' ) { $url .= 's'; }
$url .= '://' . $_SERVER['SERVER_NAME'];
if( $_SERVER['SERVER_PORT'] != '80' ) { $url .= ':' . $_SERVER['SERVER_PORT']; }
$url .= $_SERVER['SCRIPT_NAME'];

$urlPath = dirname($url);

if( substr($urlPath, -1) != '/' ) { $urlPath .= '/'; }

$ajaxSaveUrl = $urlPath . 'pushChanges.php';
$ajaxUploadUrl = $urlPath . 'upload.php';

/* TODO: SECURITY! CURRENT CODE IS VERY INSECURE!
 Need to implement nonces and session so the content cannot be hacked by directly calling our pushChanges.php
 This will either require a DB or modifying server privileges so the XML folder is password protected. DB is still more secure, however.
 Okay. Should we go wtih MySQL, 
PostgreSQL, or maybe even xQuery?
DB requires installation and more configuration.
XML is easier to distribute, though the password-protected the xml folder still requires a bit of configuration.
If you go with DB, I prefer MySQL.
Humm....
Password protected like a protected directory?
*/

?>

<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 

<link href="reset.css" rel="stylesheet" type="text/css" /> 
<link href="style.css" rel="stylesheet" type="text/css" />
 
<title>Burger Mania FCM Test Page</title>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.0/jquery.min.js"></script>

<?php if( $visitorAdmin ) { ?>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.form.min.js"></script>
<script type="text/javascript" src="js/json2.min.js"></script>

<script type="text/javascript" src="js/adminEdit.js"></script>

<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/themes/smoothness/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link href="admin.css" rel="stylesheet" type="text/css" />
<?php } ?>

<script type="text/javascript">
// This code is for the featured slider functionality.
var selected;

$j = jQuery.noConflict();

$j(document).ready(function() {
    selected = $j("#featured-buttons li.selected");
    
    $j("#featured-buttons a").click(function(e){
        $j("#featured-slides li").removeClass("selected");
        $j($j(e.target).attr("href")).addClass("selected");
        
        $j("#featured-buttons li").removeClass("selected");
        $j(e.target).parent().addClass("selected");
        e.preventDefault();
    });
});
</script>


</head> 

<body> 

 <div class="clear"></div>
<br/>

<?php if( $visitorAdmin ) { ?>
<div id="cms-buttons" style="position:fixed; background:#f4f2ff; padding:7px">
    <button id="revertChanges">Revert</button>
    <button id="pushChanges">Save</button>
    <button id="hotspotTour">Tour</button>
</div>

<div id="fb-root"> </div>
<script type="text/javascript">

function sessionResponse (response) {
    if (response.session) {
        console.log(response);
        $j("#fb-login").addClass("hidden");
        
        var perms;
        
        if( response.perms ) {
            if( typeof response.perms === "string" )
                perms = JSON.parse(response.perms);
            else
                perms = response.perms;
        }
        
        if( !perms || perms.user.length === 0 ) {
            console.log("Incorrect permissions.");
        }
        
        FB.api("/me/albums", function(response) {
            for( var i=0, album=response.data[i]; album; i++, album=response.data[i] ) {
                FB.api("/"+album.id+"/photos", function(resp) {
                    console.log(resp);
                    
                    for( var i=0, img=resp.data[i]; img; i++, img=resp.data[i] ) {
                        //console.log(img);
                        
                        var a = $j('<a href="#" title="Select this image"><img src="'+img.picture+'"/></a>')
                            .data('newSrc',img.source).click(function(){
                            $j("#switch-src").val($j(this).data('newSrc'));
                            $j("#switch-alt").val($j(this).data('newAlt'));
                            $j("#switch-title").val($j(this).data('newTitle'));
                            return false;
                        }).appendTo("#fb-album");
                        
                        if( img.name )
                            a.data('newAlt',img.name).data('newTitle',img.name);
                    }
                });
            }
        });
    } else {
        $j("#fb-login").removeClass("hidden");
        console.log("Disconnected");
    }
}

window.fbAsyncInit = function() {
    FB.init({appId: '<?php echo $appId; ?>', status: true, cookie: true,
             xfbml: true});
    FB.Canvas.setSize();
    
    window.setTimeout(function() {
        FB.Canvas.setAutoResize();
    }, 1000);
    
    FB.getLoginStatus(sessionResponse);
    $j("#fb-disconnect").click(function(){FB.api({method:'Auth.revokeAuthorization'}, sessionResponse);});
};
  (function() {
    var e = document.createElement('script'); e.async = true;
    e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
    e.async = true;
    document.getElementById('fb-root').appendChild(e);
  }());
</script>
<?php } ?>

<?php if( $visitorAdmin ) { ?>
<div id="switchBox" class="hidden" title="Image Editor">
    <ul>
        <li><a href="#switchBox-general" title="General">General</a></li>
        <li><a href="#switchBox-album" title="Facebook Album">Album</a></li>
    </ul>
    <div id="switchBox-general">
    <form>
        <fieldset>
            <label for="src">Image Location</label>
            <input type="text" name="src" id="switch-src" class="text ui-widget-content ui-corner-all"/>
            <label for="alt">Alternate Text</label>
            <input type="text" name="alt" id="switch-alt" class="text ui-widget-content ui-corner-all"/>
            <label for="title">Title</label>
            <input type="text" name="title" id="switch-title" class="text ui-widget-content ui-corner-all"/>
        </fieldset>
    </form>
    <form id="switch-form" action="<?php echo $ajaxUploadUrl; ?>" method="POST" enctype="multipart/form-data">
        <label for="upload">Upload</label>
        <input type="file" name="upload" id="switch-upload" class="ui-widget-content ui-corner-all"/>
        <!--<input type="submit" value="Upload"/>-->
    </form>
    </div>
    <div id="switchBox-album">
        <!-- Facebook album -->
        <div id="fb-album"></div>
        <fb:login-button show-faces="false" width="200" max-rows="0" id="fb-login" perms="user_photos" class="hidden" onlogin="FB.getLoginStatus(sessionResponse);"></fb:login-button>
        <button id="fb-disconnect">Disconnect</button>
    </div>
</div>
<?php } ?>

<?php
    echo file_get_contents("xml/body.xml");
?>

</body> 
</html> 
