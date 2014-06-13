<?php
require_once('./global.php');
global $vbulletin;
$table_prefix=TABLE_PREFIX;
$posting=false;
$count=0;
$time=time();
$firstpost=0;
$lastpost=0;
$attach=0;
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


$userid=intval($vbulletin->userinfo['userid']);
$username=$vbulletin->userinfo['username'];
if($_POST['posting']&&intval($_POST['posting'])==1)
	$posting=true;
$attachment_array="";
	for($i=1;$_POST['attachment'.$i];$i++)
	{
		if($_POST['attachment'.$i]&&!is_null($_POST['attachment'.$i]))
		{
			$count++;
			$attachmentid=$_POST['attachment'.$i];				
			if($posting)
			{
				$posttext=$_POST['textarea'.$i]."\n\r\n\r[ATTACH=CONFIG]".$attachmentid."[/ATTACH]";
				$sql="INSERT INTO ".$table_prefix."post(threadid,parentid,username,userid,title,dateline,pagetext,allowsmilie,showsignature,ipaddress,iconid,visible,attach,infraction,reportthreadid) VALUES(".$threadinfo['threadid'].",".$threadinfo['firstpostid'].",'".$username."',".$userid.",'',".($time+$i).",'".$posttext."',1,0,'".$_SERVER["REMOTE_ADDR"]."',0,1,1,0,0)";
				$vbulletin->db->query($sql);
$postid = $vbulletin->db->insert_id();
				if($firstpost==0)
					$firstpost=$postid;
				$lastpost=$postid;
				$vbulletin->db->query("UPDATE ".$table_prefix."attachment SET posthash='',contentid=".$postid." WHERE attachmentid=".$attachmentid);
				$attach++;
			}
			else
			{
				if(strlen($attachment_array)==0)
					$attachment_array=$attachmentid;
				else
					$attachment_array.=",".$attachmentid;
				$posttext=$posttext.$_POST['textarea'.$i]."\n\r\n\r[ATTACH=CONFIG]".$attachmentid."[/ATTACH]\n\r\n\r";
				$attach++;
			}				
				
		}
	}
	$postercount=1;
	if($count!=0)
	{
		$result=$vbulletin->db->query_first("SELECT count(*) as postercount from ".$table_prefix."post WHERE threadid=".$threadinfo['threadid']." AND userid=".$userid);
if(intval($result['postercount'])>0)
		$postercount=0;
		if($posting)
			$replycount=$attach;
		else
			$replycount=1;
		if(!$posting)
		{				
			$vbulletin->db->query("INSERT INTO ".$table_prefix."post(threadid,parentid,username,userid,title,dateline,pagetext,allowsmilie,showsignature,ipaddress,iconid,visible,attach,infraction,reportthreadid) VALUES(".$threadinfo['threadid'].",".$threadinfo['firstpostid'].",'".$username."',".$userid.",'',".$time.",'".$posttext."',1,0,'".$_SERVER["REMOTE_ADDR"]."',0,1,".$attach.",0,0)");
	$postid = $vbulletin->db->insert_id();
			$firstpost=$postid;	
			$lastpost=$postid;
			$sql="UPDATE ".$table_prefix."attachment SET posthash='',contentid=".$postid." WHERE attachmentid IN (".$attachment_array.")";
			$vbulletin->db->query($sql);
		}
		$vbulletin->db->query("UPDATE ".$table_prefix."thread SET attach=attach+".$attach.",lastpostid=".$postid.",lastpost=".$time.",replycount=replycount+".$replycount.",lastposter='".$username."',lastposterid=".$userid.",postercount=postercount+".$postercount." WHERE threadid=".$threadinfo['threadid']);
		if($lastpost==0)
			$lastpost=$firstpost;
		$vbulletin->db->query("UPDATE ".$table_prefix."user SET posts=posts+".$replycount.",lastpost=".$time.",lastpostid=".$lastpost." WHERE userid=".$userid);
		
	}
	if($firstpost)
	{
		$firstpostid=$firstpost;
		echo"
		<script type=\"text/javascript\">	
		if(window.opener)	
		{	
			window.opener.run_parent('showthread.php?t=".$threadinfo['threadid']."&p=".$firstpostid."#post".$firstpostid."');
			window.close();	
		}
		else
		{
			window.location='showthread.php?t=".$threadinfo['threadid']."&p=".$firstpostid."#post".$firstpostid."';
		}
	
		</script>";
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
