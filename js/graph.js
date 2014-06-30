var kurse = {}, toplevel = [];

/**
 * Gets list of all courses from test JSON file and sets up event handlers
**/
$(document).ready(function () {
	$.ajax({
		url: "http://drupal.dafalias.com/stukowin/crclmlst",
		dataType: 'json',
		success: function (result) {
			gc(result);
		},
		error: function (request, textStatus, errorThrown) {
			alert(textStatus + ": " + errorThrown);
			alert(request.status);
		},
	});
	//$.getJSON("http://drupal.dafalias.com/stukowin/crclmlst", gc);
	$(".main").on("click", ".button", function (event) {
		if ($(this).hasClass("expander")) {
			expand_reduce($(this).closest("div.fach, div.modul"));
		} else if ($(this).hasClass("empfohlen") && !($(this).hasClass("empty"))) {
			showEmpfohlen($(this).closest("div.fach, div.modul, div.lv"));
			$(this).toggleClass("active");
		} else if ($(this).hasClass("voraussetzung") && !($(this).hasClass("empty"))) {
			showVoraussetzungen($(this).closest("div.fach, div.modul, div.lv"));
			$(this).toggleClass("active");
		}
	});
	$(".main").on("click", "a.bedingung", function (event) {
		if ($(this).hasClass("voraussetzung")) {
			expandAndScrollToElement($(this).attr("id"), "#fc6554");
		} else if ($(this).hasClass("empfohlen")) {
			expandAndScrollToElement($(this).attr("id"), "#8ad758");
		}
		return false;
	});

});

/**
 * Extracts the matching curriculum out of the JSON-data and calls fill_crclm
 * @param {O
 * bject} data All curriculums to search
**/

function gc(data) {
	for (var i = 0; i < data.length; i++) {
		if ($(".main").attr("id") == data[i]["type"]) {
			$.getJSON("http://drupal.dafalias.com/stukowin/crclm/" + data[i]["vid"], fill_crclm);
		}
	};
}

/**
 * Extracts the top-level courses out of the JSON-data and fills the page with content
 * @param {Object} data The curriculum data to display
**/
function fill_crclm(data) {
	for (var i = 0; i < data.length; i++) {
		kurse[data[i]["tid"]] = data[i];
	};
	for (var key in kurse) {
		$(".main").append(createDivs(kurse[key], 0));
	};
	expand_all();
}

/**
 * Shows or hides the list of recommended courses for a given course
 * @param {Object} element The element to show the recommended courses for
**/
function showEmpfohlen(element) {
	if (typeof element === "undefined" || !(typeof element === "object") || !(element.prop("tagName") == "DIV")) return;
	element.children("div.bedingung.empfohlen").remove();
	if (element.children("table.header").find("td.button.empfohlen.active").length) {
		return;
	}
	var id = element.attr("id");
	if ("empfohlen" in kurse[id]["lva"]) {
		var list = '<div class="bedingung empfohlen">';
		list += '<p class="bedingung empfohlen">Für diesen Kurs sind folgende Kurse empfohlen:</p><ul class="bedingung empfohlen">';
		$.each(kurse[id]["lva"]["empfohlen"], function (key, val) {
			list += '<li><a href="javascript:void(0)"   class="bedingung empfohlen" id="' + val + '">' + ("lvtypshort" in kurse[val]["lva"] ? kurse[val]["lva"]["lvtypshort"] + " " : "") + kurse[val]["lva"]["title"] + "</a></li>";
		});
		list += "</ul>";
		list += '</div>';
		element.append(list);
	}
}

/**
 * Shows or hides the list of required courses for a given course
 * @param {Object} element The element to show the required courses for
**/
function showVoraussetzungen(element) {
	if (typeof element === "undefined" || !(typeof element === "object") || !(element.prop("tagName") == "DIV")) return;
	element.children("div.bedingung.voraussetzung").remove();
	if (element.children("table.header").find("td.button.voraussetzung.active").length) {
		return;
	}
	var id = element.attr("id");
	if ("vorraussetzung" in kurse[id]["lva"]) { // Falsch geschrieben in Testphase (Fabian der alte Spanier hat ein rollendes R^^)
		var list = '<div class="bedingung voraussetzung">';
		list += '<p class="bedingung voraussetzung">Folgende Kurse müssen verpflichtend vor diesem Kurs durchgeführt werden:</p><ul class="bedingung voraussetzung">';
		$.each(kurse[id]["lva"]["vorraussetzung"], function (key, val) { // Falsch geschrieben (s.o.)
			if (val in kurse) {
				list += '<li><a href="javascript:void(0)" class="bedingung voraussetzung" id="' + val + '">' + ("lvtypshort" in kurse[val] ? kurse[val]["lva"]["lvtypshort"] + " " : "") + kurse[val]["lva"]["title"] + "</a></li>";
			}
		});
		list += "</ul>";
		list += '</div>';
		element.append(list);
	}
}

