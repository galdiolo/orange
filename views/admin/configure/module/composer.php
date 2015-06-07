<?php
theme::form_start();
theme::header_start('Composer');
theme::header_button_back();
theme::header_end();
?>
<div class="panel panel-default">
  <div class="panel-body txt-ac">
		<h4>
			To use composer you need to have extensive knowledge of the json file format and how it pertains to composer which is avaiable here: <a href="https://getcomposer.org/doc/">https://getcomposer.org/doc/</a>.
		</h4>		
  </div>
</div>
<div class="row">
  <div class="col-md-6">
  	<br>
		<a href="<?=$controller_path ?>/composer-edit" class="btn btn-primary btn-lg btn-block">Edit composer.json</a>
  </div>
  <div class="col-md-6">
  	<br>
		<a href="<?=$controller_path ?>/composer-update" class="btn btn-primary btn-lg btn-block">Run 'composer update'</a>
  </div>
</div>