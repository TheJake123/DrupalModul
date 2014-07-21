/**
 * @defgroup Drupal2AGG Drupal2AGG
 * @brief Module for transforming CEUS data to a proper graphical representation.
 * 
 * This module contains all files, classes and methods that provide 
 * the functionality for automatically generating a visual representation of the
 * imported curricula data.
 * 
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @authors Manuel Muehlburger - Hansbert92@googlemail.com
 */

/**
 * @ingroup Drupal2AGG
 * @file
 * @brief Script for nicely displaying CEUS data
 * 
 * This script is responsible for creating the proper html/css/js needed for
 * displaying the graphical representation of CEUS data.
 * 
 * @image html "Studienplan Voraussetzung.png" "The graphical representation
 *        will look approximately like this"
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @version 1.0 2014-07-09
 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
 */
var kurse = {};
var drupal_root = "";
var jsonCalls = [];

/**
 * @brief Gets a list of all courses
 * 
 * This event handler gets a list of all courses from stukowin module's JSON
 * service and sets up event handlers for different events.
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
 */
jQuery(document).ready(
				function() {
					if (document.getElementById('curriculum_display') == null)
						return;
					drupal_root = Drupal.settings.basePath;
					addResources();
					var type = jQuery("#curriculum_display").data("currtype");
					var currList = jQuery("#curriculum_display").data(
							"curriculums").split(" ");
					var reqUrl = buildRequestURL(drupal_root, type, currList);
					jQuery.getJSON(reqUrl, getCurricula);
					// Register handler for button click
					jQuery("#curriculum_display").on(
							"click",
							".button",
							function(event) {
								if (jQuery(this).hasClass("expander")) {
									expand_reduce(jQuery(this).closest(
											"div.fach, div.modul"));
								} else if (jQuery(this).hasClass("empfohlen")
										&& !(jQuery(this).hasClass("empty"))) {
									showEmpfohlen(jQuery(this).closest(
											"div.fach, div.modul, div.lv"));
									jQuery(this).toggleClass("active");
								} else if (jQuery(this).hasClass(
										"voraussetzung")
										&& !(jQuery(this).hasClass("empty"))) {
									showVoraussetzungen(jQuery(this).closest(
											"div.fach, div.modul, div.lv"));
									jQuery(this).toggleClass("active");
								}
							});
					// Register handler vor recommendation/requirement click
					jQuery("#curriculum_display")
							.on(
									"click",
									"a.bedingung",
									function(event) {
										if (document
												.getElementById(jQuery(this)
														.data("goto")) !== null) {
											if (jQuery(this).hasClass(
													"voraussetzung")) {
												expandAndScrollToElement(
														jQuery(this).data(
																"goto"),
														"#fc6554");
											} else if (jQuery(this).hasClass(
													"empfohlen")) {
												expandAndScrollToElement(
														jQuery(this).data(
																"goto"),
														"#8ad758");
											}
										} else { // Recommendation/requirement
											// is not included in
											// display
											window.location.href = drupal_root
													+ "?q=node/"
													+ jQuery(this).data("goto");
										}
										return false; // Cancel click
									});
				});

/**
 * @brief Extracts curricula from JSON
 * 
 * This event handler extracts the curricula out of the JSON data, fills the
 * select box at the top of the page and calls
 * @ref fill_crclm() for the first curriculum.
 * 
 * @param data The received JSON file containing the curricula list
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
 */
function getCurricula(data) {
	if (!data || data == null || data.length <= 0) {
		jQuery("#curriculum_display").text(
				"Leider sind zur Zeit keine Curricula-Daten verfügbar")
		return;
	}
	var select = document.createElement("select");
	select.id = "curr_select";
	for (var i = 0; i < data.length; i++) {
		var option = document.createElement("option");
		option.value = data[i]["vid"];
		option.textContent = data[i]["name"];
		select.appendChild(option);
	}
	select.onchange = function() {
		var selectElem = document.getElementById("curr_select");
		var currId = selectElem.options[selectElem.selectedIndex].value;
		selectElem.disabled = "disabled";
		var loadingIcon = document.getElementById('curriculum_display');
		loadingIcon.style.display = 'block';
		select.style.display = 'none';
		jsonCalls = [];
		// Load selected curriculum
		jQuery.getJSON(drupal_root + "?q=stukowin/crclm/" + currId, fill_crclm);
	};
	select.disabled = "disabled";
	var loadingIcon = document.createElement('img');
	loadingIcon.id = "curr_loading";
	loadingIcon.src = drupal_root
			+ "sites/all/modules/stukowin/images/ajax-loader.gif";
	var div = document.createElement('div');
	div.setAttribute('id', 'loading_div');
	jQuery("#curriculum_display").append(div);
	div.appendChild(select);
	select.style.display = 'none';
	select.parentNode.insertBefore(loadingIcon, select.nextSibling);
	// Load first curriculum
	jQuery.getJSON(drupal_root + "?q=stukowin/crclm/" + data[0]["vid"],
			fill_crclm);
}

