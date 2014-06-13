#VBulletin Easy Uploader

This plugin will help you to upload each picture in a single post. You can upload images in one go and post them in individual posts.

## Installation

1- Upload data from upload directory to your forum home directory

2- Install easy_uploader.xml in add/import product menu

3- Go to vbulletin options -> easy uploader and enable uploader and set number of attachments allowed


you can also add this code in any template like in quick reply or in newreply

	<vb:if condition="$vboptions['easy_uploader_enable']"> 
		<script type="text/javascript">
		function newPopup(url) {
			var popupWindow=null;
			popupWindow = window.open(url ,'popUpWindow','height=400,width=800,left=10,top=10,resizable=no,scrollbars=yes,toolbar=yes,menubar=no,location=no,directories=no,status=no,directories=no');
			popupWindow.moveTo(150, 150);
		}
		</script>
		<a href="JavaScript:newPopup('eu_new_attachment.php?t={vb:raw threadinfo.threadid}');" class="newcontent_textcontrol" style="margin:0px 0px 10px 7px;">Upload Images</a>
	</vb:if>



## Contributors

1. [Ahmad Hussain](https://github.com/ahmad-hussain-confiz)
2. [Usman Ali](https://github.com/usman-ali-confiz)
3. [Kinaan Khan Sherwani](https://github.com/kinaan-khan-confiz)


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request