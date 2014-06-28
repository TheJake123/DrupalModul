<?php
// $Id$
/**
 * @file 
 * Callbacks der Administrationsseite für das stukowin-Modul
 */

/**
 * Formularerstellung. Anmerkungen
 * 
 * @ingroup forms
 * @see system_settings_form().
 */
function annotate_admin_settings($form)
{
    $options = node_type_get_names('names');
    $form['stukowin_api_url'] = array(
    '#type' => 'checkboxes',
    '#title' => t('URL zur CEUS-API'),
    '#options' => $options,
    '#default_value' => '',
    '#description' => t('A text field will be available on these content types to make user-specific notes.')
    );
    return system_settings_form($form);
}

function annotate_nodeapi(&$node, $op, $teaser, $page)
{
    global $user;
    switch($op) 
    {
        case 'view': 
            if($user->uid == 0 || !$page)
            {
                break;
            }
            $types_to_annotate = variable_get('annotate_nodetypes', array('page'));
            if(!in_array($node->type, $types_to_annotate)) break;
            $node->content['annotate_form'] = array(
                '#value' => drupal_get_form('annotate_entry_form', $node),
                '#weight' => 10
            );
            break;      
            
            
            
    }
}