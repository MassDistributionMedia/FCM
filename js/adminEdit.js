/* adminEdit.js
 http://j.mp/fcm-p1 */

$j = jQuery.noConflict();

function makeEditable(event) {
    if( !makeEditable.editBox ) {
        makeEditable.editBox = $j('<textarea></textarea>', {'id':'editBox'})
            .css({'margin':'-1px 0 0 -1px'}).click(function(){return false;}).change(editableChange)
            .blur(function(e){makeEditable.editBox.hide();})
            .hide();
    }
    
    var edit = makeEditable.editBox;
    var view = $j(event.target).closest('.editable');
    var offset = view.offset();
    
    edit.width(view.width()+1).height(view.height()+1).val(view.html())
        .css({'left':offset.left+'px','top':offset.top+'px'}).insertAfter(view).show().focus();
    
    edit.editTarget = view;
    
    $j(document).one("click", function(){makeEditable.editBox.hide();});
}

function editableChange(event) {
    var edit = makeEditable.editBox;
    var target = edit.editTarget;
    
    target.html(edit.val()).addClass('modified').addClass('textModified');
    
    $j("#revertChanges,#pushChanges").removeAttr("disabled");
    
    if( target.html() == target.data('origHtml') )
        target.removeClass('textModified');
}

function makeSwitchable(event) {
    if( !makeSwitchable.switchBox ) {
        makeSwitchable.switchBox = $j("#switchBox");
        makeSwitchable.allFields = $j([]).add("#switch-src,#switch-alt,#switch-title");
    }
    
    var edit = makeSwitchable.switchBox;
    var img = $j(event.target).closest('.switchable');
    
    $j("#switch-src").val(img.attr('src'));
    $j("#switch-alt").val(img.attr('alt'));
    $j("#switch-title").val(img.attr('title'));
    
    edit.dialog("open");
    
    edit.switchTarget = img;
    
    //$j(document).one("click", function(){makeSwitchable.switchBox.hide();});
}

function switchableChange() {
    var edit = makeSwitchable.switchBox;
    var img = edit.switchTarget;
    var newSrc = $j("#switch-src").val();
    var newTitle = $j("#switch-title").val();
    var newAlt = $j("#switch-alt").val();
    
    img.attr('src',newSrc).attr('title',newTitle).attr('alt',newAlt)
        .addClass('modified').addClass("imgModified");
    
    if( newSrc == img.data('origSrc') && newAlt == img.data('origAlt') && newTitle == img.data('origTitle') )
        img.removeClass('imgModified');
    
    $j("#revertChanges,#pushChanges").removeAttr("disabled");
    edit.dialog("close");
}

function revertChanges() {
    $j('.textModified').each(function(){
        $j(this).html($j(this).data('origHtml'));
    }).addClass("modified,textModified");
    
    $j('.imgModified').each(function(){
        var img = $j(this);
        img.attr('src',img.data('origSrc')).attr('title',img.data('origTitle')).attr('alt',img.data('origAlt'));
    }).addClass("modified,imgModified");
    
    if( !pushChanges.pushed ) {
        $j(".modified").removeClass("modified,textModified,imgModified");
        $j("#pushChanges,#revertChanges").attr("disabled","disabled");
    }
    else {
        $j("#pushChanges").removeAttr("disabled");
        $j("#revertChanges").attr("disabled","disabled");
    }
    
    //$j("#revertChanges").attr("disabled","disabled");
    //$j("#pushChanges").attr("disabled","disabled");
}

function pushChanges() {
    pushChanges.pushed = true;
    
    var changed = $j('.modified');
    pushChanges.actions = [];
    
    changed.each(function(){
        var el = $j(this);
        var mods = {actions:[],id:el.attr('id'),node:this.nodeName.toLowerCase()};
        if( el.hasClass("textModified") ) {
            mods.actions.push("htmlModified");
            mods.newHtml = el.html();
        }
        
        if( el.hasClass("imgModified") ) {
            mods.actions.push("imgModified");
            mods.newSrc = el.attr('src');
            mods.newAlt = el.attr('alt');
            mods.newTitle = el.attr('title');
        }
        
        if( mods.actions.length > 0 )
            pushChanges.actions.push(mods);
    });
    
    console.log(pushChanges.actions);
    
    $j("#pushChanges").attr("disabled","disabled");
    
	var src = document.location.href.replace(/\/[^\/]*$/, "/pushChanges.php");
	
    if( pushChanges.actions.length > 0 )
        $j.post(src, {actions:JSON.stringify(pushChanges.actions)},function(data){
			console_post(data);
		});
}


