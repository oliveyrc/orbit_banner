<?php

declare(strict_types=1);

namespace Drupal\orbit_banner\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for the Orbit Banner module.
 */
class OrbitBannerHooks
{
  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string
  {
    switch ($route_name) {
      case 'help.page.orbit_banner':
        $output = '';
        $output .= '<h3>' . t('About') . '</h3>';
        $output .= '<p>' . t('This is an example module.') . '</p>';
        return $output;
    }
    return null;
  }
}