/**
 * @brief Extracts top-level courses and fills page with content
 * 
 * This event handler extracts the top-level courses out of the JSON-data and
 * fills the page with content by calling
 * @ref createDivs().
 * 
 * @param data The received JSON file containing the curriculum tree
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @since Commit f90560aa796b39853beb42a521d6d94c86051c46 on 2014-06-28
 */
function fill_crclm(data) {
	kurse = {};
	for (var i = 0; i < data.length; i++) {
		if ("lva" in data[i]) {
			kurse[data[i]["description"]] = data[i];
		} else {
			kurse[data[i]["name"]] = data[i];
		}
	}
	clearDiv();
	for ( var key in kurse) {
		jQuery("#curriculum_display").append(createDivs(kurse[key], 0));
	}
	reduce_all();
	var selectElem = document.getElementById("curr_select");
	selectElem.removeAttribute("disabled");
	selectElem.style.display = 'block';
	document.getElementById("curr_loading").style.display = 'none';
}

/**
 * @brief Shows or hides recommended courses
 * 
 * This click handler shows or hides the list of recommended courses for a given
 * course.
 * 
 * @param element The element to show the recommended courses for
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @since Commit 3372f36c1ac4e45adeb0a5e51baa77dbd1871daf on 2014-06-29
 */
function showEmpfohlen(element) {
	if (typeof element === "undefined" || !(typeof element === "object")
			|| !(element.prop("tagName") == "DIV"))
		return;
	element.children("div.bedingung.empfohlen").remove();
	if (element.children("table.header").find("td.button.empfohlen.active").length) {
		return;
	}
	var id = element.attr("id");
	if ("empfehlung" in kurse[id]["lva"]) {
		var list = '<div class="bedingung empfohlen">';
		list += '<p class="bedingung empfohlen">Für diesen Kurs sind folgende Kurse empfohlen:</p><ul class="bedingung empfohlen">';
		jQuery
				.each(
						kurse[id]["lva"]["empfehlung"],
						function(key, val) {
							list += '<li><a href="javascript:void(0)"   class="bedingung empfohlen" data-goto="'
									+ val
									+ '">'
									+ ("lvtypshort" in kurse[val]["lva"] ? kurse[val]["lva"]["lvtypshort"]
											: kurse[val]["lva"]["typename"])
									+ " "
									+ kurse[val]["lva"]["title"]
									+ "</a></li>";
						});
		list += "</ul>";
		list += '</div>';
		element.append(list);
	}
}

/**
 * @brief Shows or hides required courses
 * 
 * This click handler shows or hides the list of required courses for a given
 * course.
 * 
 * @param element The element to show the required courses for
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @since Commit 3372f36c1ac4e45adeb0a5e51baa77dbd1871daf on 2014-06-29
 */
function showVoraussetzungen(element) {
	if (typeof element === "undefined" || !(typeof element === "object")
			|| !(element.prop("tagName") == "DIV"))
		return;
	element.children("div.bedingung.voraussetzung").remove();
	if (element.children("table.header").find("td.button.voraussetzung.active").length) {
		return;
	}
	var id = element.attr("id");
	if ("voraussetzung" in kurse[id]["lva"]) {
		var list = '<div class="bedingung voraussetzung">';
		list += '<p class="bedingung voraussetzung">Folgende Kurse müssen verpflichtend vor diesem Kurs durchgeführt werden:</p><ul class="bedingung voraussetzung">';
		jQuery
				.each(
						kurse[id]["lva"]["voraussetzung"],
						function(key, val) {
							if (val in kurse) {
								list += '<li><a href="javascript:void(0)" class="bedingung voraussetzung" data-goto="'
										+ val
										+ '">'
										+ ("lvtypshort" in kurse[val] ? kurse[val]["lva"]["lvtypshort"]
												: "")
										+ " "
										+ kurse[val]["lva"]["title"]
										+ "</a></li>";
							}
						});
		list += "</ul>";
		list += '</div>';
		element.append(list);
	}
}

