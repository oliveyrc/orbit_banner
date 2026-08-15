<?php

declare(strict_types=1);

namespace Drupal\orbit_banner\Hook;

use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Requirements checks for the Orbit Banner module.
 */
class OrbitBannerRequirements {

  use StringTranslationTrait;

  /**
   * The Swiper file the slider library depends on, relative to the web root.
   */
  private const SWIPER_PATH = 'libraries/swiper/swiper-bundle.min.js';

  public function __construct(
    #[Autowire(param: 'app.root')]
    private readonly string $appRoot,
  ) {}

  /**
   * Implements hook_runtime_requirements().
   *
   * @return array[]
   *   The runtime requirements reported by this module.
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $found = file_exists($this->appRoot . '/' . self::SWIPER_PATH);

    $requirement = [
      'title' => $this->t('Orbit Banner: Swiper'),
      'value' => $found ? $this->t('Installed') : $this->t('Not found'),
      'severity' => $found ? RequirementSeverity::OK : RequirementSeverity::Warning,
    ];

    if (!$found) {
      $requirement['description'] = $this->t('Swiper was not found at <code>@path</code>. Banners with more than one image will show only the first image until it is installed.', [
        '@path' => self::SWIPER_PATH,
      ]);
    }

    return ['orbit_banner_swiper' => $requirement];
  }

}
