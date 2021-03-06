<?php

/**
 * @file
 * Field hooks.
 */

/**
 * Implements hook_field_info().
 */
function registration_field_info() {
  // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// return array(
//     'registration' => array(
//       'label' => t('Registration'),
//       'description' => t('Enables registrations of a selected type for an entity.'),
//       'settings' => array(),
//       'instance_settings' => array(
//         'default_registration_settings' => array(
//           'status' => 0,
//           'capacity' => 0,
//           'scheduling' => array(
//             'open' => NULL,
//             'close' => NULL,
//           ),
//           'reminder' => array(
//             'send_reminder' => 0,
//             'reminder_settings' => array(
//               'reminder_date' => NULL,
//               'reminder_template' => NULL,
//             ),
//           ),
//           'settings' => array(
//             'maximum_spaces' => 1,
//             'multiple_registrations' => -1,
//             'from_address' => variable_get('site_mail', ini_get('sendmail_from')),
//           ),
//         ),
//       ),
//       'default_widget' => 'registration_select',
//       'default_formatter' => 'registration_default',
//       'no_ui' => FALSE,
//     ),
//   );

}

/**
 * Implements hook_field_instance_settings_form().
 *
 * Add default registration field instance settings.
 */
function registration_field_instance_settings_form($field, $instance) {
  $form = $form_state = array();
  $default_settings = isset($instance['settings']['default_registration_settings']) ? $instance['settings']['default_registration_settings'] : array();

  $form['register_tab'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable Register Tab'),
    '#default_value' => isset($default_settings['register_tab']) ? $default_settings['register_tab'] : TRUE,
    '#required' => FALSE,
    '#description' => t('Enable a tab on the content displaying the registration form.'),
  );

  // Flatten scheduling and reminder settings since this form is in tree mode.
  foreach ($default_settings as $key => $val) {
    if ($key != 'settings' and is_array($val)) {
      foreach ($val as $key1 => $val1) {
        if (is_array($val1)) {
          foreach ($val1 as $key2 => $val2) {
            $default_settings[$key2] = $val2;
          }
        }
        else {
          $default_settings[$key1] = $val1;
        }
      }
      unset($default_settings[$key]);
    }
  }

  $settings_form = registration_entity_settings_form($form, $form_state, $default_settings);

  $form['default_registration_settings'] = array(
    '#type' => 'fieldset',
    '#title' => t('Default Registration settings'),
    '#collapsible' => TRUE,
    '#description' => t('These settings will be applied when an entity with this field is saved and does not yet have it\'s own settings applied.')
  );

  // Unset the save button just in case.
  unset($settings_form['save']);
  $form['default_registration_settings'] += $settings_form;

  // @todo: validation
  return $form;
}





/**
 * Implements hook_field_widget_info().
 */
function registration_field_widget_info() {
  return array(
    'registration_select' => array(
      'label' => t('Registration Type'),
      'field types' => array('registration'),
      'settings' => array(),
      'behaviors' => array(
        'multiple values' => FIELD_BEHAVIOR_DEFAULT,
        'default value' => FIELD_BEHAVIOR_DEFAULT,
      ),
    ),
  );
}

/**
 * Implements hook_field_widget_form().
 */
