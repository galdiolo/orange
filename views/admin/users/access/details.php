<?php
theme::header_start('Access Detail',$access->name);
theme::header_button_back();
theme::header_end();
?>
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Role Details</h3>
	</div>
	<div class="panel-body" style="padding:0">
		<table class="table" style="margin-bottom:0">
			<tr>
				<td>Group</td>
				<td><?=$access->group ?></td>
			</tr>
			<tr>
				<td>Name</td>
				<td><?=$access->name ?></td>
			</tr>
			<tr>
				<td>Description</td>
				<td><?=$access->description ?></td>
			</tr>
			<tr>
				<td>Key</td>
				<td><?=$access->group ?>::<?=$access->name ?></td>
			</tr>
		</table>
	</div>
</div>


<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Roles with this Access</h3>
	</div>
	<div class="panel-body" style="padding:0">
		<table class="table" style="margin:0">
				<tr>
					<td title="1"><a href="/admin/users/role/details/1"><?php o::smart_model('o_role',1,'name') ?></a></td>
				</tr>

				<?php foreach ($roles as $key=>$record) { ?>
				<tr>
					<td title="<?=$record->id ?>"><a href="/admin/users/role/details/<?=$record->id ?>"><?=$record->name ?></a></td>
				</tr>
				<?php } ?>
		</table>
	</div>
</div>