<?php
namespace Drupal\registration;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityContentBase;

/**
 * Defines the Registration entity.
 *
 * @ingroup registration
 *
 * @ContentEntityType(
 *   id = "registration",
 *   label = @Translation("Registration"),
 *   fieldable = TRUE,
 *   handlers = {
 *     "list_builder" = "Drupal\registration\Controller\MailchimpSignupListBuilder",
 *     "form" = {
 *       "add" = "Drupal\registration\Form\RegistrationForm",
 *       "edit" = "Drupal\registration\Form\RegistrationForm",
 *       "delete" = "Drupal\registration\Form\RegistrationDeleteForm"
 *     }
 *   },
 *   config_prefix = "registration",
 *   admin_permission = "administer registrations",
 *   entity_keys = {
 *     "id" = "registration_id",
 *     "bundle" = "type",
 *   },
 * )
 */
class Registration extends EntityContentBase implements RegistrationInterface {

  public
    $registration_id,
    $type,
    $entity_id,
    $entity_type,
    $anon_mail = NULL,
    $user_uid = NULL,
    $count,
    $author_uid,
    $state,
    $created,
    $updated;

  /**
   * Specifies the default label, which is picked up by label() by default.
   */
  protected function label() {
    $wrapper = entity_metadata_wrapper('registration', $this);
    $host = $wrapper->entity->value();
    if ($host) {
      return t('Registration for !title', array(
          '!title' => $host->label(),
        )
      );
    }
    return '';
  }

  /**
   * Build content for Registration.
   *
   * @return array
   *   Render array for a registration entity.
   */
  public function buildContent($view_mode = 'full', $langcode = NULL) {
    $build = parent::buildContent($view_mode, $langcode);
    $wrapper = entity_metadata_wrapper('registration', $this);

    $host_entity_type_info = \Drupal::entityManager()->getDefinition($this->entity_type);
    $host_entity = $wrapper->entity->value();
    $state = $wrapper->state->value();
    $author = $wrapper->author->value();
    $user = $wrapper->user->value();
    list(, , $host_entity_bundle) = entity_extract_ids($this->entity_type, $host_entity);

    $host_label = $host_entity->label();

    $host_uri = $host_entity ? entity_uri($this->entity_type, $host_entity) : NULL;

    $build['mail'] = array(
      '#theme' => 'registration_property_field',
      '#label' => t('Email Address'),
      '#items' => array(
        array(
          '#markup' => $wrapper->mail->value(),
        ),
      ),
      '#classes' => 'field field-label-inline clearfix',
    );

    // Link to host entity.
    $host_entity_link_label = (isset($host_entity_type_info['bundles'][$host_entity_bundle]['label'])) ? '<div class="field-label">' . $host_entity_type_info['bundles'][$host_entity_bundle]['label'] . '</div>' : '';

    // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $build['host_entity_link'] = array(
//       '#theme' => 'registration_property_field',
//       '#label' => $host_entity_link_label,
//       '#items' => array(
//         array(
//           '#markup' => l($host_label, $host_uri['path']),
//         ),
//       ),
//       '#classes' => 'field field-label-inline clearfix',
//     );


    $build['created'] = array(
      '#theme' => 'registration_property_field',
      '#label' => t('Created'),
      '#items' => array(
        array(
          '#markup' => format_date($this->created),
        ),
      ),
      '#classes' => 'field field-label-inline clearfix',
    );

    $build['updated'] = array(
      '#theme' => 'registration_property_field',
      '#label' => t('Updated'),
      '#items' => array(
        array(
          '#markup' => format_date($this->updated),
        ),
      ),
      '#classes' => 'field field-label-inline clearfix',
    );

    $build['slots'] = array(
      '#theme' => 'registration_property_field',
      '#label' => t('Slots Used'),
      '#items' => array(
        array(
          '#markup' => $this->count,
        ),
      ),
      '#classes' => 'field field-label-inline clearfix',
    );

    if ($author) {
      $build['author'] = array(
        '#theme' => 'registration_property_field',
        '#label' => t('Author'),
        '#items' => array(
          array(
            '#markup' => _theme('username', array('account' => $author)),
          ),
        ),
        '#classes' => 'field field-label-inline clearfix',
        '#attributes' => '',
      );
    }

    if ($user) {
      $build['user'] = array(
        '#theme' => 'registration_property_field',
        '#label' => t('User'),
        '#items' => array(
          array(
            '#markup' => _theme('username', array('account' => $user)),
          ),
        ),
        '#classes' => 'field field-label-inline clearfix',
        '#attributes' => '',
      );
    }

    $build['state'] = array(
      '#theme' => 'registration_property_field',
      '#label' => t('State'),
      '#items' => array(
        array(
          '#markup' => ($state) ? filter_xss_admin($state->label()) : '',
        ),
      ),
      '#classes' => 'field field-label-inline clearfix',
    );

    return $build;
  }

  /**
   * Save registration.
   *
   * @see entity_save()
   */
  public function save() {
    // Set a default state if not provided.
    $wrapper = entity_metadata_wrapper('registration', $this);
    $state = $wrapper->state->value();
    if (!$state) {
      $default_state = registration_get_default_state($wrapper->type->value());
      if ($default_state) {
        $this->state = $default_state->identifier();
      }
    }

    $this->updated = REQUEST_TIME;

    if (!$this->registration_id && empty($this->created)) {
      $this->created = REQUEST_TIME;
    }
    return parent::save();
  }

  /**
   * Specify URI.
   */
  protected function defaultUri() {
    return array('path' => 'registration/' . $this->internalIdentifier());
  }

  /**
   * Determine registrant type relative to a given account.
   *
   * @object $account
   *   A Drupal user
   *
   * @return string|NULL
   *   Can be me, user, anon or NULL if account is empty and no anon email set.
   */
  public function registrant_type($account) {
    $reg_type = NULL;
    if (!empty($account)) {
      if ($account->uid && $account->uid === $this->user_uid) {
        $reg_type = REGISTRATION_REGISTRANT_TYPE_ME;
      }
      elseif ($this->user_uid) {
        $reg_type = REGISTRATION_REGISTRANT_TYPE_USER;
      }
    }
    if (!empty($this->anon_mail)) {
      $reg_type = REGISTRATION_REGISTRANT_TYPE_ANON;
    }
    return $reg_type;
  }

}
