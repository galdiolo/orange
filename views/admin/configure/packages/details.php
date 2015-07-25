<?php
theme::header_start('Package &ldquo;'.$record['folder'].'&rdquo;',$record['info']);
theme::header_button_back();
theme::header_end();
?>
<table class="table">
	<tr>
		<td>Internal Name (folder name)</td>
		<td><?=$record['folder'] ?></td>
	</tr>
	<tr>
		<td>Active</td>
		<td><?=($record['is_active']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
	</tr>
	<tr>
		<td>Description</td>
		<td><?=$record['info'] ?></td>
	</tr>
	<tr>
		<td>Version</td>
		<td><?=$record['version'] ?></td>
	</tr>
	<tr>
		<td>Type</td>
		<td><span class="label label-<?=$type_map[$record['type']]?>"><?=$record['type'] ?></span></td>
	</tr>
	<tr>
		<td>Uninstallable</td>
		<td><?=($record['uninstall']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
	</tr>
	<tr>
		<td>Onload</td>
		<td><?=($record['onload']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
	</tr>
	<tr>
		<td>Requires</td>
		<td>
			<?
			foreach ($record['requires'] as $folder=>$version) {
				$r[] = $folder.' <small>v '.$version.'</small>';
			}
			echo implode('<br>',$r);
			?>
		</td>
	</tr>

	<tr>
		<td>Composer Requires</td>
		<td>
			<?
			foreach ($record['requires-composer'] as $name=>$version) {
				$c[] = $name.' <small>v '.$version.'</small>';
			}
			echo implode('<br>',$c);
			?>
		</td>
	</tr>

	<tr>
		<td>Table<?=(strpos($record['tables'],',') === false) ? '' : 's' ?></td>
		<td><?=$record['tables'] ?></td>
	</tr>

	<tr>
		<td>Notes</td>
		<td><?=$record['notes'] ?></td>
	</tr>

	<tr>
		<td>Last Migrated Version</td>
		<td><?=($record['migration_version'] == '') ? 'Not Installed' : $record['migration_version'] ?></td>
	</tr>

	<tr>
		<td>Migration</td>
		<?php $map = [1=>'Less Than',2=>'Equal To',3=>'Greater Than'] ?>
		<td><?=$map[$record['version_check']] ?> Migration Version</td>
	</tr>

	<tr>
		<td>Has Migrations</td>
		<td><span class="badge"><?=count($record['migrations']) ?></span></td>
	</tr>
</table>
