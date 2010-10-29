function ajaxHtmlGet(div,url) {
	$(div).fadeTo('fast', 0.2);
	$.get(url, {}, function(out) {
		$(div).html(out).fadeTo('fast', 1.0);
	},'html');
}

function ajaxHtmlPost(frm,div,url) {
	$(div).fadeTo('fast', 0.2);
	$.post(url, $(frm).serialize(), function(out) {
		$(div).html(out).fadeTo('fast', 1.0);
	},'html');
}
