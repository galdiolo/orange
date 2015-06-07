<?php
theme::header_start('Upload Module',format_size_units($bytes).' or less');
theme::header_button_back();
theme::header_end();

o::hr(0,12); /* 4px padding top and bottom */
// http://www.sitepoint.com/html5-ajax-file-upload/
?>
<form id="upload" action="<?=$controller_path ?>/upload" method="POST" enctype="multipart/form-data">
  <fieldset>
    <input type="hidden" id="MAX_FILE_SIZE" name="MAX_FILE_SIZE" value="<?=$bytes ?>" />
    <div>
      <input type="file" id="fileselect" name="fileselect[]" multiple="multiple" />
      <div id="filedrag">drop zipped modules here</div>
    </div>
    <div id="submitbutton">
      <button type="submit">Upload Files</button>
    </div>
  </fieldset>
</form>

<h4>Status</h4>
<div id="messages"></div>
<style>
#filedrag {
	display: none;
	font-weight: bold;
	text-align: center;
	padding: 1em 0;
	margin: 1em 0;
	border: 2px dashed #555;
	border-radius: 7px;
	cursor: default;
	height: 100px;
	line-height: 60px;
	font-size: 18px;
}

#filedrag.hover {
	border-style: solid;
}
#messages {
  max-height: 100px;
	height: 100px;
  overflow-y: auto;
  border: 1px solid #eee;
  border-radius: 4px;
}
#messages p {
  margin: 0;
}
#fileselect {
	display: none;
}
</style>