<?php
theme::form_start();
theme::header_start('Edit composer.json file');
theme::header_end();

o::hr(0,12); /* 4px padding top and bottom */
?>
<textarea id="composer" style="width: 100%;height: 75vh" spellcheck="false"><?=$composer ?></textarea>
<p class="text-right">
  <a class="btn btn-default" onclick="window.history.go(-1);">Cancel</a>&nbsp;&nbsp;&nbsp;
  <a class="btn btn-primary js-submit">Submit</a>
</p>
<script>
/*
this all needs to be cleaned up - maybe make a universal save function
and universal way to call editPostAction with and without flash message.
we don't want to set flash msgs with just a save since the javascript 
pops it up not php via wallet
*/
document.addEventListener("DOMContentLoaded", function(event) {
	$('.js-submit').click(function() {
		if (submit(true)) {
			window.history.go(-1);
		}
  });

	keymage('defmod-s', function(){
		submit(false);
		return false;
	});

	keymage('tab', function() {
		var ta = document.getElementById("composer");
		
		var myValue = "\t";
		var startPos = ta.selectionStart;
		var endPos = ta.selectionEnd;
		var scrollTop = ta.scrollTop;
		ta.value = ta.value.substring(0, startPos) + myValue + ta.value.substring(endPos,ta.value.length);
		ta.focus();
		ta.selectionStart = startPos + myValue.length;
		ta.selectionEnd = startPos + myValue.length;
		ta.scrollTop = scrollTop;
		
		return false;
	});
	
	/* keep these from remapping to history actions */
	keymage('defmod-[', function(){ return false; });
	keymage('defmod-]', function(){ return false; });
});

/* pretty much hard coded to this form */
function submit(is_redirecting) {
	$.noticeRemoveAll();
	
	var composer =  $('#composer').val();
	var is_valid = false;
	
	if (!IsJsonString(composer)) {
	  $.noticeAdd({"text":"Error: Invalid json.","stay":true,"type":"danger"});
	} else {
	  is_valid = true
	}
	
	if (is_valid) {
	  var reply = mvc.request({data: {"composer":composer,"is_redirecting":is_redirecting}, url: controller_path + '_save', dataType: 'json'});
	
	
	  if (reply === undefined) {
	    $.noticeAdd({"text":"Error: Saving","stay":true,"type":"danger"});
	  } else {
	    if (reply.err) {
	      reply = mvc.flash_msg_format(reply);
	      $.noticeAdd({"text":"Error: Saving composer.json file","stay":true,"type":"danger"});
	    } else {
	      $.noticeAdd({"text":"Saved composer.json","stay":false,"type":"info"});
	    }
	  }
	}
	
	return is_valid;
}

function IsJsonString(str) {
	try {
		JSON.parse(str);
	} catch (e) {
		return false;
	}
	
	return true;
}

</script>
<style>
#composer {
	font: bold 15px/16px "Courier New", Courier, mono;
	color: #ffffff;
	background-color: #060732;
	tab-size: 2;
	padding: 12px;
}
</style>