<?php
theme::form_start();
theme::header_start('Clone Repro','clone a git repro directly into modules.');
theme::header_button_back();
theme::header_end();

o::hr(0,12); /* 4px padding top and bottom */

theme::start_form_section('Command');
o::text('command',$record->help);
theme::end_form_section('Complete git clone command. ie. git clone git@bitbucket.org:exampleuser/gitreproexample.git');
?>
<pre id="output" style="height: 200px; overflow-y: auto;"></pre>
<?
o::hr(0,12); /* 4px padding top and bottom */
?>
<p class="text-right">
	<a class="btn btn-primary js-process">Process</a>
</p>
<script>
document.addEventListener("DOMContentLoaded", function(event) {
	$('.js-process').click(function() {
		if (!submit()) {
			window.history.go(-1);
		}
	});
});

/* pretty much hard coded to this form */
function submit() {
	$('#output').html('<i class="fa fa-spinner fa-pulse"></i> Running');
	$.noticeRemoveAll();
	
	var reply = mvc.request({data: {"command": $('#command').val()}, url: controller_path + '-save', dataType: 'json'});

	if (reply === undefined) {
		reply.err = true;
		$.noticeAdd({"text":"Error: Running Command","stay":true,"type":"danger"});
	} else {
		$('#output').html(reply.msg);
	}

	return reply.err;
}
</script>
<style>
#output {
	font: bold 15px/16px "Courier New", Courier, mono;
	color: #ffffff;
	background-color: #060732;
	tab-size: 2;
	padding: 12px;
}
</style>