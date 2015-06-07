var mvc = (mvc) || {};

var pleaseWaitDiv = $('<div class="modal hide" id="pleaseWaitDialog" data-backdrop="static" data-keyboard="false"><div class="modal-header"><h3>Processing...</h3></div><div class="modal-body"><div class="progress progress-striped active"><div class="bar" style="width: 100%;"></div></div></div></div>');

/*
hide / show modals
pleaseWaitDiv.modal('show');
pleaseWaitDiv.modal('hide');
*/

/**
* On Ready
*/
$(function() {

	/* handle shift when selecting group access */
	$('input.js-shift-key').click(function(event) {
		if (event.shiftKey) {
			$('[data-group="' + $(this).data('group') + '"]').prop('checked',($(this).prop('checked') || false));
		}
	});

}); /* end onready */
