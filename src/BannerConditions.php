<?php

declare(strict_types=1);

namespace Drupal\orbit_banner;

use Drupal\conditional_fields\ConditionalFieldsInterface;
use Drupal\orbit_banner\Hook\OrbitBannerFormHooks;

/**
 * Defines the Conditional Fields dependencies this module installs.
 */
final class BannerConditions {

  /**
   * Fixed UUID for the effect dependency, so re-running install is idempotent.
   */
  public const EFFECT_DEPENDENCY_UUID = 'f0b8a3d2-6c41-4a7e-9d15-3e8b2c704a61';

  /**
   * The jQuery selector for the hidden banner image count input.
   */
  public const IMAGE_COUNT_SELECTOR = '[name="' . OrbitBannerFormHooks::COUNT_ELEMENT_NAME . '"]';

  /**
   * Matches an image count of two or more.
   *
   * Conditional Fields wraps this in delimiters and hands it to the States API
   * as a regex comparison, so it is anchored to avoid matching "1" inside a
   * longer number.
   */
  public const MULTIPLE_IMAGES_REGEX = '^([2-9]|[1-9][0-9]+)$';

  /**
   * Returns the Conditional Fields third party settings for the effect field.
   *
   * Shows 'Transition effect' only once at least two banner images have been
   * selected, since the transition has nothing to animate between otherwise.
   *
   * @return array[]
   *   Dependencies keyed by UUID, as stored on the form display component.
   */
  public static function effectFieldSettings(): array {
    return [
      self::EFFECT_DEPENDENCY_UUID => [
        'uuid' => self::EFFECT_DEPENDENCY_UUID,
        'entity_type' => 'node',
        'bundle' => 'basic_page',
        'dependee' => OrbitBannerFormHooks::COUNT_SOURCE_FIELD,
        'settings' => [
          'state' => 'visible',
          'condition' => 'value',
          'grouping' => 'AND',
          'values_set' => ConditionalFieldsInterface::CONDITIONAL_FIELDS_DEPENDENCY_VALUES_REGEX,
          'regex' => self::MULTIPLE_IMAGES_REGEX,
          'value' => '',
          'values' => [],
          'value_form' => [],
          'effect' => 'show',
          'effect_options' => [],
          // The media library widget exposes no count, so the dependency is
          // pointed at the hidden input added by OrbitBannerFormHooks.
          'selector' => self::IMAGE_COUNT_SELECTOR,
          'reset' => FALSE,
        ],
      ],
    ];
  }

}
