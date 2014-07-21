/**
 * @file 
 * @ingroup Drupal2AGG
 * @brief Registers the CKEditor plugin
 * 
 * This file contains the commands for registering the CKEditor Plugin.
 * It adds the button and the dialog box and adds the commands behind them.
 * 
 * @authors Werner Breuer - bluescreenwerner@gmail.com
 * @authors Markus Gutmayer - m.gutmayer@gmail.com
 * 
 * @version 1.0.0 2014-07-01
 * @since Commit 6f56770fb90b50992ff33f46c323061bf4b2dc4b on 2014-06-30
 * 
 */
// Register the plugin within the editor.
CKEDITOR.plugins.add('stukowin_curriculum', {
    // Register the icon
    icons: 'stukowin_curriculum',
    // The plugin initialization logic goes inside this method.
    init: function(editor) {

        // Define an editor command that opens our dialog.
        editor.addCommand('stukowin_curriculum_Dialog', new CKEDITOR.dialogCommand('stukowin_curriculum_Dialog'));

        // Create a toolbar button that executes the above command.
        editor.ui.addButton('stukowin_curriculum', {
            // The text part of the button (if available) and tooltip.
            label: 'Insert Taxonomy',
            // The command to execute on click.
            command: 'stukowin_curriculum_Dialog',
            // The button placement in the toolbar (toolbar group name).
            toolbar: 'insert'
        });

        // Register our dialog file. this.path is the plugin folder path.
        CKEDITOR.dialog.add('stukowin_curriculum_Dialog', this.path + 'dialogs/stukowin_curriculum.js');
    }
});

