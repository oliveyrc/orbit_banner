<?php

declare(strict_types=1);

namespace Drupal\orbit_banner\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\media\MediaInterface;
use Drupal\node\NodeInterface;
use Drupal\site_settings\SiteSettingsLoaderPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a 'Page banner' block.
 */
#[Block(
  id: 'page_banner',
  admin_label: new TranslatableMarkup('Page banner'),
)]
final class PageBanner extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The responsive image style used by the banner.
   */
  private const RESPONSIVE_IMAGE_STYLE = 'banner_image';

  /**
   * The image style used for the single fallback image and the video poster.
   */
  private const FALLBACK_IMAGE_STYLE = 'banner_image_desktop';

  /**
   * Collects cache metadata for everything the banner is built from.
   */
  private CacheableMetadata $cacheability;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly EntityFieldManagerInterface $entityFieldManager,
    private readonly LanguageManagerInterface $languageManager,
    private readonly RouteMatchInterface $routeMatch,
    private readonly RequestStack $requestStack,
    private readonly TitleResolverInterface $titleResolver,
    private readonly FileUrlGeneratorInterface $fileUrlGenerator,
    private readonly SiteSettingsLoaderPluginManager $siteSettingsLoader,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->cacheability = new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('language_manager'),
      $container->get('current_route_match'),
      $container->get('request_stack'),
      $container->get('title_resolver'),
      $container->get('file_url_generator'),
      $container->get('plugin.manager.site_settings_loader'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = $this->getCurrentNode();

    $title = $this->getTitle($node);
    $description = NULL;
    $link = NULL;
    $size = '';
    $colour = NULL;
    $parallax = FALSE;
    $effect = 'slide';

    if ($node instanceof NodeInterface) {
      $this->cacheability->addCacheableDependency($node);

      $title = $this->getFieldString($node, 'field_orbit_banner_title') ?? $title;
      $description = $this->getDescription($node);
      $size = $this->getFieldString($node, 'field_orbit_banner_size') ?? '';
      $colour = $this->getBannerColour($node);
      $parallax = $this->getFieldBoolean($node, 'field_orbit_banner_parallax');
      $effect = $this->getFieldString($node, 'field_orbit_banner_effect') ?? 'slide';

      if ($node->hasField('field_banner_link') && !$node->get('field_banner_link')->isEmpty()) {
        $link = $node->get('field_banner_link')->first()->view();
      }
    }

    // The legacy "full" size means the default height, which has no modifier
    // class of its own.
    if ($size === 'full') {
      $size = '';
    }

    $images = $this->getImages($node);
    $video_url = $this->getVideoUrl($node);

    // The first image doubles as the video poster frame.
    $poster = $images ? $this->buildPosterUrl($images[0]['uri']) : NULL;

    $build = [
      '#title' => $title,
      '#description' => $description,
      '#link' => $link,
      '#size' => $size,
      '#colour' => $colour,
      '#parallax' => $parallax,
      '#attached' => ['library' => []],
    ];

    if ($video_url !== NULL) {
      $build += [
        '#theme' => 'orbit_page_banner_video',
        '#video_url' => $video_url,
        '#video_id' => Html::getUniqueId('orbit-banner-video'),
        '#poster' => $poster,
      ];
      $build['#attached']['library'][] = 'orbit_banner/video';
    }
    elseif (count($images) > 1) {
      // Only a multi-image banner pulls in Swiper.
      $build += [
        '#theme' => 'orbit_page_banner_slider',
        '#images' => array_map(fn (array $image): array => $this->buildImage($image), $images),
        '#effect' => $effect === 'fade' ? 'fade' : 'slide',
        '#slider_id' => Html::getUniqueId('orbit-banner-slider'),
      ];
      $build['#attached']['library'][] = 'orbit_banner/slider';
    }
    elseif ($images) {
      $build += [
        '#theme' => 'orbit_page_banner',
        '#image' => $this->buildImage($images[0]),
        '#poster' => NULL,
        '#video' => NULL,
      ];
    }
    else {
      $build += [
        '#theme' => 'orbit_page_banner',
        '#image' => NULL,
        '#poster' => NULL,
        '#video' => NULL,
      ];
    }

    if ($parallax) {
      $build['#attached']['library'][] = 'orbit_banner/parallax';
    }

    $this->cacheability->applyTo($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $node = $this->getCurrentNode();

    if ($node instanceof NodeInterface) {
      return Cache::mergeTags(parent::getCacheTags(), $node->getCacheTags());
    }

    // Without a node the banner comes from site settings only.
    return Cache::mergeTags(parent::getCacheTags(), ['site_setting_entity_list']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    // The banner is derived from the current route, so it must vary by it.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route', 'languages:language_content']);
  }

  /**
   * Returns the node for the current route, if there is one.
   */
  private function getCurrentNode(): ?NodeInterface {
    $node = $this->routeMatch->getParameter('node');

    if (is_scalar($node)) {
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }

    if (!$node instanceof NodeInterface) {
      return NULL;
    }

    $langcode = $this->languageManager->getCurrentLanguage()->getId();

    return $node->hasTranslation($langcode) ? $node->getTranslation($langcode) : $node;
  }

  /**
   * Returns the page title, falling back to the resolved route title.
   *
   * @return array|string|\Drupal\Component\Render\MarkupInterface|null
   *   The title, in whichever form the title resolver produced it.
   */
  private function getTitle(?NodeInterface $node): mixed {
    if ($node instanceof NodeInterface) {
      return $node->label();
    }

    $request = $this->requestStack->getCurrentRequest();
    $route = $this->routeMatch->getRouteObject();

    if ($request === NULL || $route === NULL) {
      return NULL;
    }

    return $this->titleResolver->getTitle($request, $route);
  }

  /**
   * Returns the banner description.
   *
   * The module's own field is formatted text, so it is rendered through its
   * text format rather than passed to the template as a raw string.
   *
   * @return array|string|null
   *   A render array, a plain string for the legacy field, or NULL.
   */
  private function getDescription(NodeInterface $node): array|string|null {
    if ($node->hasField('field_orbit_banner_text') && !$node->get('field_orbit_banner_text')->isEmpty()) {
      return $node->get('field_orbit_banner_text')->view(['label' => 'hidden']);
    }

    return $this->getFieldString($node, 'field_banner_description');
  }

  /**
   * Returns the trimmed string value of a field, or NULL when it is empty.
   */
  private function getFieldString(NodeInterface $node, string $field_name): ?string {
    if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
      return NULL;
    }

    $value = trim((string) $node->get($field_name)->first()->getValue()['value']);

    return $value === '' ? NULL : $value;
  }

  /**
   * Returns the boolean value of a field.
   */
  private function getFieldBoolean(NodeInterface $node, string $field_name): bool {
    if (!$node->hasField($field_name) || $node->get($field_name)->isEmpty()) {
      return FALSE;
    }

    return (bool) $node->get($field_name)->first()->getValue()['value'];
  }

  /**
   * Returns the banner colour as a CSS colour string, if one is set.
   */
  private function getBannerColour(NodeInterface $node): ?string {
    if (!$node->hasField('field_orbit_banner_colour') || $node->get('field_orbit_banner_colour')->isEmpty()) {
      return NULL;
    }

    $value = $node->get('field_orbit_banner_colour')->first()->getValue();

    // color_field stores the hex with or without the leading hash.
    $colour = ltrim(trim((string) ($value['color'] ?? '')), '#');

    if (!preg_match('/^(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $colour)) {
      return NULL;
    }

    return '#' . $colour;
  }

  /**
   * Returns every banner image, in field order.
   *
   * Falls back to the field default, then to the site settings banner.
   *
   * @return array<int, array{uri: string, alt: string}>
   *   Image descriptors keyed by 'uri' and 'alt'.
   */
  private function getImages(?NodeInterface $node): array {
    if ($node instanceof NodeInterface && $node->hasField('field_orbit_banner_image')) {
      $images = [];

      foreach ($node->get('field_orbit_banner_image')->referencedEntities() as $media) {
        if ($image = $this->extractImage($media)) {
          $images[] = $image;
        }
      }

      if ($images) {
        return $images;
      }

      if ($images = $this->getDefaultFieldImages($node)) {
        return $images;
      }
    }

    return $this->getSiteSettingsImages();
  }

  /**
   * Returns the images configured as the field's default value.
   *
   * @return array<int, array{uri: string, alt: string}>
   *   Image descriptors keyed by 'uri' and 'alt'.
   */
  private function getDefaultFieldImages(NodeInterface $node): array {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $node->bundle());

    if (!isset($definitions['field_orbit_banner_image'])) {
      return [];
    }

    $defaults = $definitions['field_orbit_banner_image']->getDefaultValueLiteral();

    if (!$defaults) {
      return [];
    }

    $uuids = array_filter(array_column($defaults, 'target_uuid'));

    if (!$uuids) {
      return [];
    }

    $media_storage = $this->entityTypeManager->getStorage('media');
    $images = [];

    // Preserve the configured order rather than the storage order.
    $by_uuid = [];
    foreach ($media_storage->loadByProperties(['uuid' => $uuids]) as $media) {
      $by_uuid[$media->uuid()] = $media;
    }

    foreach ($uuids as $uuid) {
      if (isset($by_uuid[$uuid]) && $image = $this->extractImage($by_uuid[$uuid])) {
        $images[] = $image;
      }
    }

    return $images;
  }

  /**
   * Returns the banner images held in site settings.
   *
   * @return array<int, array{uri: string, alt: string}>
   *   Image descriptors keyed by 'uri' and 'alt'.
   */
  private function getSiteSettingsImages(): array {
    $loader = $this->siteSettingsLoader->getActiveLoaderPlugin();

    foreach (['banner', 'defaults'] as $group) {
      $settings = current($loader->loadByGroup($group));

      if (!$settings || !$settings->hasField('field_image') || $settings->get('field_image')->isEmpty()) {
        continue;
      }

      $this->cacheability->addCacheableDependency($settings);
      $images = [];

      foreach ($settings->get('field_image')->referencedEntities() as $media) {
        if ($image = $this->extractImage($media)) {
          $images[] = $image;
        }
      }

      if ($images) {
        return $images;
      }
    }

    return [];
  }

  /**
   * Extracts the file URI and alt text from a banner image media entity.
   *
   * @return array{uri: string, alt: string}|null
   *   The image descriptor, or NULL when the media has no usable image.
   */
  private function extractImage(?MediaInterface $media): ?array {
    if ($media === NULL) {
      return NULL;
    }

    $this->cacheability->addCacheableDependency($media);

    $source_field = $media->getSource()->getConfiguration()['source_field'] ?? '';

    if ($source_field === '' || !$media->hasField($source_field) || $media->get($source_field)->isEmpty()) {
      return NULL;
    }

    $item = $media->get($source_field)->first();
    $file = $item->entity;

    if ($file === NULL) {
      return NULL;
    }

    $this->cacheability->addCacheableDependency($file);

    return [
      'uri' => $file->getFileUri(),
      'alt' => (string) ($item->getValue()['alt'] ?? ''),
    ];
  }

  /**
   * Returns the absolute URL of the banner video, if one is set.
   */
  private function getVideoUrl(?NodeInterface $node): ?string {
    if (!$node instanceof NodeInterface
      || !$node->hasField('field_orbit_banner_video')
      || $node->get('field_orbit_banner_video')->isEmpty()) {
      return NULL;
    }

    $media = $node->get('field_orbit_banner_video')->referencedEntities()[0] ?? NULL;

    if (!$media instanceof MediaInterface) {
      return NULL;
    }

    $this->cacheability->addCacheableDependency($media);

    $source_field = $media->getSource()->getConfiguration()['source_field'] ?? '';

    if ($source_field === '' || !$media->hasField($source_field) || $media->get($source_field)->isEmpty()) {
      return NULL;
    }

    $file = $media->get($source_field)->first()->entity;

    if ($file === NULL) {
      return NULL;
    }

    $this->cacheability->addCacheableDependency($file);

    return $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
  }

  /**
   * Builds the render array for one banner image.
   *
   * @param array{uri: string, alt: string} $image
   *   The image descriptor.
   */
  private function buildImage(array $image): array {
    $responsive_style = $this->entityTypeManager
      ->getStorage('responsive_image_style')
      ->load(self::RESPONSIVE_IMAGE_STYLE);

    if ($responsive_style) {
      $this->cacheability->addCacheableDependency($responsive_style);

      return [
        '#theme' => 'responsive_image',
        '#responsive_image_style_id' => self::RESPONSIVE_IMAGE_STYLE,
        '#uri' => $image['uri'],
        '#alt' => $image['alt'],
        '#attributes' => ['loading' => 'eager'],
      ];
    }

    return [
      '#theme' => 'image_style',
      '#style_name' => self::FALLBACK_IMAGE_STYLE,
      '#uri' => $image['uri'],
      '#alt' => $image['alt'],
      '#attributes' => ['loading' => 'eager'],
    ];
  }

  /**
   * Builds the video poster URL from the first banner image.
   */
  private function buildPosterUrl(string $uri): ?string {
    $style = $this->entityTypeManager
      ->getStorage('image_style')
      ->load(self::FALLBACK_IMAGE_STYLE);

    if ($style === NULL) {
      return $this->fileUrlGenerator->generateString($uri);
    }

    $this->cacheability->addCacheableDependency($style);

    return $style->buildUrl($uri);
  }

}
