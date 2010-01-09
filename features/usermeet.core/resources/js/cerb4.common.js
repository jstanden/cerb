function ajaxHtmlJsGet(div,url) {
	$.get(url, {}, function(xml) {
		$(div).html($(xml).find("markup").text());
		eval($(xml).find("js").text());
	},'xml');
}

function ajaxHtmlGet(div,url) {
	$.get(url, {}, function(out) {
		$(div).html(out);
	},'html');
}
