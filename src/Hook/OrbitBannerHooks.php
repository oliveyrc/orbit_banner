<?php

declare(strict_types=1);

namespace Drupal\orbit_banner\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for the Orbit Banner module.
 */
class OrbitBannerHooks
{
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
        return null;
    }

    /**
     * Implements hook_theme().
     *
     * @return array[]
     */
    #[Hook('theme')]
    public function theme($existing, $type, $theme, $path): array {
      return [
        'orbit_page_banner' => [
          'variables' => [
            'title' => NULL,
            'description' => NULL,
            'link' => NULL,
            'image' => NULL,
            'video' => NULL,
            'poster' => NULL,
            'colour' => NULL,
            'size' => NULL,
            'no_h1' => NULL,
            'animate' => NULL,
            'animate_url' => NULL,
          ],
        ],
        'orbit_page_banner_video' => [
          'variables' => [
            'title' => NULL,
            'description' => NULL,
            'link' => NULL,
            'colour' => NULL,
            'image' => NULL,
            'video' => NULL,
            'poster' => NULL,
            'video_url' => NULL,
            'size' => NULL,
            'aria_label' => NULL,
            'no_h1' => NULL,
          ],
        ],
      ];
    }
}
