var kurse = {};
var drupal_root = "";
/**
 * Gets list of all courses from test JSON file and sets up event handlers
 */

jQuery(document)
		.ready(
				function() {
                    if (document.getElementById('curriculum_display')==null){
                        return;
                    }
                    drupal_root = Drupal.settings.basePath;
					addResources();
                                        addLegende();
					var type = jQuery("#curriculum_display").data("currtype");
					var currList = jQuery("#curriculum_display").data(
							"curriculums").split(" ");
					var reqUrl = buildRequest(drupal_root, type, currList);
					jQuery.ajax({
						url : reqUrl,
						dataType : 'json',
						success : function(result) {
							gc(result);
						},
						error : function(request, textStatus, errorThrown) {
							alert(textStatus + ": " + errorThrown);
							alert(request.status);
						}
					});
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
					jQuery("#curriculum_display").on(
							"click",
							"a.bedingung",
							function(event) {
								if (jQuery(this).hasClass("voraussetzung")) {
									expandAndScrollToElement(jQuery(this).data("goto"), "#fc6554");
								} else if (jQuery(this).hasClass("empfohlen")) {
									expandAndScrollToElement(jQuery(this).data("goto"), "#8ad758");
								}
								return false;
							});

				});

/**
 * Extracts the matching curricula out of the JSON-data and calls fill_crclm
 * 
 * @param {Object}
 *            data All curriculums to search
 */

function gc(data) {
	if (!data || data == null || data.length <= 0) {
		alert('No curriculum data available');
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
	;
	select.onchange = function() {
		var selectElem = document.getElementById("curr_select");
		var currId = selectElem.options[selectElem.selectedIndex].value;
		selectElem.disabled = "disabled";
		var loadingIcon = document.createElement('img');
		loadingIcon.id = "curr_loading";
		loadingIcon.src = drupal_root + "sites/all/modules/stukowin/images/ajax-loader.gif";
                select.style.display = 'none';
		selectElem.parentNode.insertBefore(loadingIcon, selectElem.nextSibling);
		jQuery.getJSON(drupal_root + "?q=stukowin/crclm/" + currId, fill_crclm);
	};
        
	select.disabled = "disabled";
	var loadingIcon = document.createElement('img');
	loadingIcon.id = "curr_loading";
	loadingIcon.src = drupal_root + "sites/all/modules/stukowin/images/ajax-loader.gif";
        var div = document.createElement('div');
        div.setAttribute('id', 'loading_div');
	jQuery("#curriculum_display").append(div);
        div.appendChild(select);
        select.style.display = 'none';
	select.parentNode.insertBefore(loadingIcon, select.nextSibling);
	jQuery.getJSON(drupal_root + "?q=stukowin/crclm/"
			+ data[0]["vid"], fill_crclm);
}

/**
 * Extracts the top-level courses out of the JSON-data and fills the page with
 * content
 * 
 * @param {Object}
 *            data The curriculum data to display
 */
function fill_crclm(data) {
	kurse = {};
	for (var i = 0; i < data.length; i++) {
            if ("lva" in data[i]){
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
	jQuery('#curr_loading').remove();
}

/**
 * Shows or hides the list of recommended courses for a given course
 * 
 * @param {Object}
 *            element The element to show the recommended courses for
 */
function showEmpfohlen(element) {
	if (typeof element === "undefined" || !(typeof element === "object")
			|| !(element.prop("tagName") == "DIV"))
		return;
	element.children("div.bedingung.empfohlen").remove();
	if (element.children("table.header").find("td.button.empfohlen.active").length) {
		return;
	}
        alert(element.attr('class') + element.attr('id'))
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
											+ " "
											: "")
									+ kurse[val]["lva"]["title"]
									+ "</a></li>";
						});
		list += "</ul>";
		list += '</div>';
		element.append(list);
	}
}

/**
 * Shows or hides the list of required courses for a given course
 * 
 * @param {Object}
 *            element The element to show the required courses for
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
	if ("voraussetzung" in kurse[id]["lva"]) { // Falsch geschrieben in
		// Testphase (Fabian der alte
		// Spanier hat ein rollendes
		// R^^)
		var list = '<div class="bedingung voraussetzung">';
		list += '<p class="bedingung voraussetzung">Folgende Kurse müssen verpflichtend vor diesem Kurs durchgeführt werden:</p><ul class="bedingung voraussetzung">';
		jQuery
				.each(
						kurse[id]["lva"]["voraussetzung"],
						function(key, val) { // Falsch
							// geschrieben
							// (s.o.)
							if (val in kurse) {
								list += '<li><a href="javascript:void(0)" class="bedingung voraussetzung" data-goto="'
										+ val
										+ '">'
										+ ("lvtypshort" in kurse[val] ? kurse[val]["lva"]["lvtypshort"]
												+ " "
												: "")
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
 * Toggles the expansion/reduction of a course <div>, thus showing or hiding its
 * children
 * 
 * @param {Object}
 *            element The element to expand/reduce
 */
