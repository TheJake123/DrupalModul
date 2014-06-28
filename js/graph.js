
var aMusthaves = [];
var aMayhaves = [];

/**
 * Get all Lists from DB
 */
jQuery(document).ready(function(){
jQuery.getJSON("/stukowin/crclm",fill_crclm);
//  $.getJSON("json_service.php?mthd=must",fill_must);
//  $.getJSON("json_service.php?mthd=may",fill_may);
});

/**
 * Callback from JSON call: Must-Dependent LVAs
 * 
 * @param {array} data Object from JSON 
 */
function fill_must(data)
{
  aMusthaves = data;
}

/**
 * Callback from JSON call: May-Dependent LVAs
 * 
 * @param {array} data Object from JSON 
 */
function fill_may(data)
{
  aMayhaves = data;
}

/**
 * Callback from JSON call: All LVA items (multidimensional)
 * 
 * @param {array} data Object from JSON
 */
function fill_crclm(data)
{
  jQuery("#main").append(make_divs(data,1));
  jQuery(".depth3").bind("mouseenter",mark_required);
  jQuery(".depth3").bind("mouseleave",release_required);
}

function mark_elems(aElems, thiselem, sClass)
{
  if(aElems[$(thiselem).attr('id')])
  {
    jQuery.each(aElems[$(thiselem).attr('id')], function(key, val){
      jQuery("#"+val).addClass(sClass);
    });
  } 
}

function release_elems(aElems, thiselem, sClass)
{
  if(aElems[$(thiselem).attr('id')])
  {
    jQuery.each(aElems[$(thiselem).attr('id')], function(key, val){
      jQuery("#"+val).removeClass(sClass);
    });
  }
}

function mark_required()
{
  mark_elems(aMusthaves, this, 'required');
  mark_elems(aMayhaves, this, 'desired');
}

function release_required()
{
  release_elems(aMusthaves, this, 'required');
  release_elems(aMayhaves, this, 'desired');
}


function make_divs(data, depth)
{
  var aItems = [];
  var sDiv;
  jQuery.each(data, function(key, val) {
    sDiv = '<div id="'+val["id"]+'" class="depth'+depth+'">'+val["name"];
    if(val['ects'] && val['ects'] > 0) sDiv += '<div class="ects">'+val['ects']+'</div>'
    if(val['children']) sDiv += make_divs(val['children'], depth+1);
    sDiv += '</div>';
    aItems.push(sDiv);
  });
  return aItems.join("");
}