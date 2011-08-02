(function($) {
	$(document).ready(function() {
		
		/** -----------------------------------------------
		 * TABLE OF CONTENTS
		 *
		 * Transform a #table-of-contents div to a nested list
		 */
		if($("#table-of-contents").length > 0) {
			var toc = '<div class="box"><ul id="toc"><h4>In this document:</h4>';
			
			// Remove existing anchor redirection in the url
			var pageURL = window.location.href.replace(/#[a-zA-Z0-9\-\_]*/g, '');
			
			$('#content-column h1[id], #content-column h2[id], #content-column h3[id], #content-column h4[id]').each(function(i) {
				var current = $(this);
				toc += '<li class="' + current.attr("tagName").toLowerCase() + '"><a id="link' + i + '" href="'+ pageURL +'#' + $(this).attr('id') + '" title="' + current.html() + '">' + current.html() + '</a></li>';
			});
		
			toc += '</ul></div>';
	
			$('#table-of-contents').prepend(toc);
		}
		
		/** ---------------------------------------------
		 * HEADING ANCHOR LINKS
		 *
		 * Automatically adds anchor links to headings that have IDs
		 */
		var url = window.location.href;
		
		$("#content-column h1[id], #content-column h2[id], #content-column h3[id], #content-column h4[id], #content-column h5[id], #content-column h6[id]").each(function() {
			var link = '<a class="heading-anchor-link" title="Link to this section" href="'+ url + '#' + $(this).attr('id') + '">&para;</a>';
			$(this).append(' ' + link);
		}); 
		
		$("h1[id], h2[id], h3[id], h4[id], h5[id], h6[id]").mouseenter(function() {
			$(this).addClass('hover');
		});
		
		$("h1[id], h2[id], h3[id], h4[id], h5[id], h6[id]").mouseleave(function() {
			$(this).removeClass('hover');
		});
		
		/** ---------------------------------------------
		 * LANGAUGE SELECTER
		 *
		 * Hide the change button and do it onclick
		 */
		$("#Form_LanguageForm .Actions").hide();
		
		$("#Form_LanguageForm select").change(function() {
			$("#Form_LanguageForm").submit();
		});
	});
	
	// Syntaxhighlighter defaults
	SyntaxHighlighter.defaults.toolbar = false;
})(jQuery);
