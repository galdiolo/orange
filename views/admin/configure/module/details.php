<?php
theme::header_start($module['name']);
theme::header_button_back();
theme::header_end();
?>
<table class="table">
  <tr>
    <td>Folder Name</td>
    <td><?=substr($module['filename'],8) ?></td>
  </tr>
  <tr>
    <td>Description</td>
    <td><?=$module['info'] ?></td>
  </tr>
  <tr>
    <td>Version</td>
    <td><?=$module['version'] ?></td>
  </tr>
  <tr>
    <td>Type</td>
    <td><?php $typer($module['type']) ?></td>
  </tr>
  <tr>
    <td>Installable</td>
    <td><?=($module['install']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>
  <tr>
    <td>Upgradeable</td>
    <td><?=($module['upgrade']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>
  <tr>
    <td>Uninstallable</td>
    <td><?=($module['uninstall']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>
  <tr>
    <td>Removeable</td>
    <td><?=($module['remove']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>
  <tr>
    <td>Onload</td>
    <td><?=($module['onload']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>
  <tr>
    <td>Autoload</td>
    <td><?=($module['autoload']) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>
  <tr>
    <td>Requires</td>
    <td>
      <? 
      foreach ($module['requires'] as $folder=>$record) {
      	$r[] = $folder.' <small>v '.$record[0].'-'.$record[1].'</small>';
      }
      echo implode('<br>',$r);
      ?>
    </td>
  </tr>

  <tr>
    <td>Composer Requires</td>
    <td>
      <? 
      foreach ($module['requires_composer'] as $name=>$version) {
      	$c[] = $name.' <small>v '.$version.'</small>';
      }
      echo implode('<br>',$c);
      ?>
    </td>
  </tr>

  <tr>
    <td>Theme</td>
    <td><?=$module['theme'] ?></td>
  </tr>

  <tr>
    <td>Table<?=(strpos($module['table'],',') === false) ? '' : 's' ?></td>
    <td><?=$module['table'] ?></td>
  </tr>

	<?php
	if (is_array($module['notes'])) {
		foreach ($module['notes'] as $title=>$value) {
	    if (is_bool($value)) {
	      $value = ($value) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>';
	    }
	
	    echo '<tr><td>'.$title.'</td><td>'.$value.'</td></tr>';
	  }
	}
	?>
</table>