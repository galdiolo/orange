$('.js-validate-json').on('keyup focus click',function(e){
	var text = $('.js-validate-json').val();
	if (text.substr(0,1) == '{') {
		if (!IsJsonString(text)) {
			$(this).css('border-color','#FF0000');
		} else {
			$(this).css('border-color','');
		}
	}
});

$('.js-add-link').on('click',function(e){
	e.preventDefault();
	
	var that = this;
	
	$.ajax({
	  url: $(this).attr('href'),
	  dataType: 'json',
	  success: function(data) {
	  	if (data.err === true) {
	  		$.noticeAdd({"text":"Error Adding Setting","stay":true,"type":"danger"});
	  	} else {
	  		$.noticeAdd({"text":"Setting Added","stay":false,"type":"info"});
	  		
	  		/* remove the + button */
	  		$(that).remove();
	  	}
	  },
	  error: function() {
  		$.noticeAdd({"text":"Error Adding Setting","stay":true,"type":"danger"});
	  }
	});	

});

function IsJsonString(str) {
	try {
		JSON.parse(str);
	} catch (e) {
		return false;
	}
	
	return true;
}