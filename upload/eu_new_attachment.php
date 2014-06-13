<?php 
require_once('./global.php');
global $vbulletin;
$table_prefix=TABLE_PREFIX;
if($_GET['t']&&($threadid=intval($_GET['t']))>0&&$vbulletin->options['easy_uploader_enable'])
{
if ($threadinfo['isdeleted'] OR (!$threadinfo['visible'] AND !can_moderate($threadinfo['forumid'], 'canmoderateposts')))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}

if (!$foruminfo['allowposting'] OR $foruminfo['link'] OR !$foruminfo['cancontainthreads'])
{
	eval(standard_error(fetch_error('forumclosed')));
}

if (!$threadinfo['open'])
{
	if (!can_moderate($threadinfo['forumid'], 'canopenclose'))
	{
		$vbulletin->url = fetch_seo_url('thread', $threadinfo);
		eval(standard_error(fetch_error('threadclosed')));
	}
}

$forumperms = fetch_permissions($foruminfo['forumid']);
if (($vbulletin->userinfo['userid'] != $threadinfo['postuserid'] OR !$vbulletin->userinfo['userid']) AND (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewothers']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canreplyothers'])))
{
	print_no_permission();
}
if (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canview']) OR !($forumperms & $vbulletin->bf_ugp_forumpermissions['canviewthreads']) OR (!($forumperms & $vbulletin->bf_ugp_forumpermissions['canreplyown']) AND $vbulletin->userinfo['userid'] == $threadinfo['postuserid']))
{
	print_no_permission();
}

// check if there is a forum password and if so, ensure the user has it set
verify_forum_password($foruminfo['forumid'], $foruminfo['password']);

// *********************************************************************************
// Tachy goes to coventry
if (in_coventry($threadinfo['postuserid']) AND !can_moderate($threadinfo['forumid']))
{
	eval(standard_error(fetch_error('invalidid', $vbphrase['thread'], $vbulletin->options['contactuslink'])));
}
$result=$vbulletin->db->query_read("SELECT * FROM ".$table_prefix."attachmenttype");
$extensions="";
$max_file_size=0;
$width=0;
$height=0;
while ($arr = $vbulletin->db->fetch_array($result)) {
	
	if(strlen($extensions)>0)
	{
		$extensions.=",".$arr['extension'];
	}
	else
	{
		$extensions.=$arr['extension'];
	}
	if($arr['size']&&intval($arr['size'])>$max_file_size)
		$max_file_size=intval($arr['size']);
	if($arr['width']&&intval($arr['width'])>$width)
	{
		$width=intval($arr['width']);
		$height=intval($arr['height']);
	}
	if($arr['height']&&intval($arr['height'])>$height)
	{
		$width=intval($arr['width']);
		$height=intval($arr['height']);
	}
}
$max_file_size_mb=$max_file_size/1048576;
$max_attachments=10;
if($vbulletin->options['limit_attachments_nr']&&is_integer($vbulletin->options['limit_attachments_nr']))
	$max_attachments=intval($vbulletin->options['limit_attachments_nr']);
?>
<html>
<head>
<!-- Load Queue widget CSS and jQuery -->

<style type="text/css">@import url(./plupload/js/jquery.plupload.queue/css/jquery.plupload.queue.css);</style>

<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>

<!-- Third party script for BrowserPlus runtime (Google Gears included in Gears runtime now) -->

<script type="text/javascript" src="http://bp.yahooapis.com/2.4.21/browserplus-min.js"></script>


<script type="text/javascript" src="./plupload/js/plupload.js"></script>
<script type="text/javascript" src="./plupload/js/plupload.gears.js"></script>
<script type="text/javascript" src="./plupload/js/plupload.silverlight.js"></script>
<script type="text/javascript" src="./plupload/js/plupload.flash.js"></script>
<script type="text/javascript" src="./plupload/js/plupload.browserplus.js"></script>
<script type="text/javascript" src="./plupload/js/plupload.html4.js"></script>
<script type="text/javascript" src="./plupload/js/plupload.html5.js"></script>


<!-- Load plupload and all it's runtimes and finally the jQuery queue widget -->

<script type="text/javascript" src="./plupload/js/plupload.full.js"></script>

<script type="text/javascript" src="./plupload/js/jquery.plupload.queue/jquery.plupload.queue.js"></script>

<?php echo "
<script type=\"text/javascript\">

var maxfiles=".$max_attachments.";
// Convert divs to queue widgets when the DOM is ready

$(function() {

	var uploader = $('#uploader').pluploadQueue();
	var count=0;
	var firstattachment=0;
	$(\"#uploader\").pluploadQueue({

        // General settings

        runtimes : 'gears,flash,silverlight,browserplus,html5',

        url : 'eu_upload.php?t=".$threadinfo['threadid']."',

        max_file_size : '".$max_file_size_mb."mb',

        unique_names : true,

 	max_file_count : maxfiles,

        // Resize images on clientside if we can

        resize : {width : ".$width.", height : ".$height.", quality : 90},

 

        // Specify what files to browse for

        filters : [

            {title : \"Image files\", extensions : \"".$extensions."\"},


        ],

 

        // Flash settings

        flash_swf_url : './plupload/js/plupload.flash.swf',

 

        // Silverlight settings

        silverlight_xap_url : './plupload/js/plupload.silverlight.xap',
	
	init : {
		
            FilesAdded: function(up, files) {
		var file_count=files.length;
                plupload.each(files, function(file) {		    
                    if (up.files.length > maxfiles) {
			file_count--;
			if((up.files.length-file_count)>maxfiles)
                        	up.removeFile(file);
                    }                    
                });
		$('#total_images').text(''+up.files.length);
                if (up.files.length >= maxfiles) {
                    $('#uploader_browse').hide();
                }
            },
            FilesRemoved: function(up, files) {
                if (up.files.length < maxfiles) {
                    $('#uploader_browse').fadeIn();
                }
		$('#total_images').text(''+up.files.length);
            },

	    FileUploaded: function(up, file,response) {
		count++;
		//alert(response.response);
		var obj = jQuery.parseJSON(response.response);
                $(\"#images\").append('<input type=\"hidden\" name=\"attachment'+count+'\" value=\"'+obj.attachmentid+'\" />');
		$(\"#images\").append('<input type=\"hidden\" name=\"attachmenturl'+count+'\" value=\"'+obj.attachmenturl+'\" />');
		if(count==1)
			firstattachment=obj.attachmentid;

	    },

	    UploadComplete: function(up, files) {
		$(\"#images\").append('<input type=\"hidden\" name=\"firstattachment\" value=\"'+firstattachment+'\" />');
		$('form')[0].action='eu_add_attachment.php?t=".$threadinfo['threadid']."';                    
		//document.getElementById('button').style.visibility='visible';	
		//alert($(\"#images\").innerhtml);
		$(\"#submit\").click();
	    },

	    Init: function(up) {
		document.getElementById('button').style.visibility='hidden';
	}

        }

    });
   
});

</script>";
?>
</head>
<body>

<form method="post" action="eu_add_attachment.php?t=<?php echo $threadinfo['threadid'];?>" enctype="multipart/form-data" id="images_form" name="images_form">

Total Images : <span id="total_images">0</span>
    <div id="uploader">
        
    </div>
	<div id="images" style="visibility:hidden">
</div>
<div id="button" style="visibility:hidden">
	<input type="submit" name="submit" value="Submit" id="submit" />
</div>
</form>
</body>
</html>
<?php
}
else
{
	echo"
		<script type=\"text/javascript\">		
		if(window.opener)	
		{
			window.opener.run_parent('forum.php');
			window.close();	
		}
		else
		{
			window.location='forum.php';
		}	
	
		</script>";
}
?>
