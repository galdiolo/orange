<?php
theme::header_start('User',$user->username);
theme::header_button_back();
theme::header_end();
?>
<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">User Details</h3>
	</div>
	<div class="panel-body" style="padding:0">
		<table class="table" style="margin:0">
			<tr>
				<td title="<?=$user->id ?>">Username</td><td><?=$user->username ?></td>
			</tr>
			<tr>
				<td>Email</td><td><?=$user->email ?></td>
			</tr>
			<tr>
				<td>Active</td><td><?=view_bol($user->is_active) ?></td>
			</tr>
			<tr>
				<td title="<?=$user->role_id ?>">Role</td>
				<td><?=$user->role_name.'&nbsp;&nbsp;/&nbsp;&nbsp;'.$user->role_description ?></td>
			</tr>
			<tr>
				<td>Last IP</td>
				<td><?=$user->last_ip ?></td>
			</tr>
			<tr>
				<td>Last Login</td>
				<td><?=$user->last_login ?></td>
			</tr>
		</table>
	</div>
</div>

<div class="panel panel-default">
	<div class="panel-heading">
		<h3 class="panel-title">Access</h3>
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

<?php
function view_bol($inp) {
	return ($inp) ? '<span style="color: #90a959">TRUE</span>' : '<span style="color: #ac4142">FALSE</span>';
}
?>
<?php $user = ci()->user ?>
<!--
User Id           <?=$user->id ?>

User Name         <?=$user->username ?>

Email             <?=$user->email ?>

Active            <?=($user->is_active) ? 'true' : 'false' ?>

Role ID           <?=$user->role_id ?>

Role Name         <?=$user->role_name ?>

Role Description  <?=$user->role_description ?>

Last Login        <?=$user->last_login ?>

Last IP           <?=$user->last_ip ?>

-Access--------------------------------------
<?php foreach ($user->access as $idx=>$key) { ?>
<?=$name ?>

<?php } ?>
-->