/**
 * Toggles the expansion/reduction of a course <div>, thus showing or hiding its children
 * @param {Object} element The element to expand/reduce
**/
function expand_reduce(element) {
	if (typeof element === "undefined" || !(typeof element === "object") || !(element.prop("tagName") == "DIV")) return;
	var button = element.children("table.header").find("td.left.button.expander");
	if (button.attr("alt") == "plus") {
		button.css("background-image", 'url("/Drupal/Curriculum/Icons/Minus.png")');
		button.attr("alt", "minus");
		button.attr("title", "Einklappen");
	} else {
		button.css("background-image", 'url("/Drupal/Curriculum/Icons/Plus.png")');
		button.attr("alt", "plus");
		button.attr("title", "Ausklappen");
	}
	var children = element.children("div.fach, div.modul, div.lv")
	if (children.hasClass("hidden")) {
		children.each(function () {
			$(this).slideDown("fast");
		})
	} else {
		children.each(function () {
			$(this).slideUp("fast");
		});
	}
	children.toggleClass("hidden");
	element.toggleClass("reduced");
}

/**
 * Expands all divs that are currently reduced
**/
function expand_all() {
	$(".reduced").each(function () {
		expand_reduce($(this));
	});
}

/**
 * Expands the curriculum tree down to the given element, scrolls to it and highlights it
 * @param {Object|String|Number} element The element to expand to. If an string or a number is given, it will scroll to the div with that id
 * @param {String} [highlightColor = '#90EE90'] The color to highlight the element in
**/
function expandAndScrollToElement(element, highlightColor) {
	if (typeof element === "number" || typeof element === "string") {
		element = $("div#" + element);
	}
	if (typeof element === "undefined") return;
	if (typeof highlightColor === "undefined") {
		highlightColor = "#90EE90";
	}
	element.parents(".reduced").each(function () {
		expand_reduce($(this));
	});
	if (!isFullyVisible(element)) {
		$("html, body").animate({ scrollTop: element.offset().top }, { duration: 'slow', easing: 'swing' });
	}
	element.stop(true, true).effect("highlight", { color: highlightColor }, 3000);
}

/**
 * Creates the <div>s for all the courses recursively.
 * @param {Object} kurs The course to create the <div> for
 * @param {Number} level The current recursion level (needed in order to decide if element is top-level or not
 * @return {String} The complete HTML for the given course's <div>
**/
function createDivs(kurs, level, parentIsRoot) {
	var div, hasVoraussetzungen, hasEmpfohlen, typ, rightTds;
	
	if("lva" in kurs){
		rightTds = createTds(kurs);
		if (!("children" in kurs)) {
			var clazz = parentIsRoot && parentIsRoot === true ? "" : " hidden"
			div = '<div class="lv'+ clazz + '" id="' + kurs["tid"] + '">'
				+ '<table class="header"><tr>'
				+ '<td class="left ects"><p>' + ("lva" in kurs ? kurs["lva"]["lvtypshort"] : "") + '</p></td>'
				+ rightTds
				+ '</tr></table></div>';
		} else {
			if (kurs["lva"] && kurs["lva"]["lvatype"] == 1) {
				typ = "fach";
			} else if(!kurs["lva"]) {
				typ = "fach";
			} else {
				typ = "modul hidden";
			}
			div = '<div class="' + typ + ' reduced" id="' + kurs["tid"] + '">'
				+ '<table class="header"><tr>'
				+ '<td class="left button expander" alt="plus" title="Ausklappen"></td>'
				+ rightTds
				+ '</tr></table>';
			if ("children" in kurs) {
				$.each(kurs["children"], function (key, val) {
					div += createDivs(val, level + 1);
				});
			}
			div += "</div>";
		}
	}else{
		div = '<div class="abschnitt"><h1>' + kurs["name"] + '</h1></div>';
		if ("children" in kurs) {
			$.each(kurs["children"], function (key, val) {
				div += createDivs(val, level + 1, true);
			});
		}
	}

	return div;
}

/**
 * Convenience function for creating the table cells used in course and module divs.
 * @param {Object} kurs The course to create the cells for.
 **/
function createTds(kurs) {
	var anzVoraussetzungen = "voraussetzungen" in kurs ? kurs["lva"]["voraussetzungen"].length : 0;
	var anzEmpfohlen = "empfohlen" in kurs ? kurs["lva"]["empfohlen"].length : 0;
	var rightTds = '<td class="center"><p>' + ("lva" in kurs ? kurs["lva"]["title"] : kurs["name"]) + '</p></td>'
			+ '<td class="right button voraussetzung' + (anzVoraussetzungen ? "" : " empty") + '" title="' + (anzVoraussetzungen ? anzVoraussetzungen + " verpflichtende Voraussetzung" + (anzVoraussetzungen > 1 ? "en" : "") : "Keine verpflichtenden Voraussetzungen") + '"/>'
			+ '<td class="right button empfohlen' + (anzEmpfohlen ? "" : " empty") + '" title="' + (anzEmpfohlen ? anzEmpfohlen + " empfohlene Voraussetzung" + (anzEmpfohlen > 1 ? "en" : "") : "Keine empfohlenen Voraussetzungen") + '"/>'
			+ '<td class="right ects" title="ECTS"><p>' + ("lva" in kurs ? kurs["lva"]["ects"] : "")+ '</p></td>';
	return rightTds;
}

/**
 * Utility to check if an element is fully visible
 * @param {Object} elem The element to check
**/
function isFullyVisible(elem) {
	try {
		var docViewTop = $(window).scrollTop();
		var docViewBottom = docViewTop + $(window).height();
		var elemTop = $(elem).offset().top;
		var elemBottom = elemTop + $(elem).height();
		return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
	} catch (e) {
		return false;
	}
}