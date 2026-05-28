function ajaxHtmlGet(sel, url) {
	let xhttp = new XMLHttpRequest();
	let div = document.querySelector(sel);
	
	if(!div) return;

	div.style.opacity = 0.5;

	xhttp.onreadystatechange = function () {
		if (4 === this.readyState) {
			if (200 === this.status) {
				div.innerHTML = this.responseText;
				div.style.opacity = 1.0;
			}
		}
	};

	xhttp.open('GET', url);
	xhttp.send();
}

function createEvent(name, data) {
	return new CustomEvent(name, {
		detail: data
	});
}