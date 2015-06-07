<style>
body { padding-top: 8px; height: 100%; }
header { display: none	}
nav { display:  none; }
.bar { height: 5px; margin: -8px -100px }
</style>
<?php
echo '<div class="bar bg-primary"></div>';
echo '<div class="content">';
if ($subfile) {
  theme::header_start();
  theme::header_button('Return to Modules',$controller_path,'');
  theme::header_end();
  echo $help;
} else {
  theme::header_start('Module &quot;'.$module->name.'&quot;');
  theme::header_button('Return to Modules',$controller_path,'');
  theme::header_end();

  if ($help) {
    echo '<div class="well">'.$help.'</div>';
  }

?>
<table class="table">
  <tr>
    <td>Name</td>
    <td><?=$module->name ?></td>
  </tr>
  <tr>
    <td>Folder Name</td>
    <td><?=$internal ?></td>
  </tr>
  <tr>
    <td>Description</td>
    <td><?=$module->info ?></td>
  </tr>
  <tr>
    <td>Version</td>
    <td><?=$module->version ?></td>
  </tr>
  <tr>
    <td>Type</td>
    <td><?php $typer($module->type) ?></td>
  </tr>

  <tr>
    <td>Installable</td>
    <td><?=($module->install) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>

  <tr>
    <td>Upgradeable</td>
    <td><?=($module->upgrade) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>

  <tr>
    <td>Uninstallable</td>
    <td><?=($module->uninstall) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>

  <tr>
    <td>Removeable</td>
    <td><?=($module->remove) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>

  <tr>
    <td>Onload</td>
    <td><?=($module->onload) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>' ?></td>
  </tr>

  <tr>
    <td>Requires</td>
    <td>
      <? 
      foreach ($module->requires as $folder=>$record) {
      	$r[] = $folder.' <small>v'.$record[0].'-'.$record[1].'</small>';
      }
      echo implode(', ',$r);
      ?>
    </td>
  </tr>

  <tr>
    <td>Composer Requires</td>
    <td>
      <? 
      foreach ($module->requires_composer as $name=>$version) {
      	$c[] = $name.' <small>v'.$version.'</small>';
      }
      echo implode(', ',$c);
      ?>
    </td>
  </tr>

  <?php if ($module->theme) { ?>
  <tr>
    <td>Theme</td>
    <td><?=$module->theme ?></td>
  </tr>
	<?php } ?>

  <?php if ($module->table) { ?>
  <tr>
    <td>Table(s)</td>
    <td><?=$module->table ?></td>
  </tr>
	<?php } ?>

	<?php
	if (is_array($module->notes)) {
		foreach ($module->notes as $title=>$value) {
	    if (is_bool($value)) {
	      $value = ($value) ? '<span class="label label-success">TRUE</span>' : '<span class="label label-danger">FALSE</span>';
	    }
	
	    echo '<tr><td>'.$title.'</td><td>'.$value.'</td></tr>';
	  }
	}
	?>

</table>

<?php } ?>

</div>
