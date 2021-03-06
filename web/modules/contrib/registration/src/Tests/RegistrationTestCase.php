<?php
namespace Drupal\registration\Tests;

/**
 * @file
 * Tests for the Registration module
 */
class RegistrationTestCase extends \Drupal\simpletest\WebTestBase {

  protected $profile = 'standard';

  public function setUpEntity() {
    // Create registration bundle
    $this->registration_type_name = $this->randomName();
    $label = \Drupal\Component\Utility\Unicode::strtoupper($this->registration_type_name);
    $this->registration_type = \Drupal::entityManager()->getStorage('registration_type')->create([
      'name' => $this->registration_type_name,
      'label' => $label,
    ]);
    $this->registration_type->save();

    // Field
    $field_name = 'test_registration_field';
    // @FIXME
    // Fields and field instances are now exportable configuration entities, and
    // the Field Info API has been removed.
    // 
    // 
    // @see https://www.drupal.org/node/2012896
    // $this->field = field_create_field(array(
    //       'field_name' => $field_name,
    //       'type' => 'registration',
    //     ));


    // Create main entity
    $this->host_entity_type = 'node';
    $this->host_entity = $this->drupalCreateNode();
    list($this->host_entity_id,, $this->host_entity_bundle) = entity_extract_ids($this->host_entity_type, $this->host_entity);

    // Field instance
    // @FIXME
    // Fields and field instances are now exportable configuration entities, and
    // the Field Info API has been removed.
    // 
    // 
    // @see https://www.drupal.org/node/2012896
    // $this->field_instance = field_create_instance(array(
    //       'field_name' => $field_name,
    //       'entity_type' => $this->host_entity_type,
    //       'bundle' => $this->host_entity_bundle,
    //       'display' => array(
    //         'default' => array(
    //           'type' => 'registration_form',
    //         ),
    //       ),
    //     ));


    // Set registration type for entity
    $this->host_entity->{$field_name}[\Drupal\Core\Language\Language::LANGCODE_NOT_SPECIFIED][0]['registration_type'] = $this->registration_type_name;
    $this->host_entity->save();

    $uri = entity_uri($this->host_entity_type, $this->host_entity);
    $this->host_entity_path = $uri['path'];
  }

  public function entityLastId($entity_type) {
    $query = new EntityFieldQuery();
    $result = $query
      ->entityCondition('entity_type', $entity_type)
      ->entityOrderBy('entity_id', 'DESC')
      ->range(0, 1)
      ->execute();
    return key($result[$entity_type]);
  }

  public function setHostEntitySettings(array $settings = []) {
    // @todo: Remove ['settings']. Settings must be set in schema. registration_update_entity_settings() currently requires this.
    $settings['settings'] = serialize(isset($settings['settings']) ? $settings['settings'] : []);
    registration_update_entity_settings($this->host_entity_type, $this->host_entity_id, $settings);
  }

  public /**
   * Create a Registration programmatically
   *
   * @param array $values
   *   Additional properties to add to registration entity.
   */
  function createRegistration(array $values = []) {
    $registration = \Drupal::entityManager()->getStorage('registration')->create([
      'entity_type' => $this->host_entity_type,
      'entity_id' => $this->host_entity_id,
      'type' => $this->registration_type_name,
    ] + $values);
    $registration->save();
    return $registration;
  }

  public /**
   * Loads a registration from the database, avoiding the cache.
   *
   * @param int $registration_id
   *   A registrations' unique identifier.
   */
  function loadRegistration($registration_id) {
    $this->resetRegistration();
    return registration_load($registration_id);
  }

  public /**
   * Reset session cache of registration entities.
   */
  function resetRegistration() {
    entity_get_controller('registration')->resetCache();
  }

}
