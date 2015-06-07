<?php
theme::header_start('Menubar','manage menubars.');
theme::header_button('List View',$controller_path.'/list','list');
theme::header_button_new();
theme::header_end();
?>
<div class="row">
	<div class="col-md-6">
		<div class="dd">
			<?=$tree ?>
		</div>
	</div>
	<div class="col-md-6">
		<div id="menu-fixed-panel" class="panel panel-default">
			<div id="menu-record" class="panel-body subview">
			</div>
		</div>
	</div>
</div>
<script>
var o_dialog = (o_dialog) || {};

o_dialog.menubar_hander = function(data) {
	if (data.err == false) {
		$("#node_"+$(o_dialog.that,'a').data('id')).remove();
		$("#menu-record").html("");
	}
	
	return data;
}
</script>