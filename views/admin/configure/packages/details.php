<?php
theme::header_start('Package &ldquo;'.$record['folder_name'].'&rdquo;');
theme::header_button_back();
theme::header_end();
?>
<table class="table">
	<tr>
		<td>Name</td>
		<td><?=$record['folder_name'] ?></td>
	</tr>

	<tr>
		<td>Authors<?=(count($record['authors']) > 1) ? 's' : '' ?></td>
		<td>
		<?php
		foreach ($record['authors'] as $a) {
			foreach ($a as $key=>$val) {
				echo '<strong>'.$key.':</strong> '.$val.' ';
			}
			echo '<br>';
		}
		?>
		</td>
	</tr>

	<tr>
		<td>License</small></td>
		<td><?=$record['license'] ?></td>
	</tr>

	<tr>
		<td>Location</small></td>
		<td><?=$record['full_path'] ?></td>
	</tr>

	<tr>
		<td>Active</td>
		<td><?=($record['is_active']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
	</tr>

	<tr>
		<td>Description</td>
		<td><?=$record['description'] ?></td>
	</tr>

	<tr>
		<td>Type</td>
		<td><span class="label label-<?=$type_map[$record['type']]?>"><?=$record['type'] ?></span></td>
	</tr>

	<tr>
		<td>Managed by</td>
		<?php if ($record['type_of_package'] == 'composer') { ?>
			<td><span class="label label-primary"><?=$record['type_of_package'] ?></span></td>
		<?php } else { ?>
			<td><span class="label label-info"><?=$record['type_of_package'] ?></span></td>
		<?php } ?>
	</tr>

	<tr>
		<td>Package Priority</td>
		<td><?=$record['composer_priority'] ?> - <?=$record['composer_human_priority'] ?> <small> / Common priorities: themes 10, default 50, libraries 70, plugins 80, Orange 90+</small></td>
	</tr>

	<tr>
		<td>Version</td>
		<td><?=$record['composer_version'] ?></td>
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
		<td>Package Requires</td>
		<td>
			<?
			foreach ($record['require'] as $folder=>$version) {
				$r[] = $folder.' <small>'.$version.'</small>';
			}
			echo implode('<br>',$r);
			?>
		</td>
	</tr>

	<tr>
		<td>Required By</td>
		<td class="text-danger">
			<strong>
			<?=implode('<br>',$record['required_errors']) ?>
			</strong>
		</td>
	</tr>

	<?php if (count($record['orange']['tables'])) { ?>
	<tr>
		<td>Table<?=(strpos($record['orange']['tables'],',') === false) ? '' : 's' ?></td>
		<td><?=str_replace(',','<br>',$record['orange']['tables']) ?></td>
	</tr>
	<?php } ?>

	<?php if (count($record['orange']['notes'])) { ?>
	<tr>
		<td>Notes</td>
		<td><?=$record['orange']['notes'] ?></td>
	</tr>
	<?php } ?>

	<?php if (count($record['orange']['cli'])) { ?>
	<tr>
		<td>Command Line</td>
		<td>
			<?php
			if ($record['orange']['cli']) {
				foreach ($record['orange']['cli'] as $a=>$b) {
					echo '<code>'.$a.'</code> '.$b.'<br>';
				}
			}
			?>
		</td>
	</tr>
	<?php } ?>

</table>