function registration_field_widget_form(&$form, &$form_state, $field, $instance, $langcode, $items, $delta, $element) {
  $options = array('' => t('-- Disable Registrations --'));
  foreach (registration_get_types() as $type) {
    $options[$type->name] = $type->label;
  }
  $element += array(
    '#type' => 'select',
    '#options' => $options,
    '#default_value' => isset($items[$delta]) ? $items[$delta] : array(),
  );

  // force some help text into the field, appending anything the user added.
  $element['#description'] .= ' ' . t('Select what type of registrations should be
    enabled for this @type. Depending on the display settings, it will appear
    as either string, registration link, or form.', array('@type' => $instance['bundle']));

  return array('registration_type' => $element);
}

/**
 * Implements hook_field_formatter_info().
 */
function registration_field_formatter_info() {
  return array(
    'registration_link' => array(
      'label' => t('Registration Link'),
      'field types' => array('registration'),
      'settings' => array(
        'label' => NULL,
        'i18n_string_key' => NULL,
      ),
    ),
    'registration_form' => array(
      'label' => t('Registration Form'),
      'field types' => array('registration'),
    ),
  );
}



/**
 * Form element process handler for registration_field_formatter_settings_form().
 */
function registration_field_formatter_settings_form_process($element, &$form_state, &$form) {
  // For reasons I don't fully understand, when you click the gear button to
  // open the settings, $form_state['submitted'] === TRUE; but after you set the
  // settings and click the 'Update' button, $form_state['submitted'] === FALSE.
  // Furthermore, it's impossible to add a submit handler to this sub-form or
  // the 'Manage Display' form as a whole.
  //
  // Anyway, to avoid blowing away the string translation if the user just
  // wants to look at the string without changing it.
  if ($form_state['submitted'] === FALSE) {
    _registration_translate_update($element['i18n_string_key']['#value'] . ':label', $element['label']['#default_value']);
  }

  return $element;
}





/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Hide the cardinality setting on the field settings for registration fields.
 */
function registration_form_field_ui_field_edit_form_alter(&$form, &$form_state, $form_id) {
  if ($form['#field']['type'] == 'registration') {
    $form['field']['cardinality']['#default_value'] = 1;
    $form['field']['cardinality']['#access'] = FALSE;
    $form['#validate'][] = 'registration_form_field_ui_field_edit_form_validate';
  }
}

/**
 * Validation handler for registration_form_field_ui_field_edit_form.
 *
 * Ensure cardinality is set to 1 on registration fields.
 *
 * @param $form
 * @param $form_state
 */
function registration_form_field_ui_field_edit_form_validate(&$form, &$form_state) {
  if ($form['#field']['type'] == 'registration') {
    if ($form_state['values']['field']['cardinality'] !== 1) {
      form_set_error('cardinality', t('Cardinality on registration fields must be set to one.'));
    }

    // Validate default registration settings.
    $default_settings = $form_state['values']['instance']['settings']['default_registration_settings'];
    $base_elem_key = 'instance][settings][default_registration_settings][';

    // Ensure capacity is a positive integer.
    $capacity = $default_settings['capacity'];
    if (!is_numeric($capacity) || ((int) $capacity != $capacity) || ($capacity < 0)) {
      form_set_error($base_elem_key . 'capacity', t('Capacity must be a positive integer.'));
    }

    // Validate from address.
    if (!valid_email_address($default_settings['settings']['from_address'])) {
      form_set_error($base_elem_key . 'settings][from_address', t('From email address is invalid.'));
    }

    // Validate open date.
    if (!empty($default_settings['scheduling']['open']) && strtotime($default_settings['scheduling']['open']) === FALSE) {
      form_set_error($base_elem_key . 'scheduling][open', t('Date is invalid.'));
    }

    // Validate close date.
    if (!empty($default_settings['scheduling']['close']) && strtotime($default_settings['scheduling']['close']) === FALSE) {
      form_set_error($base_elem_key . 'scheduling][close', t('Date is invalid.'));
    }

    // If sending a reminder, ensure date and template are set.
    if ($default_settings['reminder']['send_reminder']
      && (empty($default_settings['reminder']['reminder_settings']['reminder_date']) ||
        empty($default_settings['reminder']['reminder_settings']['reminder_template']))) {
      form_set_error($base_elem_key . 'reminder][send_reminder', t('If sending a reminder, provide a date and template.'));
    }

    // Validate reminder date.
    if (!empty($default_settings['reminder']['reminder_settings']['reminder_date'])
      && strtotime($default_settings['reminder']['reminder_settings']['reminder_date']) === FALSE) {
      form_set_error($base_elem_key . 'reminder][reminder_settings][reminder_date', t('Reminder date is invalid.'));
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Add a validation handler for registration_form_field_ui_field_overview_form().
 *
 * @param $form
 * @param $form_state
 * @param $form_id
 */
function registration_form_field_ui_field_overview_form_alter(&$form, &$form_state, $form_id) {
  $form['#validate'][] = 'registration_form_field_ui_field_overview_form_validate';
}

/**
 * Validation callback for registration_form_field_ui_field_overview_form().
 *
 * Ensure only one registration field is added per entity.
 *
 * @param $form
 * @param $form_state
 */
function registration_form_field_ui_field_overview_form_validate(&$form, &$form_state) {
  $fields = $form_state['values']['fields'];
  if ($fields['_add_new_field']['type'] == 'registration') {
    foreach ($form['#fields'] as $field_name) {
      $field = field_info_field($field_name);
      if ($field['type'] == 'registration') {
        form_set_error('_add_new_field', t('An entity can only have one registration field.'));
      }
    }
  }
}

/**
 * Implements hook_field_create_instance().
 */
function registration_field_create_instance($instance) {
  // Rebuild menu to recognize registration paths.
  _registration_menu_rebuild($instance);
}

/**
 * Implements hook_field_delete_instance().
 */
function registration_field_delete_instance($instance) {
  // Remove registration paths from menu router.
  _registration_menu_rebuild($instance);

  // @TODO: should we delete all registrations at this point?
}

/**
 * Rebuild the menu for a given registraiton field instance.
 *
 * @param $instance
 *   Field instance being added or deleted.
 */
function _registration_menu_rebuild($instance) {
  $registration_fields = field_read_fields(array('type' => 'registration'));
  if (in_array($instance['field_name'], array_keys($registration_fields))) {
    menu_rebuild();
  }
}
