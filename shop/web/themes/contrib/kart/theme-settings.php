<?php

use Drupal\Core\Form\FormStateInterface;

/**
 * Custom setting for kart theme.
 */
function kart_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
  $form['#attached']['library'][] = 'kart/theme-settings';
  $kartpro_img = $GLOBALS['base_url'] . '/' . \Drupal::service('extension.list.theme')->getPath('kart') . '/images/kartpro.jpg';
  $kartpro = '<img src="'.$kartpro_img.'" alt="kartpro" />';
  $form['kart'] = [
    '#type'       => 'vertical_tabs',
    '#title'      => '<h3 class="settings-form-title">' . t('') . '</h3>',
    '#default_tab' => 'general',
  ];
  // General settings tab.
  $form['general'] = [
    '#type'  => 'details',
    '#title' => t('General'),
    '#description' => t('<h4>Thanks for using kart Theme</h4><p>kart is a free Drupal commerce theme designed and developed by <a href="https://dev5.dev/" target="_blank">Dev5</a></p>'),
    '#group' => 'kart',
  ];
  // layout tab.
  $form['layout'] = [
    '#type'  => 'details',
    '#title' => t('Layout'),
    '#group' => 'kart',
  ];
  // Theme Color tab.
  $form['color'] = [
    '#type'  => 'details',
    '#title' => t('Theme Color'),
    '#group' => 'kart',
  ];
  // Social tab.
  $form['social'] = [
    '#type'  => 'details',
    '#title' => t('Social'),
    '#description' => t('These icons appear in the footer region.'),
    '#group' => 'kart',
  ];
  // Slider tab.
  $form['slider'] = [
    '#type'  => 'details',
    '#title' => t('Homepage Slider'),
    '#group' => 'kart',
  ];
  // Header tab.
  $form['header'] = [
    '#type'  => 'details',
    '#title' => t('Header'),
    '#group' => 'kart',
  ];
  // Sidebar tab.
  $form['sidebar'] = [
    '#type'  => 'details',
    '#title' => t('Sidebar'),
    '#group' => 'kart',
  ];
  // Content tab.
  $form['content'] = [
    '#type'  => 'details',
    '#title' => t('Content'),
    '#group' => 'kart',
  ];
  // Footer tab.
  $form['footer'] = [
    '#type'  => 'details',
    '#title' => t('Footer'),
    '#group' => 'kart',
  ];
  // components tab.
  $form['components'] = [
    '#type'  => 'details',
    '#title' => t('Components'),
    '#group' => 'kart',
  ];
  // Insert codes
  $form['insert_codes'] = [
    '#type'  => 'details',
    '#title' => t('Insert Codes'),
    '#group' => 'kart',
  ];
  // Support tab.
  $form['support'] = [
    '#type'  => 'details',
    '#title' => t('Support'),
    '#group' => 'kart',
  ];
  // Upgrade to kartpro tab.
  $form['upgrade'] = [
    '#type'  => 'details',
    '#title' => t('Upgrade to Kart Pro'),
    '#description'  => t("<h4>Upgrade To kartPro For $49 Only.</h4><p><a href='https://dev5.dev/theme/drupal/kartpro' target='_blank'>Purchase kartPro</a> || <a href='https://kartpro.dev5.dev' target='_blank'>kartPro Demo</a></p><p>$kartpro</p>"),
    '#group' => 'kart',
  ];
  // General
  $form['general']['general_info'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Theme Info'),
    '#description' => t('<a href="https://dev5.dev/theme/drupal/kart" target="_blank">Theme Homepage</a> || <a href="https://kart.dev5.dev" target="_blank">Theme Demo</a> || <a href="https://dev5.dev/doc/kart" target="_blank">Theme Documentation</a> || <a href="https://dev5.dev/doc/kart/support" target="_blank">Theme Support</a>'),
  ];

  $form['general']['general_info_upgrade'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Upgrade To kartPro for $49 only'),
    '#description' => t('<a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Purchase kartPro</a> || <a href="https://kartpro.dev5.dev" target="_blank">kartPro Demo</a>'),
  ];
  // Layout -> Container width
  $form['layout']['layout_container'] = [
    '#type'        => 'fieldset',
    '#title'         => t('Container width (px)'),
  ];
  $form['layout']['layout_container']['container_width'] = [
    '#type'          => 'number',
    '#default_value' => theme_get_setting('container_width', 'kart'),
    '#description'   => t('Set width of the container in px. Default width is 1260px.'),
  ];
  // Layout -> Header Layout
  $form['layout']['layout_header'] = [
    '#type'        => 'fieldset',
    '#title'         => t('Header Layout'),
  ];
  $form['layout']['layout_header']['header_width'] = [
    '#type'          => 'select',
    '#options' => array(
    	'header_width_contained' => t('contained'),
    	'header_width_full' => t('Full Width'),),
    '#default_value' => theme_get_setting('header_width', 'kart'),
  ];
  // Layout -> Main Layout
  $form['layout']['layout_main'] = [
    '#type'        => 'fieldset',
    '#title'         => t('Main Layout'),
  ];
  $form['layout']['layout_main']['main_width'] = [
    '#type'          => 'select',
    '#options' => array(
    	'main_width_contained' => t('contained'),
    	'main_width_full' => t('Full Width'),),
    '#default_value' => theme_get_setting('main_width', 'kart'),
  ];
  // Layout -> Footer Layout
  $form['layout']['layout_footer'] = [
    '#type'        => 'fieldset',
    '#title'         => t('Footer Layout'),
  ];
  $form['layout']['layout_footer']['footer_width'] = [
    '#type'          => 'select',
    '#options' => array(
    	'footer_width_contained' => t('contained'),
    	'footer_width_full' => t('Full Width'),),
    '#default_value' => theme_get_setting('footer_width', 'kart'),
  ];
  // Color
  $form['color']['theme_color'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Theme Color'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
  ];
  // Social settings
  /* Social -> Show or hide all icons */
  $form['social']['all_icons'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Show Social Icons'),
  ];
  $form['social']['all_icons']['all_icons_show'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show social icons in footer'),
    '#default_value' => theme_get_setting('all_icons_show', 'kart'),
    '#description'   => t("Check this option to show social icons in footer. Uncheck to hide."),
  ];
  // Facebook.
    $form['social']['facebook'] = [
    '#type'        => 'details',
    '#title'       => t("Facebook"),
  ];
  $form['social']['facebook']['facebook_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Facebook Url'),
    '#description'   => t("Enter yours facebook profile or page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('facebook_url', 'kart'),
  ];
  // Twitter.
  $form['social']['twitter'] = [
    '#type'        => 'details',
    '#title'       => t("Twitter"),
  ];
  $form['social']['twitter']['twitter_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Twitter Url'),
    '#description'   => t("Enter yours twitter page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('twitter_url', 'kart'),
  ];
  // Instagram.
  $form['social']['instagram'] = [
    '#type'        => 'details',
    '#title'       => t("Instagram"),
  ];
  $form['social']['instagram']['instagram_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Instagram Url'),
    '#description'   => t("Enter yours instagram page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('instagram_url', 'kart'),
  ];
  // Linkedin.
  $form['social']['linkedin'] = [
    '#type'        => 'details',
    '#title'       => t("Linkedin"),
  ];
  $form['social']['linkedin']['linkedin_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Linkedin Url'),
    '#description'   => t("Enter yours linkedin page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('linkedin_url', 'kart'),
  ];
  // YouTube.
  $form['social']['youtube'] = [
    '#type'        => 'details',
    '#title'       => t("YouTube"),
  ];
  $form['social']['youtube']['youtube_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('YouTube Url'),
    '#description'   => t("Enter yours youtube.com page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('youtube_url', 'kart'),
  ];
  // Vimeo.
  $form['social']['vimeo'] = [
    '#type'        => 'details',
    '#title'       => t("Vimeo"),
  ];
  $form['social']['vimeo']['vimeo_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('YouTube Url'),
    '#description'   => t("Enter yours vimeo.com page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('vimeo_url', 'kart'),
  ];
  // Social -> vk.com url.
  $form['social']['vk'] = [
    '#type'        => 'details',
    '#title'       => t("vk.com"),
  ];
  $form['social']['vk']['vk_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('vk.com'),
    '#description'   => t("Enter yours vk.com page url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('vk_url', 'kart'),
  ];
  // Social -> whatsapp.
  $form['social']['whatsapp'] = [
    '#type'        => 'details',
    '#title'       => t("whatsapp"),
  ];
  $form['social']['whatsapp']['whatsapp_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('WhatsApp'),
    '#description'   => t("Enter yours whatsapp url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('whatsapp_url', 'kart'),
  ];
  // Social -> github.
  $form['social']['github'] = [
    '#type'        => 'details',
    '#title'       => t("Github"),
  ];
  $form['social']['github']['github_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Github'),
    '#description'   => t("Enter yours github url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('github_url', 'kart'),
  ];
  // Social -> telegram.
  $form['social']['telegram'] = [
    '#type'        => 'details',
    '#title'       => t("Telegram"),
  ];
  $form['social']['telegram']['telegram_url'] = [
    '#type'          => 'textfield',
    '#title'         => t('Telegram'),
    '#description'   => t("Enter yours telegram url. Leave the url field blank to hide this icon."),
    '#default_value' => theme_get_setting('telegram_url', 'kart'),
  ];
  // Slider
  $form['slider']['slider_show_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Enable Slider'),
    '#description'   => t("Slider will be enabled on the pages that have content in Header Hero block region."),
  ];
  $form['slider']['slider_style'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Slider Style'),
    '#description'   => t('Only Classic slider style is available in the free version of the theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy KartPro for $49 only</a> for all slider styles.'),
  ];
  $form['slider']['slider_style']['slider_style_options'] = [
    '#type'          => 'radios',
    '#options' => array(
    	'slider_one' => t('Basic Slider (text only)'),
      'slider_two' => t('Basic Slider (text and image)'),
      'slider_three' => t('Classic Slider'),
      'slider_four' => t('Layered Slider'),
    ),
    '#default_value' => 'slider_three',
    '#disabled'   => TRUE,

  ];
  $form['slider']['slider_code'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Slider Code'),
    '#description'   => t('Create a block and place it in the slider block region. Please refer to the <a href="https://dev5.dev/doc/kart/hero-slider" target="_blank">slider documentation page</a> for more details.'),
  ];
  $form['slider']['slider_doc'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Slider Documentation'),
    '#description'   => t('<p>Please refer to the <a href="https://dev5.dev/doc/kart/hero-slider" target="_blank">slider documentation page</a> for more details.</p>'),
  ];
  // Header
  $form['header']['sticky_header'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Sticky Header'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
  ];
  $form['header']['header_links'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Documentation Links'),
    '#description'   => t('<p><a href="https://dev5.dev/doc/kart/favicon-icon" target="_blank">How to change favicon icon</a></p><p><a href="https://dev5.dev/doc/kart/website-logo" target="_blank">How to manage website logo</a></p><p><a href="https://dev5.dev/doc/kart/header-search-form" target="_blank">Header Search Form</a></p><p><a href="https://dev5.dev/doc/kart/header-main-menu" target="_blank">Header main menu</a></p>'),
  ];
  // Sidebar
  $form['sidebar']['animated_sidebar'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Animated Sliding Sidebar'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
  ];
  // Content
  $form['content']['content_tab'] = [
    '#type'  => 'vertical_tabs',
  ];
  // content -> Demo site
  $form['content_tab']['demo_content'] = [
    '#type'        => 'details',
    '#title'       => t('Demo Site Content'),
    '#description'   => t('You can <a href="https://dev5.dev/demo-site/kart" target="_blank">purchase demo site content</a> for $19 only. This contains all Drupal files and database file. We can also create demo site on your server.'),
    '#group' => 'content_tab',
  ];
  // content -> Homepage  content
  $form['content_tab']['home_content'] = [
    '#type'        => 'details',
    '#title'       => t('Homepage content'),
    '#description' => t('<p>Please follow this tutorial to add content on homepage.</p><p><a href="https://dev5.dev/doc/kart/create-homepage" target="_blank">How To Create Homepage</a></p><p><a href="https://dev5.dev/doc/kart/add-content-homepage" target="_blank">How to add content on homepage</a></p>'),
    '#group' => 'content_tab',
  ];
  // content -> Animated Content
  $form['content_tab']['animated_content'] = [
    '#type'        => 'details',
    '#title'       => t('Animated Content'),
    '#description' => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
    '#group' => 'content_tab',
  ];

  // content -> shortcodes
  $form['content_tab']['shortcode'] = [
    '#type'        => 'details',
    '#title'       => t('Shortcodes'),
    '#description' => t('<p>kart theme has some custom shortcodes. You can create some styling content using these shortcodes.</p><p>Please visit this tutorial page for details. <a href="https://dev5.dev/doc/kart/custom-shortcodes" target="_blank">Shortcodes in kart theme</a>.</p>'),
    '#group' => 'content_tab',
  ];
  // content -> comment
  $form['content_tab']['comment'] = [
    '#type'        => 'details',
    '#title'       => t('Comment'),
    '#description' => t(''),
    '#group' => 'content_tab',
  ];

  // content -> comment -> Highlight author comment
  $form['content_tab']['comment']['comment_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Highlight Node Author Comment'),
  ];
  $form['content_tab']['comment']['comment_section']['highlight_author_comment'] = [
    '#type'          => 'checkbox',
    '#title'         => t("Highlight Node Author's Comments"),
    '#default_value' => theme_get_setting('highlight_author_comment', 'kart'),
    '#description'   => t("Check this option to highlight node author's comments."),
  ];
  // Scroll to top.
  $form['footer']['scrolltotop'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Scroll To Top'),
  ];
  $form['footer']['scrolltotop']['scrolltotop_on'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable scroll to top feature.'),
    '#default_value' => theme_get_setting('scrolltotop_on', 'kart'),
    '#description'   => t("Check this option to enable scroll to top feature. Uncheck to disable this fearure and hide scroll to top icon."),
  ];
  // Footer -> Copyright.
  $form['footer']['copyright'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Website Copyright Text'),
  ];
  $form['footer']['copyright']['copyright_text'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Show website copyright text in footer.'),
    '#default_value' => theme_get_setting('copyright_text', 'kart'),
    '#description'   => t("Check this option to show website copyright text in footer. Uncheck to hide."),
  ];
  // Footer -> Copyright -> custom copyright text
  $form['footer']['copyright']['copyright_text_custom'] = [
    '#type'          => 'fieldset',
    '#title'         => t('Custom copyright text'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
  ];
  // Footer -> Cookie message.
  $form['footer']['cookie'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Cookie Consent message'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
  ];
  $form['footer']['cookie']['cookie_message'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Show Cookie Consent Message'),
    '#description'   => t('Make your website EU Cookie Law Compliant. According to EU cookies law, websites need to get consent from visitors to store or retrieve cookies.'),
  ];
  /**
   * Components
   */
  $form['components']['components_tab'] = [
    '#type'  => 'vertical_tabs',
  ];
  // Page loader
  $form['components_tab']['preloader'] = [
    '#type'        => 'details',
    '#title'       => t('Pre Page Loader'),
    '#description' => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
    '#group' => 'components_tab',
  ];
  // Font icons
  $form['components_tab']['icon_tab'] = [
    '#type'        => 'details',
    '#title'       => t('Font Icons'),
    '#group' => 'components_tab',
  ];
  $form['components_tab']['icon_tab']['bootstrap_icons'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Bootstrap Font Icons'),
    '#description'   => t('Bootstrap Font Icons is enabled by default. You can use these font icons on your website. Read more about <a href="https://dev5.dev/doc/kart/font-icons" target="_blank">Bootstrap Icons</a>'),
  ];
  $form['components_tab']['icon_tab']['fontawesome6_sec'] = [
    '#type'        => 'fieldset',
    '#title'       => t('FontAwesome 6'),
		'#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>')
  ];
  // share page
  $form['components_tab']['node_share'] = [
    '#type'        => 'details',
    '#title'       => t('Share Page'),
    '#description' => t('<p><strong>Share Page On Social Media</strong></p><p>This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a></p>'),
    '#group' => 'components_tab',
  ];
  /**
   * Insert Codes
   */
  $form['insert_codes']['insert_codes_tab'] = [
    '#type'  => 'vertical_tabs',
  ];
  // Insert Codes -> CSS
  $form['insert_codes']['css'] = [
    '#type'        => 'details',
    '#title'       => t('CSS Codes'),
    '#group'       => 'insert_codes_tab',
  ];
  // Insert Codes -> Head
  $form['insert_codes']['head'] = [
    '#type'        => 'details',
    '#title'       => t('Head'),
    '#description' => t('<h4>Insert Codes Before &lt;/HEAD&gt;</h4><hr />'),
    '#group' => 'insert_codes_tab',
  ];
  // Insert Codes -> Body
  $form['insert_codes']['body'] = [
    '#type'        => 'details',
    '#title'       => t('Body'),
    '#group' => 'insert_codes_tab',
  ];
  // Insert Codes -> css
  $form['insert_codes']['css']['css_custom'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Addtional CSS'),
  ];
  $form['insert_codes']['css']['css_custom']['css_extra'] = [
    '#type'          => 'checkbox',
    '#title'         => t('Enable Addtional CSS'),
    '#default_value' => theme_get_setting('css_extra', 'kart'),
    '#description'   => t("Check this option to enable additional styling / css. Uncheck to disable this feature."),
  ];
  $form['insert_codes']['css']['css_code'] = [
    '#type'          => 'textarea',
    '#title'         => t('Addtional CSS Codes'),
    '#default_value' => theme_get_setting('css_code', 'kart'),
    '#description'   => t('Add your own CSS codes here to customize the appearance of your site. Please refer to this tutorial for detail: <a href="https://dev5.dev/doc/kart/custom-css" target="_blank">Custom CSS</a>'),
  ];
  // Insert Codes -> Head -> Head codes
  $form['insert_codes']['head']['insert_head'] = [
    '#type'          => 'fieldset',
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
  ];
  // Insert Codes -> Body -> Body start codes
  $form['insert_codes']['body']['insert_body_start_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Insert code after &lt;BODY&gt; tag'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
  ];
  // Insert Codes -> Body -> Body End codes
  $form['insert_codes']['body']['insert_body_end_section'] = [
    '#type'        => 'fieldset',
    '#title'       => t('Insert code before &lt;/BODY&gt; tag'),
    '#description'   => t('This feature is available in the premium version of this theme. <a href="https://dev5.dev/theme/drupal/kartpro" target="_blank">Buy kartPro for $49 only.</a>'),
  ];
  // Support
  $form['support']['info'] = [
    '#type'        => 'fieldset',
    '#title'         => t('Theme Support'),
    '#description' => t('<h4>Documentation</h4>
    <p>We have a detailed documentation about how to use theme. Please read the <a href="https://dev5.dev/doc/kart" target="_blank">kart Theme Documentation</a>.</p>
    <hr />
    <h4>Open An Issue</h4>
    <p>If you need support that is beyond our theme documentation, please <a href="https://www.drupal.org/project/issues/kart?categories=All" target="_blank">open an issue</a> at project page.</p>
    <hr />
    <h4>Contact Us</h4>
    <p>If you need some specific customization in theme, please contact us<br><a href="https://dev5.dev/contact" target="_blank">dev5.dev/contact</a></p>'),
  ];
}
