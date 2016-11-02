;(function($) {
	$(document).ready(function() {

		// Open sidebar on mobile
		$('.menu-open').click(function(){
		    $('#sidebar').removeClass('hide').addClass('open');
			return false;
		});
		// Close sidebar on mobile
		$('.menu-close').click(function(){
			$('#sidebar').removeClass('open').addClass('hide');

			setTimeout(function() {
				$('#sidebar').removeClass('hide');
			}, 500);
			return false;
		});
		// Close sidebar by hitting of ESC
		$(document).keyup(function(e) {
		    if (e.keyCode == 27) {
		        $('#sidebar').removeClass('open');
		    }
		});

		var switched = false;

		var updateTables = function() {
			if (($(window).width() < 540) && !switched ){
				switched = true;

				$("table").each(function(i, element) {
					splitTable($(element));
				});

				return true;
			}
			else if (switched && ($(window).width() > 540)) {
				switched = false;

				$("table").each(function(i, element) {
					unsplitTable($(element));
				});
			}
		};

		$(window).load(updateTables);
		$(window).on("redraw",function() {
			switched = false;
			updateTables();
		}); // An event to listen for

		$(window).on("resize", updateTables);


		function splitTable(original) {
			original.wrap("<div class='table-wrapper' />");

			var copy = original.clone();
			copy.find("td:not(:first-child), th:not(:first-child)").css("display", "none");
			copy.removeClass("responsive");

			original.closest(".table-wrapper").append(copy);
			copy.wrap("<div class='pinned' />");
			original.wrap("<div class='scrollable' />");

			setCellHeights(original, copy);
		}

		function unsplitTable(original) {
			original.closest(".table-wrapper").find(".pinned").remove();
			original.unwrap();
			original.unwrap();
		}

		function setCellHeights(original, copy) {
			var tr = original.find('tr'),
			tr_copy = copy.find('tr'),
			heights = [];

			tr.each(function (index) {
				var self = $(this),
				tx = self.find('th, td');

				tx.each(function () {
					var height = $(this).outerHeight(true);
					heights[index] = heights[index] || 0;

					if (height > heights[index]) heights[index] = height;
				});
			});

			tr_copy.each(function (index) {
				$(this).height(heights[index]);
			});
		}

		/** -----------------------------------------------
		 * TABLE OF CONTENTS
		 *
		 * Transform a #table-of-contents div to a nested list
		 */
		if($("#table-contents-holder").length > 0) {
			var toc = '<div id="table-of-contents" class="open">' +
				  '<h4>Table of contents<span class="updown">&#9660;</span></h4><ul style="display: none;">';

			// Remove existing anchor redirection in the url
			var pageURL = window.location.href.replace(/#[a-zA-Z0-9\-\_]*/g, '');

			var itemCount = 0;
			$('#content h1[id], #content h2[id], #content h3[id], #content h4[id]').each(function(i) {
				var current = $(this);
				var tagName = current.prop("tagName");
				if(typeof tagName == "String") tagName = tagName.toLowerCase();
				itemCount++;
				toc += '<li class="' + tagName + '"><a id="link' + i + '" href="'+ pageURL +'#' + $(this).attr('id') + '" title="' + current.html() + '">' + current.html() + '</a></li>';
			});

			// if no items in the table of contents, don't show anything
			if(itemCount == 0) return false;

			toc += '</ul></div>';

			$('#table-contents-holder').prepend(toc);

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
		var url = window.location.href.replace(/#[a-zA-Z0-9\-\_]*/g, '');

		$("#content h1[id], #content h2[id], #content h3[id], #content h4[id], #content h5[id], #content h6[id]").each(function() {
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

		/** ---------------------------------------------
		 * SYNTAX HIGHLIGHTER
		 *
		 * As the Markdown parser now uses the GFM structure (```yml) this does
		 * not work with SyntaxHighlighter. The below translates the GFM output
		 * to one SyntaxHighter can use
		 */
		$("pre").each(function(i, elem) {
			var code = $(elem).find('code[class^=language]');

			if(code.length > 0) {
				var brush = code.attr('class').replace('language-', '');
				$(elem).attr('class', 'prettyprint lang-' + brush);
//				$(elem).html(code.html());
			}
		});


	});
})(jQuery);
