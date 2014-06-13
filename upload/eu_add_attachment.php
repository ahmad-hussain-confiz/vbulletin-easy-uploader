

<?php
require_once('./global.php');
global $vbulletin;
$table_prefix=TABLE_PREFIX;
$count=0;
if($_GET['t']&&($threadid=intval($_GET['t']))>0)
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



?>
<head>
<style type="text/css">
.width-990 { width:800px; float:left; font-size:12px; font-family:Arial, Helvetica, sans-serif;}

.width-990 ul{ margin:0; padding:0; list-style:none; width:800px;}
.width-990 ul li{ margin:0 15px 20px 0; padding:0; width:185px; list-style:none; float:left;}
.width-990 ul li img{ margin:0; height:185px; padding:0; max-width:185px; max-height:185px; overflow:hidden; text-align:center;
}

.width-990 ul li textarea { margin:10px 0 0; width:185px; height:70px; border:1px solid #ccd0d4; font-size:12px; font-family:Arial, Helvetica, sans-serif;}

.cb {clear:both;}

input{ float:left;}

input.submit {background:#0362af; color:#FFFFFF; font-weight:bold; margin:10px 0px 0px ; border:#0f6bb7 1px solid; border-radius:4px; -moz-border-radius: 4px; -webkit-border-radius: 4px; padding:0px 10px; cursor:pointer; float:left;}


div.submit { float:left; margin:17px 15px 0 0;}
.thumbnails{text-align:center; height:185px;width: 185px; overflow: hidden;}
</style>
</head>
<form method="post" action="eu_submit_attachment.php?t=<?php echo $threadinfo['threadid'];?>">
<div class="width-990">
  <ul>
<?php
	for($i=1;$_POST['attachment'.$i];$i++)
	{
		if($_POST['attachment'.$i]&&!is_null($_POST['attachment'.$i]))
		{
			$count++;
			$attachmentid=$_POST['attachment'.$i];
			$attachmenturl=$_POST['attachmenturl'.$i];
			echo "<li>
      <div class=\"thumbnails\"><img src=\"".$attachmenturl."\" /></div><textarea name=textarea".$count." id=textarea".$count."></textarea><input type=\"hidden\" name=\"attachment".$count."\" value=\"".$attachmentid."\" /></li>";			
		}
	}
?>
</ul>
  <div class="cb"></div>
<?php
	if($count!=0)
	{
		?>
		<div class="submit">
		  <input type="checkbox" name="posting" class="checkbox" value="1" /><span>Add in different Posts</span>
		  	
		    
		  </div>
		  <input type="submit" class="submit" value="Upload" />
		<?php
	}
	else
	{
		echo"
		<script type=\"text/javascript\">		
		if(window.opener)	
		{
			window.opener.run_parent('showthread.php?t=".$threadinfo['threadid']."');
			window.close();	
		}
		else
		{
			window.location='showthread.php?t=".$threadinfo['threadid']."';
		}
		</script>";	
	}
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
</div>
</form>
<?php
?>