/**
 * @brief Toggles the expansion/reduction of a course @<div@>
 * 
 * This procedure expands/reduces a course @<div@>, thus showing or hiding its
 * children.
 * 
 * @param element The element to expand/reduce
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @since Commit 3372f36c1ac4e45adeb0a5e51baa77dbd1871daf on 2014-06-29
 */
function expand_reduce(element) {
	if (typeof element === "undefined" || !(typeof element === "object")
			|| !(element.prop("tagName") == "DIV"))
		return;
	var button = element.children("table.header").find(
			"td.left.button.expander");
	if (button.attr("alt") == "plus") {
		button.css("background-image", 'url("' + drupal_root
				+ 'sites/all/modules/stukowin/images/Minus.png")');
		button.attr("alt", "minus");
		button.attr("title", "Einklappen");
	} else {
		button.css("background-image", 'url("' + drupal_root
				+ 'sites/all/modules/stukowin/images/Plus.png")');
		button.attr("alt", "plus");
		button.attr("title", "Ausklappen");
	}
	var children = element.children("div.fach, div.modul, div.lv")
	if (children.hasClass("hidden")) {
		children.each(function() {
			jQuery(this).slideDown("fast");
		})
	} else {
		children.each(function() {
			jQuery(this).slideUp("fast");
		});
	}
	children.toggleClass("hidden");
	element.toggleClass("reduced");
}

/**
 * @brief Expands all divs
 * 
 * This procedure expands all divs that are currently reduced.
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @since Commit 3372f36c1ac4e45adeb0a5e51baa77dbd1871daf on 2014-06-29
 */
function expand_all() {
	jQuery(".reduced").each(function() {
		expand_reduce(jQuery(this));
	});
}

/**
 * @brief Reduces all divs
 * 
 * This procedure reduces all divs that are currently expanded.
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @since Commit d831fad697214901da89de111a3c8f50e5d68727 on 2014-07-03
 */
function reduce_all() {
	jQuery("div.fach, div.modul").not(".reduced").each(function() {
		expand_reduce(jQuery(this));
	});
}

/**
 * @brief Expands the curriculum tree down to an element
 * 
 * This procedure expands the curriculum tree down to the given element, scrolls
 * to it and then highlights it in the given
 * @c highlightColor.
 * 
 * @param element The element to expand and scroll to
 * @param highlightColor The color to highlight the element in. Default is
 *            #90EE90.
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @since Commit 3372f36c1ac4e45adeb0a5e51baa77dbd1871daf on 2014-06-29
 */
function expandAndScrollToElement(element, highlightColor) {
	if (typeof element === "number" || typeof element === "string") {
		element = jQuery("div#" + element);
	}
	if (typeof element === "undefined")
		return;
	if (typeof highlightColor === "undefined") {
		highlightColor = "#90EE90";
	}
	element.parents(".reduced").each(function() {
		expand_reduce(jQuery(this));
	});
	if (!isFullyVisible(element)) {
		jQuery("html, body").animate({
			scrollTop : element.offset().top
		}, {
			duration : 'slow',
			easing : 'swing'
		});
	}
	element.stop(true, true).effect("highlight", {
		color : highlightColor
	}, 3000);
}

/**
 * @brief Creates the @<div@>s for all courses recursively
 * 
 * This function creates the entire content of the graphical display
 * recursively. For each course it creates a header table and its children.
 * 
 * @param kurs The course to create the @<div@> for
 * @param level The current recursion level (needed in order to decide if
 *            the element is top-level or not
 * @return The complete HTML for the given course's @<div@>
 * 
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @since Commit 3372f36c1ac4e45adeb0a5e51baa77dbd1871daf on 2014-06-29
 * 
 */
