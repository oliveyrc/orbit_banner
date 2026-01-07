<?php

declare(strict_types=1);

namespace Drupal\orbit_banner\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the Orbit Banner module.
 */
class OrbitBannerHooks {
  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.orbit_banner':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('This module provides banner functionality, in the form of a block and creates the required fields on a content type.') . '</p>';

        return $output;
    }
    return NULL;
  }

}
