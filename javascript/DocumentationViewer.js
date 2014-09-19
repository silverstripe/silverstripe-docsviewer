;(function($) {
	$(document).ready(function() {
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
		var url = window.location.href;
		
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

		$(".search input").live("keyup", function(e) {
			clearTimeout($.data(this, 'timer'));

			var string = $(this).val();
			var self = $(this);

			if (string == '') {
				$(".search .autocomplete-results").hide();
			} else {
				var container;

				if($(this).siblings('.autocomplete-results').length == 0) {
					container = $("<div class='autocomplete-results'></div");
					
					$(this).after(container);
				} else {
					container = $(this).siblings('.autocomplete-results').first();
				}

				$(this).data('timer', setTimeout(function() {
					if(string !== '') {
						$.getJSON(
							self.parents('form').attr('action'),
							{ query: string },
							function(results) {
								if(results) {
									var list = $("<ul></ul>");

									$.each(results, function(i, elem) {
										list.append(
											$("<li></li>")
												.append(
													$("<a></a>").attr('href', elem.link).text(elem.title)
												).append(
													elem.path
												)
										);
									});

									container.append(list);
								} else {
									container.hide().removeClass('loading');
								}
							}
						);
					}

					return false;
				}, 100));
			};
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
