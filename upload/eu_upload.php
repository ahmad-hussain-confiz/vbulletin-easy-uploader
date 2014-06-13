<?php
require_once('./global.php');
global $vbulletin;
$table_prefix=TABLE_PREFIX;
$local_path=$vbulletin->options['bburl'];
$no_of_uploads=10;
$base_disk_path=DIR;
$attach_dir_path=$vbulletin->options['attachpath'];
$thumbnail_quality=95;
function vbmkdir2($path, $mode = 0777) {
    if (is_dir($path)) {
        if (!(is_writable($path))) {
            chmod($path, $mode);
        }
        return true;
    } else {
        $oldmask = umask(0);
        $partialpath = dirname($path);
        if (!vbmkdir2($partialpath, $mode)) {
            return false;
        } else {
            return mkdir($path, $mode);
        }
    }
}
function add_watermark($attachpath,$filename,$filedataid)
{
	global $vbulletin;

	$extension = substr($filename, strrpos($filename, ".")); 
	if (strtolower($extension) == ".jpg" || strtolower($extension) == ".jpeg") {
	$watermark_path="images/watermark.png";
	if($vbulletin->options['easy_uploader_watermark_path']&&!is_null($vbulletin->options['easy_uploader_watermark_path']))
		$watermark_path=$vbulletin->options['easy_uploader_watermark_path'];
	$wm_b_tmp = imagecreatefrompng($watermark_path); 
            $im_a = ""; 
        

            if (strtolower($extension) == ".jpg" || strtolower($extension) == ".jpeg") { 
                $im_a = imagecreatefromjpeg($attachpath); 
            }

            if ($im_a) { 

//####### resize water mark image according to original image 
                $wm_b=$wm_b_tmp;


//###### END of resizing ##################  
                $tempimage = imagecreatetruecolor(imagesx($im_a), imagesy($im_a));  

                imagecopy($tempimage, $im_a, 0, 0, 0, 0, imagesx($im_a), imagesy($im_a));  
                $im_a = $tempimage;  

                 if ($wm_b && imagesx($im_a) > imagesx($wm_b)&&imagesy($im_a)>imagesy($wm_b)&&imagesx($im_a)>=250&&imagesy($im_a)>=150){  

                    imagecopy($im_a, $wm_b, (imagesx($im_a) - imagesx($wm_b)), (imagesy($im_a) - imagesy($wm_b)), 0, 0, imagesx($wm_b), imagesy($wm_b));  
                }  

                if (strtolower($extension) == ".jpg" || strtolower($extension) == ".jpeg") { 
                    imagejpeg($im_a, $attachpath); 
			$vbulletin->db->query("UPDATE forum_filedata SET filesize=".filesize($attachpath)." WHERE filedataid=".$filedataid); 
                }
                if ($wm_b)
                    imagedestroy($wm_b);
                imagedestroy($im_a);
            }
}
}
function fetch_attachment_path_withfiledataid($userid, $base_path, $as_new, $attachmentid = 0, $thumb = false) {
    if ($as_new) { // expanded paths
        $path = $base_path . '/' . implode('/', preg_split('//', $userid, -1, PREG_SPLIT_NO_EMPTY));
    } else {
        $path = $base_path . '/' . $userid;
    }

    if ($attachmentid) {
        if ($thumb) {
            $path .= '/' . $attachmentid . '.thumb';
        } else {
            $path .= '/' . $attachmentid . '.attach';
        }
    }

    return $path;
}

function fetch_attachment_path1($userid, $base_path, $as_new) {
    if ($as_new) { // expanded paths
        $path = $base_path . '/' . implode('/', preg_split('//', $userid, -1, PREG_SPLIT_NO_EMPTY));
    } else {
        $path = $base_path . '/' . $userid;
    }


    return $path;
}

function move_file($src_file_path,$dest_file_path,$full_path) {
global $vbulletin,$thumb_quality;
        if (vbmkdir2($full_path, 0777)) {		
		if(move_uploaded_file($src_file_path,$dest_file_path))
		{
			chmod($dest_file_path,0755);		
			return 0;	
		}
		else{
			return 1;
		}
	}
	else{
		return 1;
	}
}

