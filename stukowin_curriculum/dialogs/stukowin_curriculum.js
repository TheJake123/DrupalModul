/**
 * The stukowin_curriculum dialog definition.
 *
 */

// Our dialog definition.
CKEDITOR.dialog.add( 'stukowin_curriculum', function( editor ) {
	return {

		// Basic properties of the dialog window: title, minimum size.
		title: 'Insert a Taxonomy View',
		minWidth: 400,
		minHeight: 200,
                
                contents: [
                    {
                        elements: [
                            {
                                type: 'text',
                                id: 'taxonomy',
                                label: 'Enter the taxonomy name you want to insert:',
                                
                                validate: CKEDITOR.dialog.validate.notEmpty( "Abbreviation field cannot be empty" )
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
			div.setAttribute( 'id', dialog.getValueOf( 'taxonomy' ) );
			div.setAttribute( 'class', curriculum);

			editor.insertElement( div );
		}
	};
});