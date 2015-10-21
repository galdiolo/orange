<?php
theme::header_start($controller_titles);
theme::header_button('Back',$controller_path.'/list-all','reply');
theme::header_end();

echo '<div class="panel panel-primary"><div class="panel-heading"><h3 class="panel-title">Merged (File + Database)</h3></div><div class="panel-body" style="padding: 0">';
settingController::looper($all,'merged');
echo '</div></div>';

echo '<div class="panel panel-success"><div class="panel-heading"><h3 class="panel-title">Database Settings</h3></div><div class="panel-body" style="padding: 0">';
settingController::looper($all,'db');
echo '</div></div>';

echo '<div class="panel panel-info"><div class="panel-heading"><h3 class="panel-title">Environment ../config/'.ENVIRONMENT.'/'.$which.'.php</h3></div><div class="panel-body" style="padding: 0">';
settingController::looper($all,'env');
echo '</div></div>';

echo '<div class="panel panel-info"><div class="panel-heading"><h3 class="panel-title">Application ../config/'.$which.'.php</h3></div><div class="panel-body" style="padding: 0">';
settingController::looper($all,'file');
echo '</div></div>';

theme::return_to_top();