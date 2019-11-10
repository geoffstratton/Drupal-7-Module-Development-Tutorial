# Drupal 7 Module Development Tutorial
 How to build a module for Drupal 7, using Google Ads

On my Drupal 7 personal site, I wanted to create a method of automatically injecting my Google Ads banners on all content pages before the text. You could do this manually, but that doesn't retroactively update any existing pages. This is a good time to make use of Drupal's ability to modify content dynamically using its hooks API in a simple custom module.

You control the order of elements on a Drupal node by setting the "Weight" property (an integer typically ranging from -100 to 100) of each piece of content; the higher the weight, the lower the priority, and the further down the page it's likely to appear. A field or node with a value of 0 will appear before 25, which will appear before 30, and so forth.

hook_node_view() is the Drupal 7 API hook that makes it possible to modify the properties and contents of nodes before they're displayed.

But first of all, we need to ensure our module is recognized by Drupal 7. So we create a directory in /sites/all/modules/ -- I called it 'googleads' -- and then inside that directory we create a googleads.info file:

```php
name = Google Ad Injector
description = "Inject the Google Ads banners at the bottom of the content area."
core = 7.x
 
files[] = googleads.module
configure = admin/config/content/googleads
```

The entries here are all important. The "name" and "description" provide a meaningful entry in the Modules listing (admin/modules), "core" tells Drupal 7 your module is compatible, and adding a "configure" directive gives you a "Configure" link in the modules listing. Finally, the "files" directive tells Drupal which other files comprise the module.

Now we should build the googleads.module file itself. We'll get started by writing the code to modify the content pages:
	
```php
/**
 * Implements hook_node_view().
 */
function googleads_node_view($node, $view_mode, $langcode) {
  $google_ad = '<!-- Google ads -->
                <script async="" src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
                rest of google ad code';
 
  // inject google_ad into content
  if ($view_mode == 'full' || ($view_mode == 'teaser' && variable_get('googleads_teasers', 0) == 1)) {
       $node->content['google_ads'] = array(
         '#markup' => $google_ad,
         '#weight' => variable_get('googleads_weight', 50)
       );
  }
}
```

This uses Drupal's hook_node_view() hook to set up a method of modifying every node's content before it's rendered by render($page['content']) in page.tpl.php. When writing custom implementations of hooks, you should name them after your module, both for clarity and to avoid PHP's cannot redeclare error. So in this case the function is called googleads_node_view(), and I've introduced a couple of custom variables: 'googleads_weight' and 'googleads_teasers'. The 'googleads_weight' variable gives the user the option of defining a custom weight, giving them some control over where the ads appear on the page. Meanwhile, calling hook_node_view() will modify every instance of every node, but I don't necessarily want ads appearing in teasers like my category view: so this is user-definable in googleads_teasers. We'll look at how to set these options in a moment.

There are other view modes besides 'full' and 'teaser'. You can certainly make these configurable as well.

Also, Drupal's variable_get() function here is important: it checks Drupal's $conf global variables array for googleads_teasers and googleads_weight, and if neither variable is found, variable_get() supplies default values (0 and 50). If the weight and teaser options have been set by the site administrator, they'd already exist in the $conf array when referenced by our module, and we want to honor those values.

Now, let's set up an administration form so a site admin can easily define the weight and the appearance of ads on teaser pages. We add some additional code to googleads.module:
	
```php
/**
 * Implements hook_menu().
 */
function googleads_menu() {
  $items = array();
 
  $items['admin/config/content/googleads'] = array(
     'title' => 'Google Ads',
     'description' => 'Configuration for the Google Ads injector',
     'page callback' => 'drupal_get_form',
     'page arguments' => array('googleads_form'),
     'access arguments' => array('access administration pages'),
     'type' => MENU_NORMAL_ITEM,
  );
 
  return $items;
}
```

In a large module with thousands of lines of code, it's better practice to begin separating out the different components: the admin page(s) are frequently placed in a separate file called admin.inc.php, which you'd then have to specify in googleads.info. But this is a small module, so I'm leaving them all in googleads.module.

hook_menu() is important for several reasons: it specifies an URL for the administration page, provides a title and description to appear in the administration menu, calls the drupal_get_form() function to render the admin form, and sets the permission level needed to view the form. We want to ensure only a site admin can access the admin page. Next, we'll set up the admin form itself:

```php
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
```

You're using hook_form() to create the form widgets and specify the values that will be submitted (googleads_weight, googleads_teasers). In this case, we get a select box with the values 10, 20, 30, 40, 50 for the weight, and a checkbox to toggle the teaser ads on and all (values 0 and 1). hook_form() also provides a basic submit button.

Finally, you want to validate the form data before it goes into your database:
	
```php
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
    form_set_error('current_pos', t('The teasers option should be yes or no!'));
  }
}
```

Using _form_validate() here. It might seem unnecessary to validate a form that doesn't permit users to do much: they can only select options from a drop-down and toggle a checkbox. But it's good practice never to trust user-submitted values. Who knows, maybe some joker is using the Tamper Data Firefox plugin to change the form submission data.

And that's it! Now we have a simple Google ads injector that allows us to set the weight and select whether or not your ads appear in the teaser for each page. If you save your module file, and flush your Drupal caches, you'll have a "Google Ad Injector" module that you can toggle on and off in your admin pages. If you inspect the variables table in your database after submitting the form for the first time, you'll also see that 'googleads_weight' and 'googleads_teasers' entries are now in there.