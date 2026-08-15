<?php

declare(strict_types=1);

namespace Drupal\orbit_banner\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Node form alterations for the Orbit Banner module.
 */
class OrbitBannerFormHooks {

  use StringTranslationTrait;

  /**
   * The field whose selected item count drives the conditional field.
   */
  public const COUNT_SOURCE_FIELD = 'field_orbit_banner_image';

  /**
   * The form key, name attribute and jQuery selector of the count element.
   *
   * The Conditional Fields dependency watches this exact name, so the two must
   * be kept in step.
   *
   * @see \Drupal\orbit_banner\BannerConditions::IMAGE_COUNT_SELECTOR
   */
  public const COUNT_ELEMENT_NAME = 'orbit_banner_image_count';

  /**
   * Implements hook_form_BASE_FORM_ID_alter() for node_form.
   *
   * Adds a hidden input holding the number of selected banner images.
   *
   * The media library widget has no single input that reflects how many items
   * are selected, and it rebuilds itself over AJAX whenever one is added or
   * removed. The States API can only watch a form input, and only binds to the
   * ones present when it attaches, so a dependency pointed at the widget
   * itself would go stale on the first add. This element sits outside the
   * widget's AJAX wrapper, so the binding survives, and orbit-banner-admin.js
   * keeps its value current.
   */
  #[Hook('form_node_form_alter')]
  public function nodeFormAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $entity = $form_state->getFormObject()->getEntity();

    if (!$entity->hasField(self::COUNT_SOURCE_FIELD)) {
      return;
    }

    $form[self::COUNT_ELEMENT_NAME] = [
      '#type' => 'hidden',
      // Seeded server side so the field is in the right state on first paint,
      // before any JavaScript runs.
      '#value' => (string) $entity->get(self::COUNT_SOURCE_FIELD)->count(),
      '#name' => self::COUNT_ELEMENT_NAME,
      // Kept out of the field's own parents so it never reaches the widget's
      // value extraction.
      '#parents' => [self::COUNT_ELEMENT_NAME],
      '#attributes' => [
        'data-orbit-banner-image-count' => self::COUNT_SOURCE_FIELD,
      ],
    ];

    $form['#attached']['library'][] = 'orbit_banner/admin';

    $this->addDependeeNameCallback($form);
  }

  /**
   * Queues ::nameDependeeElement() to run before Conditional Fields does.
   *
   * Conditional Fields registers the dependee during element after_build, then
   * builds the states from a form level after_build. Inserting ahead of that
   * second pass is what lets ::nameDependeeElement() see the registration.
   */
  private function addDependeeNameCallback(array &$form): void {
    $callbacks = $form['#after_build'] ?? [];
    $position = array_search('conditional_fields_form_after_build', $callbacks, TRUE);
    $callback = [self::class, 'nameDependeeElement'];

    if ($position === FALSE) {
      $callbacks[] = $callback;
    }
    else {
      array_splice($callbacks, $position, 0, [$callback]);
    }

    $form['#after_build'] = $callbacks;
  }

  /**
   * Gives the registered dependee element a #name.
   *
   * ConditionalFieldsFormHelper::getState() only builds a 'value' condition
   * when the dependee element carries a #name. Every element in the media
   * library widget is a container, none of which gets one, so a dependency
   * against this field would otherwise produce no states at all and the
   * transition effect would simply always be visible.
   *
   * The element is looked up from the registration rather than guessed at,
   * because which part of the widget subtree registers itself depends on the
   * widget's internal structure. The name is inert for rendering — containers
   * have no name attribute — and is read only to resolve the states handler.
   *
   * @see \Drupal\conditional_fields\ConditionalFieldsElementAlterHelper::afterBuild()
   * @see \Drupal\conditional_fields\ConditionalFieldsFormHelper::getState()
   */
  public static function nameDependeeElement(array $form, FormStateInterface $form_state): array {
    $parents = $form['#conditional_fields'][self::COUNT_SOURCE_FIELD]['parents'] ?? NULL;

    if (!is_array($parents)) {
      return $form;
    }

    $element = &NestedArray::getValue($form, $parents, $exists);

    if ($exists && is_array($element) && !isset($element['#name'])) {
      $element['#name'] = self::COUNT_SOURCE_FIELD;
    }

    return $form;
  }

}
