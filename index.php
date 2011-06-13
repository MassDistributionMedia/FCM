<?php

/* index.php
http://j.mp/fcm-p1 */

/* TODO: SECURITY! CURRENT CODE IS VERY INSECURE!
 Need to implement nonces and session so the content cannot be hacked by directly calling our pushChanges.php
 This will either require a DB or modifying server privileges so the XML folder is password protected. DB is still more secure, however.
 Okay. Should we go wtih MySQL, PostgreSQL, or maybe even xQuery?
DB requires installation and more configuration.
XML is easier to distribute, though the password-protected the xml folder still requires a bit of configuration.
If you go with DB, I prefer MySQL.
Humm....
Password protected like a protected directory?
*/

phpinfo();

require_once('config.php');
require_once('./src/facebook.php');

global $config;

session_start();
if( isset($_REQUEST['signed_request']) )
	$_SESSION['signed_request'] = $_REQUEST['signed_request'];

$facebook = new Facebook(array(
    'appId'  => $config['appId'],
    'secret' => $config['appSecret'],
    'cookie' => true,
));

$signed_request = $facebook->getSignedRequest();

$page_id = '';
$page_admin = false;
$like_status = false;

if( $signed_request['page'] ) {
	$page_id = $signed_request['page']['id'];
    $page_admin = $signed_request['page']['admin'];
    $like_status = $signed_request['page']['liked'];
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

/**
 *  Given a file, i.e. /css/base.css, replaces it with a string containing the
 *  file's mtime, i.e. /css/base.1221534296.css.
 *  
 *  @param $file  The file to be loaded.  Must be an absolute path (i.e.
 *                starting with slash).
 */
function auto_version($file)
{
	if( !file_exists($file) )
		return $file;
	
	$mtime = filemtime($file);
	return preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $file);
	
	//if(strpos($file, '/') !== 0 || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file))
	//	return $file;
	
	//$mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $file);
	//return preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $file2);
}


?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="http://www.facebook.com/2008/fbml">

<head> 
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" /> 

<link href="<?php echo auto_version('style/reset.css');?>" rel="stylesheet" type="text/css" /> 
<link href="<?php echo auto_version('style/style.css');?>" rel="stylesheet" type="text/css" />
 
<title>FCM Developer Page</title>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.0/jquery.min.js"></script>
<script type="text/javascript" src="js/jquery.cycle.all.min.js"></script>

<?php if( $page_admin ) { ?>
<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/jquery-ui.min.js"></script>
<script type="text/javascript" src="js/jquery.form.min.js"></script>
<script type="text/javascript" src="js/json2.min.js"></script>

<script type="text/javascript" src="<?php echo auto_version('js/adminEdit.js');?>"></script>

<link href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.12/themes/smoothness/jquery-ui.css" rel="stylesheet" type="text/css"/>
<link href="<?php echo auto_version('style/admin.css');?>" rel="stylesheet" type="text/css" />
<?php } ?>

<script type="text/javascript">
// This code is for the featured slider functionality.
$j = jQuery.noConflict();

$j(document).ready(function() {
    $j("#featured-buttons a").click(function(e){
        $j("#featured-slides li").removeClass("selected");
        $j($j(e.target).closest("a").attr("href")).addClass("selected");
        
        $j("#featured-buttons li").removeClass("selected");
        $j(e.target).parents("#featured-buttons li").addClass("selected");
        e.preventDefault();
    });
});
</script>


</head> 

<body>
<?php if( $page_admin ) { ?>
<div id="cms-buttons">
    <button id="revertChanges">Revert</button>
    <button id="pushChanges">Save</button>
    <button id="hotspotTour">Tour</button>
</div>
<?php } ?>
<div id="fb-root"></div>
<script type="text/javascript">
<?php if( $page_admin ) { ?>
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
<?php } ?>

window.fbAsyncInit = function() {
    FB.init({appId: '<?php echo $config['appId']; ?>', status: true, cookie: true,
             xfbml: true});
    FB.Canvas.setSize();
    
    window.setTimeout(function() {
        FB.Canvas.setAutoResize();
    }, 1000);
<?php if( $page_admin ) { ?>
    
    FB.getLoginStatus(sessionResponse);
    $j("#fb-disconnect").click(function(){FB.api({method:'Auth.revokeAuthorization'}, sessionResponse);});
<?php } ?>
};
  (function() {
    var e = document.createElement('script'); e.async = true;
    e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
    e.async = true;
    document.getElementById('fb-root').appendChild(e);
  }());
</script>

<?php if( $page_admin ) { ?>
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
