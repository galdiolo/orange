<?php
theme::header_start('Set "'.ci()->uri->segment(5).'"');
theme::header_button('Back',$controller_path.'/list-all','reply');
theme::header_end();

echo '<div class="panel panel-primary"><div class="panel-heading"><h3 class="panel-title">Merged (File + Database)</h3></div><div class="panel-body" style="padding: 0">';
settingController::looper($merged,true);
echo '</div></div>';

echo '<div class="panel panel-info"><div class="panel-heading"><h3 class="panel-title">Application Config File /'.$which.'.php</h3></div><div class="panel-body" style="padding: 0">';
settingController::looper($file_array);
echo '</div></div>';

echo '<div class="panel panel-info"><div class="panel-heading"><h3 class="panel-title">Environment Config File /'.ENVIRONMENT.'/'.$which.'.php</h3></div><div class="panel-body" style="padding: 0">';
settingController::looper($env_array);
echo '</div></div>';

echo '<div class="panel panel-success"><div class="panel-heading"><h3 class="panel-title">Database Settings</h3></div><div class="panel-body" style="padding: 0">';
settingController::looper($db_array);
echo '</div></div>';

theme::return_to_top();


