<?php

declare(strict_types=1);

namespace Drupal\orbit_banner\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;

/**
 * Provides a 'PageBanner' block.
 */
#[Block(
    id: 'page_banner',
    admin_label: new TranslatableMarkup('Page banner'),
)]
class PageBanner extends BlockBase
{

    /**
     * {@inheritdoc}
     */
    public function build()
    {
        $link = $description = $alt_text = $video = $poster = $picture = null;
        $size = '';
        $image_uri = false;
        $show_video = false;
        $desktop_url = false;
        $node = \Drupal::routeMatch()->getParameter('node');
        $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();

        if (is_string($node)) {
            $node = Node::load($node);
        }

        $request = \Drupal::request();
        $route_match = \Drupal::routeMatch();
        $title = \Drupal::service('title_resolver')
            ->getTitle($request, $route_match->getRouteObject());

        if ($node) {

            $translated_node = $node->getTranslation($lang_code);
            if ($translated_node) {
                $title = $translated_node->getTitle();
            }

            if (($node->hasField('field_banner_description')) && (!$node->get('field_banner_description')->isEmpty())) {
                $description = $node->get('field_banner_description')->getString();
            }

            if ($node->hasField('field_orbit_banner_size')) {
                if ((!$node->get('field_orbit_banner_size')->isEmpty()) && ($node->get('field_orbit_banner_size')->first()->getValue()['value'] !== 'full')) {
                    $size = $node->get('field_orbit_banner_size')->first()->getValue()['value'];
                }
            }

            if (($node->hasField('field_banner_link')) && (!$node->get('field_banner_link')->isEmpty())) {
                $link = $node->get('field_banner_link')->first()->view();
            }

            // Does this node have the banner image and its set.
            if ($node->hasField('field_orbit_banner_image') && (!($node->get('field_orbit_banner_image')->isEmpty()))) {
                $media = Media::load($node->get('field_orbit_banner_image')->first()->getValue()['target_id']);
                if ($media) {
                    $file = File::load($media->getSource()->getSourceFieldValue($media));
                    if ($file) {
                        $image_uri = $file->getFileUri();
                        $alt_text = $media->get('field_media_image')->first()->getValue()['alt'];
                    }
                }
            }
            // See if there a default image for the field.
            elseif ($node->hasField('field_orbit_banner_image')) {
                $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', $node->getType());
                $default_value = $definitions['field_orbit_banner_image']->getDefaultValueLiteral();
                // Use the default field image.
                if (!empty($default_value)) {
                    $media_id = \Drupal::entityTypeManager()->getStorage('media')->loadByProperties(['uuid' => $default_value[0]['target_uuid']]);
                    $media_id = current($media_id)->id();
                    $media = Media::load($media_id);
                    $file = File::load($media->getSource()->getSourceFieldValue($media));
                    $image_uri = $file->getFileUri();
                    $alt_text = $media->get('field_media_image')->first()->getValue()['alt'];
                }
                else {
                    // Use the site settings banner image.
                    $site_settings = \Drupal::service('plugin.manager.site_settings_loader')->getActiveLoaderPlugin();
                    $settings = current($site_settings->loadByGroup('defaults'));
                    if ($settings && !$settings->get('field_image')->isEmpty()) {
                        $banner = $settings->get('field_image')->first()->getValue();
                        $media = Media::load($banner['target_id']);
                        if ($media) {
                              $file = File::load($media->getSource()->getSourceFieldValue($media));
                            if ($file) {
                                $image_uri = $file->getFileUri();
                                $alt_text = $media->get('field_media_image')->first()->getValue()['alt'];
                            }
                        }
                    }
                }
            }

            if ($node->hasField('field_orbit_banner_video') && (!$node->get('field_orbit_banner_video')->isEmpty())) {
                $show_video = true;
                $media = Media::load($node->get('field_orbit_banner_video')->first()->getValue()['target_id']);
                $file = File::load($media->getSource()->getSourceFieldValue($media));
                $video_url = $file->getFileUri();
                $video_url = \Drupal::service('file_url_generator')->generateAbsoluteString($video_url);
            }
        }

        // Get the default banner image.
        if (!$image_uri) {
            [$image_uri, $alt_text] = $this->getDefaultBanner();
        }

        if ($image_uri) {

            // Generate a desktop image URL for the video poster.
            $desktop_url = ImageStyle::load('banner_image_desktop')->buildUrl($image_uri);
        }
        if ($size != 'min') {
            if ($show_video) {
                return [
                '#theme' => 'orbit_page_banner_video',
                '#title' => $title,
                '#description' => $description,
                '#size' => $size,
                '#image' => $picture,
                '#link' => $link,
                '#poster' => $desktop_url,
                '#video_url' => $video_url,
                ];

            }
            else {
                $picture = false;
                $image = false;
                if ($image_uri) {
                    $responsive_style = \Drupal::entityTypeManager()
                        ->getStorage('responsive_image_style')
                        ->load('banner_image');

                    if ($responsive_style) {
                        $image = [
                          '#theme' => 'responsive_image',
                          '#responsive_image_style_id' => 'banner_image',
                          '#uri' => $image_uri,
                          '#alt' => $alt_text,
                          '#attributes' => [
                        'loading' => 'eager',
                          ],
                        ];
                    }
                    else {
                        $image = [
                        '#theme' => 'image_style',
                        '#style_name' => 'banner_image_desktop',
                        '#uri' => $image_uri,
                        '#alt' => $alt_text,
                        '#attributes' => [
                        'loading' => 'eager',
                        ],
                        ];
                    }
                }
                // Pass the data to the template.
                return [
                '#theme' => 'orbit_page_banner',
                '#title' => $title,
                '#description' => $description,
                '#size' => $size,
                '#image' => $image,
                '#link' => $link,
                '#video' => null,
                '#poster' => $poster,
                ];

            }
        }

    }

    /**
     * {@inheritdoc}
     */
    public function getCacheTags()
    {
        // With this when your node change your block will rebuild.
        $node = \Drupal::routeMatch()->getParameter('node');
        if (is_string($node)) {
            $node = Node::load($node);
        }
        if (!empty($node)) {
            // If there is node add its cachetag.
            return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
        }
        else {
            return Cache::mergeTags(
                parent::getCacheTags(), [
                'config:oyster_news.settings',
                ]
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheContexts()
    {
        // If you depends on \Drupal::routeMatch()
        // you must set context of this block with 'route' context tag.
        // Every new route this block will rebuild.
        return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
    }

    /**
     * Return an array of the default banner image and alt text.
     */
    private function getDefaultBanner(): array
    {
        $image_uri = '';
        $alt_text = 'Missing Image';

        $site_settings = \Drupal::service('plugin.manager.site_settings_loader')->getActiveLoaderPlugin();
        $settings = current($site_settings->loadByGroup('banner'));
        if ($settings && !$settings->get('field_image')->isEmpty()) {
            $banner = $settings->get('field_image')->first()->getValue();
            $media = Media::load($banner['target_id']);
            if ($media) {
                $file = File::load($media->getSource()->getSourceFieldValue($media));
                if ($file) {
                    $image_uri = $file->getFileUri();
                    $alt_text = $media->get('field_media_image')
                        ->first()
                        ->getValue()['alt'];
                }
            }
        }
        return [$image_uri, $alt_text];
    }

}
