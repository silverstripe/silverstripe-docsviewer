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
				current.attr('id', 'title' + i);
				toc += '<li class="' + current.attr("tagName").toLowerCase() + '"><a id="link' + i + '" href="'+ window.location.href +'#title' + i + '" title="' + current.html() + '">' + current.html() + '</a></li>';
			});
		
			toc += '</ul></div>';
	
			$('#table-of-contents').prepend(toc);
		}
		
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
