;(function($) {
	$(document).ready(function() {
		
		/** -----------------------------------------------
		 * TABLE OF CONTENTS
		 *
		 * Transform a #table-of-contents div to a nested list
		 */
		if($("#content-column").length > 0) {
			var toc = '<div id="table-of-contents" class="open">' +
				  '<h4>Table of contents<span class="updown">&#9660;</span></h4><ul style="display: none;">';

			// Remove existing anchor redirection in the url
			var pageURL = window.location.href.replace(/#[a-zA-Z0-9\-\_]*/g, '');

			var itemCount = 0;
			$('#content-column h1[id], #content-column h2[id], #content-column h3[id], #content-column h4[id]').each(function(i) {
				var current = $(this);
				var tagName = current.prop("tagName");
				if(typeof tagName == "String") tagName = tagName.toLowerCase();
				itemCount++;
				toc += '<li class="' + tagName + '"><a id="link' + i + '" href="'+ pageURL +'#' + $(this).attr('id') + '" title="' + current.html() + '">' + current.html() + '</a></li>';
			});

			// if no items in the table of contents, don't show anything
			if(itemCount == 0) return false;

			toc += '</ul></div>';

			// Table of content location
			var title = $('#content-column h1:first');
			if (title.length > 0) {
				title.after(toc);
			} else {
				var breadcrums = $('#content-column .doc-breadcrumbs');
				if (breadcrums.length > 0) {
					breadcrums.after(toc);
				} else {
					$('#content-column').prepend(toc);
				}
			}

			// Toggle the TOC
			$('#table-of-contents').attr('href', 'javascript:void()').toggle(
				function() {
					$("#table-of-contents ul").animate({'height':'show'}, 200, function(){$('#table-of-contents h4 span').html('&#9650;');})
				},
				function() {
					$("#table-of-contents ul").animate({'height':'hide'}, 200, function(){$('#table-of-contents h4 span').html('&#9660;');})
				}
			);

			// Make sure clicking a link won't toggle the TOC
			$("#table-of-contents li a").click(function (e) { e.stopPropagation(); });

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
		
		SyntaxHighlighter.defaults.toolbar = false;
		SyntaxHighlighter.all();
	});
})(jQuery);