function createDivs(kurs, level) {
	var div, typ, rightTds;
	if ("lva" in kurs && kurs["lva"]) {
		kurse[kurs['description']] = kurs;
		rightTds = createTds(kurs);
		if (kurs["lva"]["lvatype"] == 3) {
			div = '<div class="lv' + '" id="' + kurs["description"] + '">'
					+ '<table class="header"><tr>' + '<td class="left ects">'
					+ ("lva" in kurs ? kurs["lva"]["lvtypshort"] : "")
					+ '</td>' + rightTds + '</tr></table></div>';
		} else {
			if (kurs["lva"]["lvatype"] == 1) {
				typ = "fach";
			} else if (kurs["lva"]["lvatype"] == 2) {
				typ = "modul";
			}
			var expander = "children" in kurs ? "left button expander" : "";
			div = '<div class="' + typ + '" id="' + kurs["description"] + '">'
					+ '<table class="header"><tr>' + '<td class="' + expander
					+ '" alt="minus" title="Ausklappen"></td>' + rightTds
					+ '</tr></table>';
			if ("children" in kurs) {
				jQuery.each(kurs["children"], function(key, val) {
					div += createDivs(val, level + 1);
				});
			}
			div += "</div>";
		}
	} else {
		div = '<div class="abschnitt"><h1>' + kurs["name"] + '</h1></div>';
		if ("children" in kurs) {
			jQuery.each(kurs["children"], function(key, val) {
				div += createDivs(val, level + 1, true);
			});
		}
	}

	return div;
}

/**
 * @brief Creates the header table cells
 * 
 * This utility function creates the table cells in the header used in all
 * course, module and subject @<div@>s. It also requests the details of all
 * courses referenced (as recommendation/prerequisite) but not included in the
 * loaded vocabulary.
 * @param kurs The course to create the cells for
 * @return The HTML code for the @<td@>s in the header
 * 
 * @author Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @since Commit 15c1f4d1ee7859139588438d30c6f5a638da35b5 on 2014-06-30
 * 
 */
function createTds(kurs) {
	var anzVoraussetzungen = "voraussetzung" in kurs["lva"] ? kurs["lva"]["voraussetzung"].length
			: 0;
	var anzEmpfohlen = "empfehlung" in kurs["lva"] ? kurs["lva"]["empfehlung"].length
			: 0;
	var rightTds = '<td class="center">'
			+ ("lva" in kurs ? '<a href="' + drupal_root + '?q=node/'
					+ kurs['id'] + '">' + kurs["lva"]["title"] + '</a>'
					: kurs["name"])
			+ '</td>'
			+ '</td>'
			+ '<td class="right button voraussetzung'
			+ (anzVoraussetzungen ? "" : " empty")
			+ '" title="'
			+ (anzVoraussetzungen ? anzVoraussetzungen
					+ " verpflichtende Voraussetzung"
					+ (anzVoraussetzungen > 1 ? "en" : "")
					: "Keine verpflichtenden Voraussetzungen")
			+ '"/>'
			+ '<td class="right button empfohlen'
			+ (anzEmpfohlen ? "" : " empty")
			+ '" title="'
			+ (anzEmpfohlen ? anzEmpfohlen + " empfohlene Voraussetzung"
					+ (anzEmpfohlen > 1 ? "en" : "")
					: "Keine empfohlenen Voraussetzungen") + '"/>'
			+ '<td class="right ects" title="ECTS">'
			+ ("lva" in kurs ? kurs["lva"]["ects"] : "") + '</td>';
	for (var i = 0; i < anzEmpfohlen; i++) {
		if (!(kurs["lva"]["empfehlung"][i] in kurse)
				&& jsonCalls.indexOf(kurs["lva"]["empfehlung"][i]) == -1) {
			jQuery.getJSON(drupal_root + "?q=stukowin/lva/"
					+ kurs["lva"]["empfehlung"][i], fillMissingDetail);
			jsonCalls.push(kurs["lva"]["empfehlung"][i]);
		}
	}
	for (var i = 0; i < anzVoraussetzungen; i++) {
		if (!(kurs["lva"]["voraussetzung"][i] in kurse)
				&& jsonCalls.indexOf(kurs["lva"]["voraussetzung"][i]) == -1) {
			jQuery.getJSON(drupal_root + "?q=stukowin/lva/"
					+ kurs["lva"]["voraussetzung"][i], fillMissingDetail);
			jsonCalls.push(kurs["lva"]["voraussetzung"][i]);
		}
	}
	return rightTds;
}

