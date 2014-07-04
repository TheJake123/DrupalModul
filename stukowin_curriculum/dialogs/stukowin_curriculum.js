/**
 * The stukowin_curriculum dialog definition.
 * 
 */

// Our dialog definition.
CKEDITOR.dialog.add( 'stukowin_curriculum_Dialog', function( editor ) {
        
	return {

		// Basic properties of the dialog window: title, minimum size.
		title: 'Insert a Curriculum View',
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
			var currType = dialog.getValueOf( 'currdialog', 'currtype' );
			var taxonomyTypeSet = false;
			var curriculums = "";
			if (dialog.getValueOf( 'currdialog', 'normal' )) {
				curriculums += 'curriculum ';
				taxonomyTypeSet = true;
			}
			if (dialog.getValueOf( 'currdialog', 'itsv' )) {
				curriculums += 'itsv ';
				taxonomyTypeSet = true;
			}
			if (dialog.getValueOf( 'currdialog', 'specialisation' )) {
				curriculums += 'specialisation';
				taxonomyTypeSet = true;
			}
			if (!taxonomyTypeSet) {
				alert ('Error: Please select at least one taxonomy type to display');
				return;
			}
			div.setAttribute('data-currtype', currType);
			div.setAttribute('data-curriculums', curriculums);
			div.setAttribute('id', "curriculum_display");
			editor.insertElement( div );                        
		}
	};
});
