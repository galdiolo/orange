<?php
theme::header_start('Dashboard');
theme::header_end();
?>
<div class="row" id="dashboards">
</div>
<style>
a:hover {
  box-shadow: 0 0 5px rgba(99, 99, 99, 1);
  -webkit-box-shadow: 0 0 5px rgba(99, 99, 99, 1); 
  -moz-box-shadow: 0 0 5px rgba(99, 99, 99, 1); 
}
a.dashboard-block { width:100%;margin:8px 0; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; color: white}
@media only screen and (min-width : 320px) {
	a.dashboard-block  { font-size: 18px;}
}
@media only screen and (min-width : 480px) {
	a.dashboard-block  { font-size: 16px;}
}
@media only screen and (min-width : 768px) {
	a.dashboard-block  { font-size: 14px;}
}
@media only screen and (min-width : 992px) {
	a.dashboard-block  { font-size: 13px;}
}
@media only screen and (min-width : 1200px) {
	a.dashboard-block  { font-size: 13px;}
}
.btn:hover {
	color: white;
}
</style>
<script>
function add_block(that) {
	var href = that.attr('href');
	var target = that.attr('target');
	var icon = that.data('icon');
	var color = that.data('color');
	var html = that.html();

	icon = (icon) ? icon : 'link';
	color = (color) ? color : 'E36B2A';
	target = (target) ? ' target="'+target+'" ' : '';

	if (href != '#' && href != '') {
		var html_stripped = html.replace(/(<([^>]+)>)/ig,"");
		
		$('#dashboards').append('<div class="col-xs-12 col-sm-6 col-md-3 col-lg-2"><a href="'+href+'" '+target+'style="background-color: #'+color+';" class="btn dashboard-block"><i class="fa fa-'+icon+'"></i><br>'+html_stripped+'</div>');
	}
}
document.addEventListener("DOMContentLoaded", function(event) {
	$('ul.nav li a').each(function() { add_block($(this)); });
});
</script>