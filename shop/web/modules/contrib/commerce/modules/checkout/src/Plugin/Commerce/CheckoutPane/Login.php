<?php

namespace Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\commerce_checkout\Attribute\CommerceCheckoutPane;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\commerce_checkout\Event\CheckoutRegisterEvent;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the login pane.
 */
#[CommerceCheckoutPane(
  id: "login",
  label: new TranslatableMarkup('Log in or continue as guest'),
  admin_description: new TranslatableMarkup("Presents customers with the choice to log in or proceed as a guest during checkout."),
  default_step: "login",
  wrapper_element: "container",
)]
class Login extends CheckoutPaneBase implements CheckoutPaneInterface, ContainerFactoryPluginInterface {

  /**
   * The credentials check flood controller.
   *
   * @var \Drupal\commerce\CredentialsCheckFloodInterface
   */
  protected $credentialsCheckFlood;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthenticationInterface
   */
  protected $userAuth;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?CheckoutFlowInterface $checkout_flow = NULL) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition, $checkout_flow);
    $instance->credentialsCheckFlood = $container->get('commerce.credentials_check_flood');
    $instance->currentUser = $container->get('current_user');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $instance->userAuth = $container->get('user.auth');
    $instance->languageManager = $container->get('language_manager');
    $instance->entityDisplayRepository = $container->get('entity_display.repository');
    $instance->requestStack = $container->get('request_stack');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'allow_guest_checkout' => TRUE,
      'allow_registration' => FALSE,
      'registration_form_mode' => 'register',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationSummary() {
    $parent_summary = parent::buildConfigurationSummary();
    if (!empty($this->configuration['allow_guest_checkout'])) {
      $summary = $this->t('Guest checkout: Allowed') . '<br>';
    }
    else {
      $summary = $this->t('Guest checkout: Not allowed') . '<br>';
    }
    if (!empty($this->configuration['allow_registration'])) {
      $summary .= $this->t('Registration: Allowed') . '<br>';
      $form_modes = $this->entityDisplayRepository->getFormModeOptions('user');
      $summary .= $this->t('Registration form mode: @mode', ['@mode' => $form_modes[$this->configuration['registration_form_mode']]]);
    }
    else {
      $summary .= $this->t('Registration: Not allowed');
    }

    return $parent_summary ? implode('<br>', [$parent_summary, $summary]) : $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['allow_guest_checkout'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow guest checkout'),
      '#default_value' => $this->configuration['allow_guest_checkout'],
    ];
    $form['allow_registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow registration'),
      '#default_value' => $this->configuration['allow_registration'],
    ];
    $form['registration_form_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Registration Form Mode'),
      '#default_value' => $this->configuration['registration_form_mode'],
      '#options' => $this->entityDisplayRepository->getFormModeOptions('user'),
      '#states' => [
        'visible' => [
          ':input[name="configuration[panes][login][configuration][allow_registration]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['allow_guest_checkout'] = !empty($values['allow_guest_checkout']);
      $this->configuration['allow_registration'] = !empty($values['allow_registration']);
      $this->configuration['registration_form_mode'] = $values['registration_form_mode'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    return $this->currentUser->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['#attached']['library'][] = 'commerce_checkout/login_pane';

    $pane_form['returning_customer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Returning Customer'),
      '#attributes' => [
        'class' => [
          'form-wrapper__login-option',
          'form-wrapper__returning-customer',
        ],
      ],
    ];
    $pane_form['returning_customer']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#size' => 60,
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#attributes' => [
        'autocorrect' => 'none',
        'autocapitalize' => 'none',
        'spellcheck' => 'false',
        'autofocus' => 'autofocus',
      ],
    ];
    $pane_form['returning_customer']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#size' => 60,
    ];
    $pane_form['returning_customer']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Log in'),
      '#op' => 'login',
      '#attributes' => [
        'formnovalidate' => 'formnovalidate',
      ],
      '#limit_validation_errors' => [
        array_merge($pane_form['#parents'], ['returning_customer']),
      ],
      '#submit' => [],
    ];
    $pane_form['returning_customer']['forgot_password'] = [
      '#type' => 'link',
      '#title' => $this->t('Forgot password?'),
      '#url' => Url::fromRoute('user.pass'),
    ];

    $pane_form['guest'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Guest Checkout'),
      '#access' => $this->configuration['allow_guest_checkout'],
      '#attributes' => [
        'class' => [
          'form-wrapper__login-option',
          'form-wrapper__guest-checkout',
        ],
      ],
    ];
    $pane_form['guest']['text'] = [
      '#prefix' => '<p>',
      '#suffix' => '</p>',
      '#markup' => $this->t('Proceed to checkout. You can optionally create an account at the end.'),
      '#access' => $this->canRegisterAfterCheckout(),
    ];
    $pane_form['guest']['continue'] = [
      '#type' => 'submit',
      '#value' => $this->t('Continue as Guest'),
      '#op' => 'continue',
      '#attributes' => [
        'formnovalidate' => 'formnovalidate',
      ],
      '#limit_validation_errors' => [],
      '#submit' => [],
    ];

    $pane_form['register'] = [
      '#parents' => array_merge($pane_form['#parents'], ['register']),
      '#type' => 'fieldset',
      '#title' => $this->t('New Customer'),
      '#access' => $this->configuration['allow_registration'],
      '#attributes' => [
        'class' => [
          'form-wrapper__login-option',
          'form-wrapper__guest-checkout',
        ],
      ],
    ];
    $pane_form['register']['mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#description' => $this->t('The email address is not made public. It will only be used if you need to be contacted about your account or for opted-in notifications.'),
      '#required' => FALSE,
    ];
    $pane_form['register']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
      '#description' => $this->t("Several special characters are allowed, including space, period (.), hyphen (-), apostrophe ('), underscore (_), and the @ sign."),
      '#required' => FALSE,
      '#attributes' => [
        'class' => ['username'],
        'autocorrect' => 'off',
        'autocapitalize' => 'off',
        'spellcheck' => 'false',
      ],
      '#default_value' => '',
    ];
    $pane_form['register']['password'] = [
      '#type' => 'password_confirm',
      '#size' => 60,
      '#required' => FALSE,
    ];
    $pane_form['register']['register'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create new account and continue'),
      '#op' => 'register',
      '#weight' => 50,
    ];

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->create([]);
    $form_display = EntityFormDisplay::collectRenderDisplay($account, $this->configuration['registration_form_mode']);
    $form_display->buildForm($account, $pane_form['register'], $form_state);

    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $triggering_element = $form_state->getTriggeringElement();
    $trigger = !empty($triggering_element['#op']) ? $triggering_element['#op'] : 'continue';
    switch ($trigger) {
      case 'continue':
        return;

      case 'login':
        $name_element = $pane_form['returning_customer']['name'];
        $username = $values['returning_customer']['name'];
        $password = trim($values['returning_customer']['password']);
        // Generate the "reset password" url.
        $query = !empty($username) ? ['name' => $username] : [];
        $password_url = Url::fromRoute('user.pass', [], ['query' => $query])->toString();

        if (empty($username) || empty($password)) {
          $form_state->setError($pane_form['returning_customer'], $this->t('Unrecognized username or password. <a href=":url">Have you forgotten your password?</a>', [':url' => $password_url]));
          return;
        }
        $user = $this->userAuth->lookupAccount($username);
        if ($user && $user->isBlocked()) {
          $form_state->setError($name_element, $this->t('The username %name has not been activated or is blocked.', ['%name' => $username]));
          return;
        }
        $client_ip = $this->requestStack->getCurrentRequest()->getClientIp();
        if (!$this->credentialsCheckFlood->isAllowedHost($client_ip)) {
          $form_state->setError($name_element, $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => $password_url]));
          $this->credentialsCheckFlood->register($client_ip, $username);
          return;
        }
        elseif (!$this->credentialsCheckFlood->isAllowedAccount($client_ip, $username)) {
          $form_state->setError($name_element, $this->t('Too many failed login attempts for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => $password_url]));
          $this->credentialsCheckFlood->register($client_ip, $username);
          return;
        }

        if (!$user || !$this->userAuth->authenticateAccount($user, $password)) {
          $this->credentialsCheckFlood->register($client_ip, $username);
          $form_state->setError($name_element, $this->t('Unrecognized username or password. <a href=":url">Have you forgotten your password?</a>', [':url' => $password_url]));
          return;
        }
        $form_state->set('logged_in_uid', $user->id());
        break;

      case 'register':
        $email = $values['register']['mail'];
        $username = $values['register']['name'];
        $password = trim($values['register']['password']);
        if (empty($email)) {
          $form_state->setError($pane_form['register']['mail'], $this->t('Email field is required.'));
          return;
        }
        if (empty($username)) {
          $form_state->setError($pane_form['register']['name'], $this->t('Username field is required.'));
          return;
        }
        if (empty($password)) {
          $form_state->setError($pane_form['register']['password'], $this->t('Password field is required.'));
          return;
        }

        /** @var \Drupal\user\UserInterface $account */
        $account = $this->entityTypeManager->getStorage('user')->create([
          'mail' => $email,
          'name' => $username,
          'pass' => $password,
          'status' => TRUE,
          'langcode' => $this->languageManager->getCurrentLanguage()->getId(),
          'preferred_langcode' => $this->languageManager->getCurrentLanguage()->getId(),
          'preferred_admin_langcode' => $this->languageManager->getCurrentLanguage()->getId(),
        ]);

        $form_display = EntityFormDisplay::collectRenderDisplay($account, $this->configuration['registration_form_mode']);
        $form_display->extractFormValues($account, $pane_form['register'], $form_state);
        $form_display->validateFormValues($account, $pane_form['register'], $form_state);

        // Manually flag violations of fields not handled by the form display.
        // This is necessary as entity form displays only flag violations for
        // fields contained in the display.
        // @see \Drupal\user\AccountForm::flagViolations
        $violations = $account->validate();
        foreach ($violations->getByFields(['name', 'pass', 'mail']) as $violation) {
          [$field_name] = explode('.', $violation->getPropertyPath(), 2);
          $form_state->setError($pane_form['register'][$field_name], $violation->getMessage());
        }

        if (!$form_state->hasAnyErrors()) {
          $account->save();
          $form_state->set('logged_in_uid', $account->id());
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $triggering_element = $form_state->getTriggeringElement();
    $trigger = !empty($triggering_element['#op']) ? $triggering_element['#op'] : 'continue';
    switch ($trigger) {
      case 'continue':
        break;

      case 'login':
      case 'register':
        $storage = $this->entityTypeManager->getStorage('user');
        /** @var \Drupal\user\UserInterface $account */
        $account = $storage->load($form_state->get('logged_in_uid'));
        user_login_finalize($account);
        $this->order->setCustomer($account);
        $client_ip = $this->requestStack->getCurrentRequest()->getClientIp();
        $this->credentialsCheckFlood->clearAccount($client_ip, $account->getAccountName());
        if ($trigger === 'register') {
          // Notify other modules.
          $event = new CheckoutRegisterEvent($account, $this->order);
          $this->eventDispatcher->dispatch($event, CheckoutEvents::CHECKOUT_REGISTER);
        }
        break;
    }

    $form_state->setRedirect('commerce_checkout.form', [
      'commerce_order' => $this->order->id(),
      'step' => $this->checkoutFlow->getNextStepId($this->getStepId()),
    ]);
  }

  /**
   * Checks whether guests can register after checkout is complete.
   *
   * @return bool
   *   TRUE if guests can register after checkout is complete, FALSE otherwise.
   */
  protected function canRegisterAfterCheckout() {
    $completion_register_pane = $this->checkoutFlow->getPane('completion_register');
    return $completion_register_pane->getStepId() != '_disabled';
  }

}
