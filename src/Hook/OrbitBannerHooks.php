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
    if ($route_name !== 'help.page.orbit_banner') {
      return NULL;
    }

    $output = '<h3>' . $this->t('About') . '</h3>';
    $output .= '<p>' . $this->t('This module provides banner functionality, in the form of a block and creates the required fields on a content type.') . '</p>';
    $output .= '<h3>' . $this->t('Media') . '</h3>';
    $output .= '<p>' . $this->t('A banner shows a video when one is added, otherwise it shows the banner images. Adding more than one image turns the banner into a Swiper slideshow, and the first image is used as the poster frame for a video.') . '</p>';

    return $output;
  }

  /**
   * Implements hook_theme().
   *
   * @return array[]
   *   The theme definitions provided by this module.
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    $common = [
      'title' => NULL,
      'description' => NULL,
      'link' => NULL,
      'colour' => NULL,
      'size' => NULL,
      'parallax' => FALSE,
      'no_h1' => NULL,
    ];

    return [
      'orbit_page_banner' => [
        'variables' => $common + [
          'image' => NULL,
          'video' => NULL,
          'poster' => NULL,
        ],
      ],
      'orbit_page_banner_video' => [
        'variables' => $common + [
          'video_url' => NULL,
          'video_id' => NULL,
          'poster' => NULL,
          'aria_label' => NULL,
        ],
      ],
      'orbit_page_banner_slider' => [
        'variables' => $common + [
          'images' => [],
          'effect' => 'slide',
          'slider_id' => NULL,
        ],
      ],
    ];
  }

}
