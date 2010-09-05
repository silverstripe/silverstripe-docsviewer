(function($) {
	$(document).ready(function() {
		
		
		/** -----------------------------------------------
		 * TABLE OF CONTENTS
		 *
		 * Transform a #table-of-contents div to a nested list
		 */
		if($("#table-of-contents").length > 0) {
			var toc = '<div class="box"><ul id="toc"><h4>In this document:</h4>';

			$('#left-column h1, #left-column h2, #left-column h3, #left-column h4').each(function(i) {
				var current = $(this);
				toc += '<li class="' + current.attr("tagName").toLowerCase() + '"><a id="link' + i + '" href="'+ window.location.href +'#title' + i + '" title="' + current.html() + '">' + current.html() + '</a></li>';
			});
		
			toc += '</ul></div>';
	
			$('#table-of-contents').prepend(toc);
		}
		
		/** ---------------------------------------------
		 * HEADING ANCHOR LINKS
		 *
		 * Automatically adds anchor links to headings that have IDs
		 */
		$("h1[id], h2[id], h3[id], h4[id], h5[id], h6[id]").each(function() {
			var link = '<a class="heading-anchor-link" title="Link to this section" href="#' + $(this).attr('id') + '">&para;</a>';
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
})(jQuery);
