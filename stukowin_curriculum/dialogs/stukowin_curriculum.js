/**
 * The stukowin_curriculum dialog definition.
 * 
 */

// Our dialog definition.
CKEDITOR.dialog.add( 'stukowin_curriculum_Dialog', function( editor ) {
        
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
                        type: 'radio',
                        id: 'currtype',
                        label: 'Select the curricula type you want to display:',
                        items: [['Bachelor','Bachelorstudium'],['Master','Masterstudium']],
                        'default':'Bachelorstudium'
                    },
                    {
    					type: 'fieldset',
    					label: 'Taxonomy types to display',
    					children: [
    						{
        						type: 'vbox',
        						padding: 0,
        						children: [
        							{
		                                type: 'checkbox',
		                                id: 'normal',
		                                label: 'Normal',
		                                'default':'checked'
		                            },
		                            {
		                                type: 'checkbox',
		                                id: 'itsv',
		                                label: 'ITSV'
		                            },
		                            {
		                                type: 'checkbox',
		                                id: 'specialisation',
		                                label: 'Specialisation'
		                            }
        						]
        					}
    					]
                    }	
                ]
            }
        ],
		

		// This method is invoked once a user clicks the OK button, confirming
		// the dialog.
		onOk: function() {

			// The context of this function is the dialog object itself.
			// http://docs.ckeditor.com/#!/api/CKEDITOR.dialog
			var dialog = this;
			
			
			
            // Creates a new <div> element.
			var div = editor.document.createElement( 'div' );
                        
			// Set element attribute and text, by getting the defined field
			// values.
			var divClass = dialog.getValueOf( 'currdialog', 'currtype' );
			var taxonomyTypeSet = false;
			if (dialog.getValueOf( 'currdialog', 'normal' )) {
				divClass += ' curriculum';
				taxonomyTypeSet = true;
			}
			if (dialog.getValueOf( 'currdialog', 'itsv' )) {
				divClass += ' itsv';
				taxonomyTypeSet = true;
			}
			if (dialog.getValueOf( 'currdialog', 'specialisation' )) {
				divClass += ' specialisation';
				taxonomyTypeSet = true;
			}
			if (!taxonomyTypeSet) {
				alert ('Error: Please select at least one taxonomy type to display');
				return;
			}
			div.setAttribute( 'class', divClass);
			editor.insertElement( div );
			
			// Creates the <script> element.
			var script = editor.document.createElement('script');
            script.setAttribute( 'src', 'sites/all/modules/stukowin/js/graph.js');
            editor.insertElement( script );
                        
		}
	};
});
