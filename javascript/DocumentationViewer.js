$(document).ready(function() {
	var toc = '<div class="box"><ul id="toc">';
	toc += '<h4>In this document:</h4>';
	$('#LeftColumn h1, #LeftColumn h2, #LeftColumn h3, #LeftColumn h4').each(function(i) {
		var current = $(this);
		current.attr('id', 'title' + i);
		toc += '<li class="' + current.attr("tagName").toLowerCase() + '"><a id="link' + i + '" href="#title' + i + '" title="' + current.html() + '">' + current.html() + '</a></li>';
	});
	toc += '</ul></div>';
	
	$('#RightColumn').prepend(toc);
});