/*
 * Warning levels:
 *	normal:		0
 *  success:	1
 *	error:		-1
 */
function console_post(message, warning) {
	$j('<div class="console-item"></div>').html(message).prependTo("#adminConsole")
		.delay(7000).fadeOut(2000, function(){$j(this).remove();});
}

function adminOverlayCreate(e){
    var $th = $j(this), off = $th.offset(), dim = {width:$th.width(),height:$th.height()};
    if( $th.hasClass('editable') ) {
        off.top -= 6;
        dim.height += 12;
    }
    
    var ov = $j('<div class="admin-overlay"></div>').css({width:dim.width+'px',
            height:dim.height+'px',top:off.top+'px',left:top.left+'px',opacity:'0.6'})
        .data('editTarget',$th).dblclick(function(){
                $j(this).data('editTarget').dblclick();
                $j(this).remove();
        }).mouseleave(function(){
                $j(this).addClass('queueRemove').animate({opacity:0},400,function(){
                        if( $j(this).hasClass('queueRemove') )
                            $j(this).remove();
                    }).mouseenter(function(){
                            $j(this).removeClass('queueRemove').css({opacity:'0.6'});
                    });
        }).insertAfter($th);
}

$j(document).ready(function() {
    $j("p,h1,h2").addClass('editable').dblclick(makeEditable).each(function(){
        $j(this).data('origHtml',$j(this).html());
    });
    
    $j("img").addClass('switchable').dblclick(makeSwitchable).each(function(){
        var $th = $j(this);
        var src=$th.attr('src'),alt=$th.attr('alt'),title=$th.attr('title');
        $th.data('origSrc',(src)?src:'');
        $th.data('origAlt',(alt)?alt:'');
        $th.data('origTitle',(title)?title:'');
    });
    
    //$j('.editable,.switchable').mouseenter(adminOverlayCreate);
    
    $j("#pushChanges").click(pushChanges).attr("disabled","disabled");
    $j("#revertChanges").click(revertChanges).attr("disabled","disabled");
    
    $j("#switchBox").dialog({
        autoOpen:false,
        width:400,
        modal:true,
        buttons:{ 'Submit':switchableChange },
        Cancel: function() { $j(this).dialog("close"); },
        close: function() { makeSwitchable.allFields.val(""); }
    }).tabs().removeClass("hidden");
    
    $j("#switch-form").ajaxForm({
        beforeSubmit: function(){ console.log("Uploading file..."); },
        success: function(xhr){ console.log(xhr); $j("#switch-src").val(xhr); }
    }).submit(function(){
        $j(this).ajaxSubmit();
        return false;
    });
    
    $j("#switch-upload").change(function(){
        console.log("File changed.");
        $j("#switch-form").submit();
    });
	
	$j("#hotspotTour").click(tour);
	
	$j('<div id="adminConsole"></div>').insertBefore("div.body");
});

function tour() {
    tour.stagTime = 333;
    tour.waitDur = 333;
    tour.animDur = 500;
    
    $j('.editable:visible,.switchable:visible').each(function(i){
        var $th = $j(this);
        var off = $th.offset();
        var pulse = $j('<div></div>');
        
        pulse.css({width:$th.width()+'px',height:$th.height()+'px',left:off.left+'px',top:off.top+'px',
                opacity:'0',backgroundColor:'#FFC',position:'absolute',display:'block',zIndex:'5'})
            .appendTo(".body");
        
        pulse.delay(i*tour.stagTime).removeClass('hidden').animate({
                left:'-=10px',
                top:'-=10px',
                width:'+=20px',
                height:'+=20px',
                opacity:'0.75'
        },{duration:tour.animDur}).delay(tour.waitTime).animate({
                left:'+=10px',
                top:'+=10px',
                width:'-=20px',
                height:'-=20px',
                opacity:'0'
        },{duration:tour.animDur,complete:function(){$j(this).remove();}});
    });
}
