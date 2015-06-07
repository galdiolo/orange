<?php
theme::form_start();
theme::header_start('Running "composer update"');
theme::header_button_back();
theme::header_end();

o::hr(0,12); /* 4px padding top and bottom */
?>
<pre id="output" style="height: 75vh; overflow-y: auto;"><i class="fa fa-spinner fa-pulse"></i> Running</pre>
<script>
document.addEventListener("DOMContentLoaded", function(event) {
	$.ajax({url: controller_path+'_ajax' }).done(function(data) {
		$('#output').html(data);
	});
});
</script>