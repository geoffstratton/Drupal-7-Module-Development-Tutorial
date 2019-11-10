<?php
 
/**
 * @file
 * A module that displays Google ad banners in the node content.
 */
 
/**
 * Implements hook_node_view().
 */
function googleads_node_view($node, $view_mode, $langcode) {
  $google_ad = '<!-- Google ads -->
                <script async="" src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
               more google ad code';
 
  // inject google_ad into content
  if ($view_mode == 'full' || ($view_mode == 'teaser' && variable_get('googleads_teasers', 0) == 1)) {
       $node->content['google_ads'] = array(
         '#markup' => $google_ad,
         '#weight' => variable_get('googleads_weight', 50)
       );
  }
}
 
/**
 * Implements hook_menu().
 */
function googleads_menu() {
  $items = array();
 
  $items['admin/config/content/googleads'] = array(
     'title' => 'Google Ads',
     'description' => 'Configuration for the Google Ads injector.',
     'page callback' => 'drupal_get_form',
     'page arguments' => array('googleads_form'),
     'access arguments' => array('access administration pages'),
     'type' => MENU_NORMAL_ITEM,
  );
  return $items;
}
 
/**
 * Page callback: Google Ads settings
 *
 * @see googleads_menu()
 */
function googleads_form($form, &$form_state) {
  $form['googleads_weight'] = array(
     '#type' => 'select',
     '#title' => t('Weight'),
     '#description' => t('When the ads are shown in the content area, you can set the position at which they will be shown.'),
     '#options' => drupal_map_assoc(array(0, 10, 20, 30, 40, 50)),
     '#default_value' => variable_get('googleads_weight', 50)
  );
 
  $form['googleads_teasers'] = array(
     '#type' => 'checkbox',
     '#title' => t('Teasers?'),
     '#description' => t('Select this option to show Google Ads on teaser pages as well as full content pages.'),
     '#default_value' => variable_get('googleads_teasers', 0)
  );
  return system_settings_form($form);
}
 
/**
 * Implements validation from the Form API.
 *
 * @param $form
 *   A structured array containing the elements and properties of the form.
 * @param $form_state
 *   An array that stores information about the form's current state
 *   during processing.
 */
function googleads_form_validate($form, &$form_state){
  $gads_weight = $form_state['values']['googleads_weight'];
  if (!is_numeric($gads_weight)){
    form_set_error('current_pos', t('You must enter a number for the weight!'));
  }
  elseif ($gads_weight < -50){
    form_set_error('googleads_weight', t('The weight must be greater than or equal to -50.'));
  }
  elseif ($gads_weight > 50) {
    form_set_error('googleads_weight'. t('The weight must be less than or equal to 50.'));
  }
  $gads_teaser = $form_state['values']['googleads_teasers'];
  if ($gads_teaser != 0 && $gads_teaser != 1){
    form_set_error('current_pos', t('The teasers option should be yes or no (1, 0)!'));
  }
}
 
?>