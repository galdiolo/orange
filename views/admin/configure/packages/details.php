<?php
theme::header_start('Package &ldquo;'.$record['folder'].'&rdquo;',$record['info']);
theme::header_button_back();
theme::header_end();
?>
<table class="table">
	<tr>
		<td>Name</td>
		<td><?=$record['name'] ?></td>
	</tr>
	<tr>
		<td>Internal Name <small>(folder name)</small></td>
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
		<td>Priority</td>
		<td><?=$record['priority'] ?> - <?=($record['priority'] > 49) ? 'low' : 'high' ?></td>
	</tr>
	<tr>
		<td>Package Requires</td>
		<td>
			<?
			foreach ($record['requires'] as $folder=>$version) {
				$r[] = $folder.' <small>v'.$version.'</small>';
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
				$c[] = $name.' <small>v'.$version.'</small>';
			}
			echo implode('<br>',$c);
			?>
		</td>
	</tr>

	<tr>
		<td>Required By</td>
		<td class="text-danger">
			<strong>
			<?=implode('<br>',$record['required_error_raw']) ?>
			</strong>
		</td>
	</tr>

	<tr>
		<td>Table<?=(strpos($record['tables'],',') === false) ? '' : 's' ?></td>
		<td><?=str_replace(',','<br>',$record['tables']) ?></td>
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
		<td>Migration Status</td>
		<?php $map = [1=>'Less Than',2=>'Equal To',3=>'Greater Than'] ?>
		<td><?=$map[$record['version_check']] ?> Migration Version</td>
	</tr>

	<tr>
		<td>Has Migrations</td>
		<td><span class="badge"><?=count($record['migrations']) ?></span></td>
	</tr>

	<tr>
		<td colspan="2">
			<h4>Requirements</h4>
		</td>
	</tr>

	<tr>
		<td>Missing Required Packages</td>
		<td class="text-danger">
			<strong>
			<?=implode('<br>',$record['package_error_raw']) ?>
			</strong>
		</td>
	</tr>

	<tr>
		<td>Missing Required Composer Packages</td>
		<td class="text-danger">
			<strong>
			<?=implode('<br>',$record['composer_error_raw']) ?>
			</strong>
		</td>
	</tr>
	
	<tr>
		<td>Additional Help</td>
		<td>
			<?php if ($help) { ?>
			<a href="/admin/configure/packages/help/<?=$help_folder ?>" target="_blank">Open</a>
			<?php } else { ?>
				None Available
			<?php } ?>
		</td>
	</tr>
</table>