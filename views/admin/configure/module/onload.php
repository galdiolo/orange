<?php
theme::form_start();
theme::header_start('Edit modules onload.');
theme::header_end();

o::hr(0,12); /* 4px padding top and bottom */

echo '<form action="'.$controller_path.'" id="main-form" method="post" accept-charset="utf-8">';
theme::table_start(['Module','Public'=>'txt-ac','Admin'=>'txt-ac'],null,$records);

foreach ($records as $record) {
	theme::table_start_tr();
	echo '..'.str_replace(ROOTPATH,'',$record);

	theme::table_row('txt-ac');
	$checked = ($record == in_array($record,$public)) ? 'checked' : '';
	echo '<input type="checkbox" name="public_'.bin2hex($record).'" value="1" '.$checked.'>';

	theme::table_row('txt-ac');
	$checked = ($record == in_array($record,$admin)) ? 'checked' : '';
	echo '<input type="checkbox" name="admin_'.bin2hex($record).'" value="1" '.$checked.'>';
	
	theme::table_end_tr();
}
theme::table_end();
echo '</form>';

o::hr(0,12); /* 4px padding top and bottom */

echo '<div class="form-footer-buttons text-right">';
theme::footer_cancel_button($this->controller_path);
theme::footer_button('#','Submit',['class'=>'js-submit btn btn-primary']);
echo '</div>';
?>
<script>
document.addEventListener("DOMContentLoaded", function(event) {
	$('.js-submit').click(function(e) {
		e.preventDefault();

		var obj = $('#main-form').mvcForm2Obj();
		
	  var reply = mvc.request({data: {"checkboxes":obj}, url: controller_path + '_save', dataType: 'json'});

		window.history.go(-1);
	});
});
</script>