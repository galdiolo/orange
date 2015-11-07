<div>
	<strong>Id: </strong><?php o::e($record->id) ?>
</div>
<br>
<div>
	<strong>Text: </strong><?php o::e($record->text) ?>
</div>
<br>
<div>
	<strong>URL: </strong><?php o::e(rtrim($record->url,'/#')) ?>
</div>
<br>
<div>
	<strong>Access: </strong><?php o::smart_model('o_access',$record->access_id,'key') ?>
</div>
<br>
<div>
	<strong>Enabled: </strong><?php o::enum($record->active,'Inactive|Active') ?>
</div>
<br>

<div>
<?php if ($record->is_deletable) { ?>
	<?php o_dialog::confirm_a($controller_path.'/delete/'.$record->id,'Delete Record',['text'=>'Are you sure you want to delete this record?','icon'=>'trash','class'=>'btn btn-sm btn-danger','callback'=>'menubar_hander','id'=>$record->id]); ?><i class="fa fa-trash"></i> Delete</a>
<?php } ?>

	<a href="<?=$controller_path.'/new/'.$record->id.'/'.rawurlencode($record->text) ?>" data-id="<?=$record->id ?>" class="btn btn-sm btn-default"><i class="fa fa-magic"></i> Add Child Menu</a>

<?php if ($record->is_editable) { ?>
	<a href="<?=$controller_path.'/edit/'.$record->id ?>" data-id="<?=$record->id ?>" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i> Edit</a>
<?php } ?>
</div>