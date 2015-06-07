// getElementById
function $id(id) {
	return document.getElementById(id);
}

// output information
function Output(msg) {
	var m = $id("messages");
	m.innerHTML = msg + m.innerHTML;
}

// call initialization file
if (window.File && window.FileList && window.FileReader) {
	Init();
}

// initialize
function Init() {

	var fileselect = $id("fileselect"),
		filedrag = $id("filedrag"),
		submitbutton = $id("submitbutton");

	// file select
	fileselect.addEventListener("change", FileSelectHandler, false);

	// is XHR2 available?
	var xhr = new XMLHttpRequest();
	if (xhr.upload) {

		// file drop
		filedrag.addEventListener("dragover", FileDragHover, false);
		filedrag.addEventListener("dragleave", FileDragHover, false);
		filedrag.addEventListener("drop", FileSelectHandler, false);
		filedrag.style.display = "block";

		// remove submit button
		submitbutton.style.display = "none";
	}

}

// file drag hover
function FileDragHover(e) {
	e.stopPropagation();
	e.preventDefault();
	e.target.className = (e.type == "dragover" ? "hover" : "");
}

// file selection
function FileSelectHandler(e) {

	// cancel event and hover styling
	FileDragHover(e);

	// fetch FileList object
	var files = e.target.files || e.dataTransfer.files;

	// process all File objects
	for (var i = 0, f; f = files[i]; i++) {
		ParseFile(f);
		UploadFile(f);
	}

}

function ParseFile(file) {
	Output(
		"<p>File information: <strong>" + file.name +
		"</strong> type: <strong>" + file.type +
		"</strong> size: <strong>" + file.size +
		"</strong> bytes</p>"
	);
}

// upload files
// upload files
function UploadFile(file) {
	var xhr = new XMLHttpRequest();

	xhr.addEventListener('error', function(e) {
		var reply = JSON.parse(xhr.responseText);
		Output('<p>Unknown Error</p>');
	}, false);

	xhr.addEventListener('load', function(e) {
		var reply = JSON.parse(xhr.responseText);
		Output('<p>'+reply.msg+'</p>');
	}, false);

	var max_size = $id("MAX_FILE_SIZE").value;

	if (file.size >= max_size) {
		Output('<p>File size to large '+(max_size / 1024 / 1024)+'mb or smaller.</p>');
	}

	if (xhr.upload && file.type == "application/zip" && file.size <= max_size) {
		xhr.open("POST", $id("upload").action, true);
		xhr.setRequestHeader("X_FILENAME", file.name);
		xhr.send(file);
	}

}