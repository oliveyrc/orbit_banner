# Orbit Banner

Orbit Banner is a custom Drupal 11 module that adds banner functionality to the Basic page content type (`basic_page`).

On install, the module provides:
- Banner media types for images and videos.
- Banner fields on Basic page.
- Form display setup for those fields.
- A `Tabs` field group with a `Banner` tab on the Basic page edit form.

## Features

### Media types
- `banner_image` (source: image)
- `banner_video` (source: video file)

### Basic page fields
- `field_orbit_banner_help` (markup help text)
- `field_orbit_banner_title` (string)
- `field_orbit_banner_text` (formatted text)
- `field_orbit_banner_image` (media reference to `banner_image`, unlimited)
- `field_orbit_banner_effect` (list: `slide` "Swipe", `fade`)
- `field_orbit_banner_video` (media reference to `banner_video`)
- `field_orbit_banner_size` (list: `small`, `medium`, `large`)
- `field_orbit_banner_parallax` (boolean)
- `field_orbit_banner_colour` (color field)

### What the block renders

The `page_banner` block picks one of three templates:

| Condition | Template | Libraries attached |
| --- | --- | --- |
| A video is referenced | `orbit-page-banner-video.html.twig` | `orbit_banner/video` |
| More than one image | `orbit-page-banner-slider.html.twig` | `orbit_banner/slider` (pulls in Swiper) |
| One image, or none | `orbit-page-banner.html.twig` | none |

A video always wins over images. The first image is used as the video's poster frame.

Swiper is only requested when a banner actually holds more than one image, so
single-image and video banners ship no slideshow JavaScript or CSS.

When no image is set on the node, the block falls back to the field's default
value, then to the `banner` site settings group, then to the `defaults` group.

### Transition effect

`field_orbit_banner_effect` chooses between Swiper's `slide` (labelled "Swipe")
and `fade` transitions. It has no effect unless the banner holds more than one
image. The default is `slide`.

### Parallax

`field_orbit_banner_parallax` moves the media layer at a slower rate than the
page scroll. It works for all three banner types. The media layer is over-sized
by the travel distance so the transform can never expose an edge, and the extra
size is only applied once the behaviour is running — without JavaScript the
banner is unchanged.

### Accessibility

- Video banners have a play/pause button with `aria-controls` pointing at the
  video, and an accessible name that switches between "Play video" and
  "Pause video" as the media state changes. The state is driven from the
  media element's own `play` and `pause` events, so it stays correct when
  playback is blocked by an autoplay policy.
- Slideshows use Swiper's a11y module, real `<button>` elements for previous
  and next, and a play/pause button for the autoplay, as required by
  WCAG 2.2.2 (Pause, Stop, Hide).
- Video autoplay, slideshow autoplay, and parallax are all skipped when the
  visitor has `prefers-reduced-motion: reduce` set. Video autoplay is started
  from JavaScript rather than the `autoplay` attribute so this can be honoured.

### Colour

`field_orbit_banner_colour` is exposed to the template as the
`--orbit-banner-colour` custom property together with a `has-colour` class, and
tints the gradient overlay that sits above the media.

## Requirements

- Drupal `^11`
- Contrib modules declared in module info:
  - `color_field`
  - `markup`
  - `media_library_edit`
  - `field_group`
  - `orbit_media`
  - `site_settings`
- [Swiper](https://swiperjs.com) `^11.2` at `web/libraries/swiper`, but only if
  you want multi-image banners.

## Installing Swiper

The module expects `swiper-bundle.min.js` and `swiper-bundle.min.css` at
`web/libraries/swiper/`. The status report warns when they are missing, and
multi-image banners fall back to showing the first image only.

Composer ignores `repositories` declared outside the root `composer.json`, so
add the package to the project root:

```bash
composer config repositories.swiper '{"type":"package","package":{"name":"nolimits4web/swiper","version":"11.2.10","type":"drupal-library","license":"MIT","dist":{"type":"tar","url":"https://registry.npmjs.org/swiper/-/swiper-11.2.10.tgz"}}}'
```

```bash
composer require nolimits4web/swiper
```

## Installation

1. Ensure required dependencies are available in the site.
2. Enable the module:

```bash
ddev drush en orbit_banner -y
```

3. Rebuild caches:

```bash
ddev drush cr
```

## Updating an existing install

`orbit_banner_update_11001()` switches `field_orbit_banner_image` to unlimited
cardinality, creates `field_orbit_banner_effect` and
`field_orbit_banner_parallax`, and re-applies the Banner tab layout:

```bash
ddev drush updatedb -y
```

## Verify

1. Go to **Structure -> Content types -> Basic page -> Manage form display**.
2. Confirm there is a **Tabs** group containing a **Banner** tab.
3. Confirm the banner fields are inside the **Banner** tab.
4. Add two or more images to a Basic page and confirm the banner becomes a
   slideshow.

## Maintainer

- Ricahrd Olivey (oliveyrc)
- Source: https://github.com/oliveyrc/orbit_banner
- Issues: https://github.com/oliveyrc/orbit_banner/issues
