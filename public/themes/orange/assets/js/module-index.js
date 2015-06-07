/* module importer stuff */
$(function() {
	$('body').append('<div id="module-dialog" class="modal fade"><div class="modal-dialog"><div class="modal-content"><div class="modal-body"><h4 class="modal-title"><i class="fa fa-exclamation-triangle"></i> Modules</h4><br><p></p></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button><button type="button" class="btn btn-primary">Ok</button></div></div></div></div>');

	$('.js-issues').click(function(e){
		e.preventDefault();

		/* remove all orange notices */
		$.noticeRemoveAll();

		var errors = $(this).data('errors');
		var module_name = $(this).data('myname');

		$('#module-dialog button.btn-default').hide();
		$('#module-dialog p').html(errors);
		$('#module-dialog').modal('show');
	});

	$('.js-uninstallable').click(function(e){
		e.preventDefault();

		/* remove all orange notices */
		$.noticeRemoveAll();

		$('#module-dialog p').html('Are you sure you want to unstall the module "'+$(this).data('name')+'"?<br>This will only remove it from you installation but not delete the module.');
		$('#module-dialog').data('href',$(this).attr('href')).modal('show');
	});

	$('.js-remove').click(function(e){
		e.preventDefault();

		/* remove all orange notices */
		$.noticeRemoveAll();

		$('#module-dialog p').html('Are you sure you want to remove the module "'+$(this).data('name')+'"?<br>This will completely delete it from you system.');
		$('#module-dialog').data('href',$(this).attr('href')).modal('show');
	});

	$('#module-dialog .btn-primary').on('click',function() {
		/* hide the dialog */
		$('#module-dialog').modal('hide');

		var href = $('#module-dialog').data('href');

		if (href !== undefined) {
			/* redirect to the href on the link */
			window.location.replace(href);
		}

		$('#module-dialog button.btn-default').show();
	});

}); /* end onready */