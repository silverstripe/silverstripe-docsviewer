$(document).ready(function() {
	var toc = '<div class="box"><ul id="toc">';
	toc += '<h4 style="margin-top: 0; margin-bottom: 5px; font-weight: bold;">In this document:</h4>';
	$('h1, h2, h3, h4').each(function(i) {
		var current = $(this);
		current.attr('id', 'title' + i);
		toc += '<li class="' + current.attr("tagName").toLowerCase() + '"><a id="link' + i + '" href="#title' + i + '" title="' + current.html() + '">' + current.html() + '</a></li>';
	});
	toc += '</ul></div>';
	
	$('#RightColumn').prepend(toc);
});
