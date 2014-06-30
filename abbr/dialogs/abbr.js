/**
 * The abbr dialog definition.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

// Our dialog definition.
CKEDITOR.dialog.add( 'abbrDialog', function( editor ) {
	return {

		// Basic properties of the dialog window: title, minimum size.
		title: 'Abbreviation Properties',
		minWidth: 400,
		minHeight: 200,

		// Dialog window contents definition.
		contents: [
			{
				// Definition of the Basic Settings dialog tab (page).
				id: 'tab-basic',
				label: 'Basic Settings',

				// The tab contents.
				elements: [
					{
						// Text input field for the abbreviation text.
						type: 'text',
						id: 'abbr',
						label: 'Abbreviation',

						// Validation checking whether the field is not empty.
						validate: CKEDITOR.dialog.validate.notEmpty( "Abbreviation field cannot be empty" )
					},
					{
						// Text input field for the abbreviation title (explanation).
						type: 'text',
						id: 'title',
						label: 'Explanation',
						validate: CKEDITOR.dialog.validate.notEmpty( "Explanation field cannot be empty" )
					}
				]
			},

			// Definition of the Advanced Settings dialog tab (page).
			{
				id: 'tab-adv',
				label: 'Advanced Settings',
				elements: [
					{
						// Another text field for the abbr element id.
						type: 'text',
						id: 'id',
						label: 'Id'
					}
				]
			}
		],

		// This method is invoked once a user clicks the OK button, confirming the dialog.
		onOk: function() {

			// The context of this function is the dialog object itself.
			// http://docs.ckeditor.com/#!/api/CKEDITOR.dialog
			var dialog = this;

			// Creates a new <abbr> element.
			var abbr = editor.document.createElement( 'abbr' );

			// Set element attribute and text, by getting the defined field values.
			abbr.setAttribute( 'title', dialog.getValueOf( 'tab-basic', 'title' ) );
			abbr.setText( dialog.getValueOf( 'tab-basic', 'abbr' ) );

			// Now get yet another field value, from the advanced tab.
			var id = dialog.getValueOf( 'tab-adv', 'id' );
			if ( id )
				abbr.setAttribute( 'id', id );

			// Finally, inserts the element at the editor caret position.
			editor.insertElement( abbr );
		}
	};
});