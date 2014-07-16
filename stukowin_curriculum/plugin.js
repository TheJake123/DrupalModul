/**
 * @file 
 * @brief Registers this CKEditor plugin
 * 
 * This file contains all commands for registering the CKEditor Plugin.
 * Adds the buttons and dialog box and adds the command behind them.
 * 
 * @author Werner Breuer - bluescreenwerner@gmail.com
 * @author Markus Gutmayr
 * 
 * @version 1.0.0 2014-07-01
 * @since Commit 6f56770fb9 on 2014-06-30
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