function generate_thumbnail($filedataid)
{
global $vbulletin,$thumbnail_quality;
	require_once(DIR . '/includes/class_image.php');
	$image =& vB_Image::fetch_library($vbulletin);
	$validtypes =& $image->thumb_extensions;
	$extensions = array();
	foreach ($vbulletin->attachmentcache AS $key => $value)
	{
		$key = strtolower($key);
		if ($key != 'extensions' AND !empty($validtypes["$key"]))
		{
			$extensions[] = "'$key'";
		}
	}
	$extensions = implode(',', $extensions);

	if ($vbulletin->options['imagetype'] != 'Magick' AND !function_exists('imagetypes'))
	{
		//define('CP_REDIRECT', 'misc.php');
		print_stop_message('your_version_no_image_support');
	}

	
	if ($vbulletin->options['attachfile'])
	{
		require_once(DIR . '/includes/functions_file.php');
	}

	$attachments = $vbulletin->db->query_read("
		SELECT
			filedataid, filedata, userid, extension, dateline, CONCAT('file.', extension) AS filename
		FROM " . TABLE_PREFIX . "filedata
		WHERE filedataid=".$filedataid
	);


	while ($attachment = $vbulletin->db->fetch_array($attachments))
	{
		if (!$vbulletin->options['attachfile']) // attachments are in the database
		{
			if ($vbulletin->options['safeupload'])
			{
				$filename = $vbulletin->options['tmppath'] . '/' . md5(uniqid(microtime()) . $attachment['userid']);
			}
			else
			{
				$filename = tempnam(ini_get('upload_tmp_dir'), 'vbthumb');
			}
			$filenum = fopen($filename, 'wb');
			fwrite($filenum, $attachment['filedata']);
			fclose($filenum);
		}
		else
		{
			$filename = fetch_attachment_path($attachment['userid'], $attachment['filedataid']);
		}


		if (!is_readable($filename) OR !@filesize($filename))
		{
			continue;
		}

		$labelimage = ($vbulletin->options['attachthumbs'] == 3 OR $vbulletin->options['attachthumbs'] == 4);
		$drawborder = ($vbulletin->options['attachthumbs'] == 2 OR $vbulletin->options['attachthumbs'] == 4);

		$thumbnail = $image->fetch_thumbnail($attachment['filename'], $filename, $vbulletin->options['attachthumbssize'], $vbulletin->options['attachthumbssize'], $thumbnail_quality, $labelimage, $drawborder);

		// Remove temporary file we used to generate thumbnail
		if (!$vbulletin->options['attachfile'])
		{
			@unlink($filename);
		}

		$attachdata =& datamanager_init('Filedata', $vbulletin, ERRTYPE_SILENT, 'attachment');
		$attachdata->set_existing($attachment);
		$attachdata->set('width', $thumbnail['source_width']);
		$attachdata->set('height', $thumbnail['source_height']);
		if (!empty($thumbnail['filedata']))
		{
			$attachdata->setr('thumbnail', $thumbnail['filedata']);
			$attachdata->set('thumbnail_dateline', TIMENOW);
			$attachdata->set('thumbnail_width', $thumbnail['width']);
			$attachdata->set('thumbnail_height', $thumbnail['height']);
		}
		if (!($result = $attachdata->save()))
		{
			if (!empty($attachdata->errors[0]))
			{
			}
		}
		unset($attachdata);

		if (!empty($thumbnail['imageerror']))
		{						
		}
		else if (empty($thumbnail['filedata']))
		{
		}

		$finishat = ($attachment['filedataid'] > $finishat ? $attachment['filedataid'] : $finishat);
	}
}

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$time=time();
$result=$vbulletin->db->query_read("SELECT * from ".$table_prefix."attachmenttype");
$count=0;
while ($arr = $vbulletin->db->fetch_array($result)) {
	$allowed_filetypes[$count]=$arr['mimetype'];
	$allowed_fileextensions[$count]=$arr['extension'];
}
$attach=0;
$replycount=0;
$userid=0;
$postercount=1;
$firstpostid=0;
$userid=intval($vbulletin->userinfo['userid']);
$username=$vbulletin->userinfo['username'];
$salt=$vbulletin->userinfo['salt'];
if($userid>0&&strlen($username)>0)
{
$result=$vbulletin->db->query_first("SELECT count(*) as postercount from ".$table_prefix."post WHERE threadid=".$threadinfo['threadid']." AND userid=".$userid);
if(intval($result['postercount'])>0)
	$postercount=0;

$textarea="";

if ($_FILES["file"]["name"])
  {
$extension = end(explode(".", $_FILES["file"]["name"]));

$result=$vbulletin->db->query_read("SELECT * from ".$table_prefix."attachmenttype");
$count=0;
$found=false;
while ($arr = $vbulletin->db->fetch_array($result)) {
	if(strcmp($arr['extension'],$extension)==0)
	{	
		if(intval($_FILES["file"]["size"])<=intval($arr['size']))
			$found=true;
	}
}
if($found)
{
$postid=0;

if($postid)
	$replycount++;

list($width, $height, $type, $attr) = getimagesize($_FILES['file']['tmp_name']);

	if(is_null($width)&&(!$width))
		$width=0;
	if(is_null($height)&&(!$height))
		$height=0;
        $filehash = md5(file_get_contents($_FILES['file']['tmp_name']));

$sql = "Select * from ".$table_prefix."filedata where filehash = '$filehash'";
        $result = $vbulletin->db->query_first($sql);
	$found=$result ? $result['filedataid'] : 0;
        $filedataid = $result ? $result['filedataid'] : 0;
        if ($filedataid) {
            $vbulletin->db->query("
				UPDATE ".$table_prefix."filedata
				SET refcount = refcount + 1
				WHERE filedataid = {$filedataid}
			");
        } else {
            // insert into filedata table to get the filedataid auto inc
            $sql = "
				INSERT INTO ".$table_prefix."filedata
				(
					userid,
					dateline,
					thumbnail_dateline,
					filesize,
					filehash,
					extension,
					refcount,
					width,
					height
				)
				VALUES
				(
					" . intval($userid) . ",
					" . $time. ",
					" . $time . ",
					" . intval($_FILES["file"]["size"]) . ",
					'" . addslashes($filehash) . "',
					'" . addslashes($extension) . "',
					1,
					".$width.",
					".$height."
				)
			";

            $vbulletin->db->query($sql);
            $filedataid = $vbulletin->db->insert_id();
        }
$posthash = md5($time . $userid . $salt);
        $vbulletin->db->query("
			INSERT INTO ".$table_prefix."attachment
			(
				filename,
				userid,
				dateline,
				contentid,
				filedataid,
				contenttypeid,
				posthash
			)
			VALUES
			(
				'" . addslashes($_FILES["file"]["name"]) . "',
				" . intval($userid) . ",
				" . $time . ",
				" . $postid . ",
				" . $filedataid . ",
				1,
				'". $posthash ."'
			)
		");
        $attachmentid = $vbulletin->db->insert_id();
	$full_path = fetch_attachment_path1($userid, $attach_dir_path, true);
	$dest_file_path=fetch_attachment_path_withfiledataid($userid, $attach_dir_path, true,$filedataid);	
	$mid=move_file($_FILES["file"]["tmp_name"],$dest_file_path,$full_path);
	$attach++;	
	generate_thumbnail($filedataid);
	if($vbulletin->options['easy_uploader_enable_watermark']&&!is_null($vbulletin->options['easy_uploader_enable_watermark'])&&$vbulletin->options['easy_uploader_watermark_path']&&!is_null($vbulletin->options['easy_uploader_watermark_path']))
	{
		add_watermark($dest_file_path,$_FILES["file"]["name"],$filedataid);
	}

if($attach>0)
{
	die("{\"attachmenturl\" : \"".$local_path."/attachment.php?attachmentid=".$attachmentid."&d=".$time."\",\"attachmentid\" : \"".$attachmentid."\"}");
}//end of attach
else
{
	die('{"jsonrpc" : "2.0", "error" : {"code": 100, "message": "unable to create attachment."}, "id" : "id"}');
}
}//end of $found
}//end of $_FILES["file"]["name"]
}//end of $userid>0

?>
