/**
 * The stukowin_curriculum dialog definition.
 *
 */

// Our dialog definition.
CKEDITOR.dialog.add( 'stukowin_curriculum_Dialog', function( editor ) {
        var items = new Array();
		jQuery.ajax({ url: 'http://drupal.dafalias.com/stukowin/crclmlst', 
			async: false,
			dataType: 'json',
			success: function(data) {
				for (var i = 0; i < data.length; i++) {
					items.push(new Array(data[i]["name"], data[i]["vid"]));
				}
			}
		});

        //alert(JSON.stringify(items));
        
	return {

		// Basic properties of the dialog window: title, minimum size.
		title: 'Insert a Taxonomy View',
		minWidth: 400,
		minHeight: 200,
                
                contents: [
                    {
                        id: 'currdialog',
                        elements: [
                            {
                                type: 'select',
                                id: 'taxonomy',
                                label: 'Select the taxonomy you want to insert:',
                                items: items,
                                onChange: function(api) {
                                    // this = CKEDITOR.ui.dialog.select
                                    alert('Current value: ' + this.getValue());
                                },
                            }
                        ]
                    }
                    
                ],
		

		// This method is invoked once a user clicks the OK button, confirming the dialog.
		onOk: function() {

			// The context of this function is the dialog object itself.
			// http://docs.ckeditor.com/#!/api/CKEDITOR.dialog
			var dialog = this;

			// Creates a new <div> element.
			var div = editor.document.createElement( 'div' );
                        
			// Set element attribute and text, by getting the defined field values.
			div.setAttribute( 'vid', dialog.getValueOf( 'currdialog', 'taxonomy' ) );
			div.setAttribute( 'class', 'curriculum');
                        alert(div.vid);
			editor.insertElement( div );
                        var script = editor.document.createElement('script');
                        script.setAttribute( 'src', 'sites/all/modules/stukowin/js/graph.js');
                        editor.insertElement( script );
		}
	};
});