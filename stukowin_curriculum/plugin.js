/**
 * Basic sample plugin inserting abbreviation elements into CKEditor editing area.
 *
 * Created out of the CKEditor Plugin SDK:
 * http://docs.ckeditor.com/#!/guide/plugin_sdk_sample_1
 */

// Register the plugin within the editor.
CKEDITOR.plugins.add( 'stukowin_curriculum', {

	// Register the icons.
	icons: 'stukowin_curriculum',

	// The plugin initialization logic goes inside this method.
	init: function( editor ) {

		// Define an editor command that opens our dialog.
		editor.addCommand( 'stukowin_curriculum_Dialog', new CKEDITOR.dialogCommand( 'stukowin_curriculum_Dialog' ) );

		// Create a toolbar button that executes the above command.
		editor.ui.addButton( 'stukowin_curriculum', {

			// The text part of the button (if available) and tooptip.
			label: 'Insert Taxonomy',

			// The command to execute on click.
			command: 'stukowin_curriculum_Dialog',

			// The button placement in the toolbar (toolbar group name).
			toolbar: 'insert'
		});

		// Register our dialog file. this.path is the plugin folder path.
		CKEDITOR.dialog.add( 'stukowin_curriculum_Dialog', this.path + 'dialogs/stukowin_curriculum.js' );
	}
});