/**
 * @brief Checks if an element is fully visible
 * 
 * This utility function determines if an element is fully visible on the screen
 * 
 * @param elem The element to check
 * @retval true The element is fully visible
 * @retval false The element is @b not fully visible
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @since Commit 3372f36c1ac4e45adeb0a5e51baa77dbd1871daf on 2014-06-29
 */
function isFullyVisible(elem) {
	try {
		var docViewTop = jQuery(window).scrollTop();
		var docViewBottom = docViewTop + jQuery(window).height();
		var elemTop = jQuery(elem).offset().top;
		var elemBottom = elemTop + jQuery(elem).height();
		return ((elemBottom <= docViewBottom) && (elemTop >= docViewTop));
	} catch (e) {
		return false;
	}
}

/**
 * @brief Creates the JSON request URL
 * 
 * This utility function buils the URL for the JSON request based on the main @<div@>s
 * @c data tags.
 * 
 * @param baseUrl Drupal base URL to build the request on (Needed because
 *            drupal could be installed under a sub path)
 * @param type The curriculum type to get
 *            ("Bachelorstudium","Masterstudium")
 * @param curriculums The vocabulary types to get ("curriculum", "itsv"
 *            and/or "specialisation")
 * @return The complete request URL
 * 
 * @author Werner Breuer - bluescreenwerner@gmail.com
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @since Commit 58e2eac1e9e1224796c5cab04ab39bff1f2c0014 on 2014-07-02
 */
function buildRequestURL(baseUrl, type, curriculums) {
	var url = baseUrl + "?q=stukowin/crclmlst";
	if (url.indexOf("?") >= 0) {
		url += "&";
	} else {
		url += "?";
	}
	url += "currtype=";
	url += type;
	for (var i = 0; i < curriculums.length; i++) {
		url += "&taxtypes[]=";
		url += curriculums[i];
	}
	return url;
}

/**
 * @brief Empties main @<div@>
 * 
 * This utility procedure deletes all @<div@> contents except for the
 * curriculum legend.
 * 
 * @author Werner Breuer - bluescreenwerner@gmail.com
 * @authors Jakob Strasser - jakob.strasser@telenet.be
 * @since Commit 58e2eac1e9e1224796c5cab04ab39bff1f2c0014 on 2014-07-02
 */
function clearDiv() {
	jQuery("#curriculum_display > div").not('#loading_div,#curriculum_legende')
			.remove();
}

/**
 * @brief Adds missing stylesheets and the jQuery effect library
 * 
 * This utility procedure adds the
 * @ref curriculum_style.css stylesheet and the jQuery effect library to the
 *      document.
 * 
 * @author Markus Gutmayer - m.gutmayer@gmail.com
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @since Commit 9f5e8a6b688922ea0f6f4a295013edc826ef0f7e on 2014-07-05
 */
function addResources() {
	var head = jQuery('head')[0];
	var link = document.createElement('link');
	link.rel = 'stylesheet';
	link.type = 'text/css';
	link.href = drupal_root
			+ 'sites/all/modules/stukowin/css/curriculum_style.css';
	link.media = 'all';
	head.appendChild(link);
	var highlightScript = document.createElement('script');
	highlightScript.type = "text/javascript";
	highlightScript.src = drupal_root
			+ "sites/all/modules/jquery_update/replace/ui/ui/minified/jquery.ui.effect-highlight.min.js";
	head.appendChild(highlightScript);
}

/**
 * @brief Fetches course data for missing courses
 * 
 * This utility procedure and event handler is used for fetching the details of
 * all those courses that are not included in the display but referenced as
 * recommendation and/or prerequisite.
 * 
 * @param data The received JSON details array of one course
 * 
 * @author Jakob Strasser - jakob.strasser@telenet.be
 * @since Commit f157d51285e8cc638db4a8f62c7635e5ed2bb2fd on 2014-07-06
 */
function fillMissingDetail(data) {
	if (data == null || "error" in data)
		return;
	kurse[data["id"]] = {};
	kurse[data["id"]]["lva"] = data;
}
