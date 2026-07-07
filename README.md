# Orbit Banner

Orbit Banner is a custom Drupal 11 module that adds banner functionality to the Basic page content type (`page`).

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
- `field_orbit_banner_image` (media reference to `banner_image`)
- `field_orbit_banner_video` (media reference to `banner_video`)
- `field_orbit_banner_size` (list: `small`, `medium`, `large`)
- `field_orbit_banner_colour` (color field)

### Form display behavior
During module install, `orbit_banner_install()` updates `node.page.default` form display to:
- Place all banner fields in `content` region with expected weights.
- Create `group_tabs` (`tabs`, vertical) as a top-level field group.
- Create `group_banner` (`tab`) under `group_tabs`.
- Assign all banner fields to `group_banner`.

## Requirements

- Drupal `^11`
- Contrib modules declared in module info:
  - `color_field`
  - `markup`
  - `media_library_edit`
  - `field_group`

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

## Verify

1. Go to **Structure -> Content types -> Basic page -> Manage form display**.
2. Confirm there is a **Tabs** group containing a **Banner** tab.
3. Confirm the banner fields are inside the **Banner** tab.

## Important note for existing installs

The form-display field-group setup runs in the module install hook.
If the module is already installed, reinstalling the module is required for install-hook logic to run again.

## Maintainer

- Ricahrd Olivey (oliveyrc)
- Source: https://github.com/oliveyrc/orbit_banner
- Issues: https://github.com/oliveyrc/orbit_banner/issues