function expand_reduce(element) {
	if (typeof element === "undefined" || !(typeof element === "object")
			|| !(element.prop("tagName") == "DIV"))
		return;
	var button = element.children("table.header").find(
			"td.left.button.expander");
	if (button.attr("alt") == "plus") {
		button.css("background-image",
				'url("' + drupal_root + 'sites/all/modules/stukowin/images/Minus.png")');
		button.attr("alt", "minus");
		button.attr("title", "Einklappen");
	} else {
		button.css("background-image",
				'url("' + drupal_root + 'sites/all/modules/stukowin/images/Plus.png")');
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
 * Expands all divs that are currently reduced
 */
function expand_all() {
	jQuery(".reduced").each(function() {
		expand_reduce(jQuery(this));
	});
}

/**
 * Reduces all divs that are currently expanded
 */
function reduce_all() {
	jQuery("div.fach, div.modul").not(".reduced").each(function() {
		expand_reduce(jQuery(this));
	});
}
/**
 * Expands the curriculum tree down to the given element, scrolls to it and
 * highlights it
 * 
 * @param {Object|String|Number}
 *            element The element to expand to. If an string or a number is
 *            given, it will scroll to the div with that id
 * @param {String}
 *            [highlightColor = '#90EE90'] The color to highlight the element in
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
 * Creates the <div>s for all the courses recursively.
 * 
 * @param {Object}
 *            kurs The course to create the <div> for
 * @param {Number}
 *            level The current recursion level (needed in order to decide if
 *            element is top-level or not
 * @return {String} The complete HTML for the given course's <div>
 */
function createDivs(kurs, level, parentIsRoot) {
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
			var expander = "children" in kurs ? "left button expander" : "left";
			div = '<div class="'
					+ typ
					+ '" id="'
					+ kurs["description"]
					+ '">'
					+ '<table class="header"><tr>'
					+ '<td class="' + expander + '" alt="minus" title="Ausklappen"></td>'
					+ rightTds + '</tr></table>';
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
 * Convenience function for creating the table cells used in course and module
 * divs.
 * 
 * @param {Object}
 *            kurs The course to create the cells for.
 */
function createTds(kurs) {
	var anzVoraussetzungen = "voraussetzung" in kurs["lva"] ? kurs["lva"]["voraussetzung"].length
			: 0;
	var anzEmpfohlen = "empfehlung" in kurs["lva"] ? kurs["lva"]["empfehlung"].length
			: 0;
	var rightTds = '<td class="center">'
			+ ("lva" in kurs ? '<a href="' + drupal_root + 'node/' + kurs['id'] + '">' + kurs["lva"]["title"] + '</a>' : kurs["name"])   + '</td>'
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
	return rightTds;
}

/**
 * Utility to check if an element is fully visible
 * 
 * @param {Object}
 *            elem The element to check
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

function buildRequest(baseUrl, type, curriculums) {
	var url = baseUrl  + "?q=stukowin/crclmlst";
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

function clearDiv() {
	jQuery("#curriculum_display > div").not('#loading_div,#curriculum_legende').remove();
}

/**
* Convenience function that adds missing stylesheets and jquery effect libraries.
*/
function addResources() {
	var head = jQuery('head')[0];
	var link = document.createElement('link');
	link.rel = 'stylesheet';
	link.type = 'text/css';
	link.href = drupal_root + 'sites/all/modules/stukowin/css/curriculum_style.css';
	link.media = 'all';
	head.appendChild(link);
	var highlightScript = document.createElement('script');
	highlightScript.type = "text/javascript";
	highlightScript.src = drupal_root +  "sites/all/modules/jquery_update/replace/ui/ui/minified/jquery.ui.effect-highlight.min.js";
	head.appendChild(highlightScript);
}

function  addLegende() {
    var display = document.getElementById("curriculum_display");
    var curr_div = document.createElement('div');
    curr_div.setAttribute('id', 'curriculum_legende')
    jQuery(display).append(curr_div);
    var table = document.createElement('table');
    table.id = "Legende";
    curr_div.appendChild(table);

    var row1 = table.insertRow(0);
    var cell11 = row1.insertCell(0);
    var cell12 = row1.insertCell(-1);
    var cell13 = row1.insertCell(-1);
    var cell14 = row1.insertCell(-1);

    var row2 = table.insertRow(-1);
    var cell21 = row2.insertCell(0);
    var cell22 = row2.insertCell(-1);
    var cell31 = row2.insertCell(-1);
    var cell32 = row2.insertCell(-1);
 
    var cell23 = row1.insertCell(-1);
    var cell24 = row1.insertCell(-1);
    var cell33 = row2.insertCell(-1);
    var cell34 = row2.insertCell(-1);
    
    var plusIcon = document.createElement('img');
    plusIcon.id = "plus";
    plusIcon.src = drupal_root + "sites/all/modules/stukowin/images/Plus.png";

    var minusIcon = document.createElement('img');
    minusIcon.id = "minus";
    minusIcon.src = drupal_root + "sites/all/modules/stukowin/images/Minus.png";

    var vorIcon = document.createElement('img');
    vorIcon.id = "vor";
    vorIcon.src = drupal_root + "sites/all/modules/stukowin/images/Voraussetzung.png";

    var empfIcon = document.createElement('img');
    empfIcon.id = "empf";
    empfIcon.src = drupal_root + "sites/all/modules/stukowin/images/Empfohlen.png";

    var ectsIcon = document.createElement('img');
    ectsIcon.id = "ects";
    ectsIcon.src = drupal_root + "sites/all/modules/stukowin/images/ECTS.png";

    var voIcon = document.createElement('img');
    voIcon.id = "vo";
    voIcon.src = drupal_root + "sites/all/modules/stukowin/images/V300.png";

    cell11.appendChild(plusIcon);
    cell12.innerHTML = "Opens the element";
    cell13.appendChild(empfIcon);
    cell14.innerHTML = "Shows Empfehlungen of course";

    cell21.appendChild(minusIcon);
    cell22.innerHTML = "Closes the element";
    cell23.appendChild(ectsIcon);
    cell24.innerHTML = "ECTS of course";

    cell31.appendChild(vorIcon);
    cell32.innerHTML = "Shows Voraussetzungen of course";
    cell33.appendChild(voIcon);
    cell34.innerHTML = "Type of course e.g. VO1";
}