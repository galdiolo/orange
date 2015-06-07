<?php
theme::header_start('Role Detail',$role->name);
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
				<td title="<?=$role->id ?>"><?=$role->name ?></td>
				<td><?=$role->description ?></td>
			</tr>
		</table>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">User(s) with this Role</h3>
	</div>
	<div class="panel-body" style="padding:0">
		<table class="table" style="margin:0">
				<?php foreach ($users as $record) { ?>
				<tr>
					<td title="<?=$record->id ?>"><a href="/admin/users/user/details/<?=$record->id ?>"><?=$record->username ?></td>
				</tr>
				<?php } ?>
		</table>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Access assigned to this Role</h3>
	</div>
	<div class="panel-body" style="padding:0">
		<table class="table" style="margin:0">
				<?php foreach ($access as $record) { ?>
				<tr>
					<td><a href="/admin/users/access/details/<?=$record->id ?>"><?=$record->description ?></a></td>
					<td><?=$record->key ?></td>
				</tr>
				<?php } ?>
		</table>
	</div>
</div>

