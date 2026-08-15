/**
 * @file
 * Keeps the hidden banner image count in step with the media library widget.
 *
 * The Conditional Fields dependency on 'Transition effect' watches this input
 * rather than the widget, because the widget rebuilds over AJAX and exposes no
 * element that reflects how many items are selected.
 *
 * @see \Drupal\orbit_banner\Hook\OrbitBannerFormHooks
 * @see \Drupal\orbit_banner\BannerConditions
 */

((Drupal, once, $) => {
  'use strict';

  /**
   * Counts the media items currently selected in a widget.
   *
   * The media library widget renders one hidden target_id input per selected
   * item, so counting those is the same as counting the previews.
   *
   * @param {string} fieldName
   *   The machine name of the media field.
   *
   * @return {number}
   *   The number of selected items.
   */
  const countSelected = (fieldName) =>
    document.querySelectorAll(
      `input[name^="${fieldName}[selection]["][name$="[target_id]"]`,
    ).length;

  /**
   * Forces the States API to re-evaluate the dependency on this input.
   *
   * Conditional Fields installs its regex comparison from
   * Drupal.behaviors.statesModification, marked weight -10. Core's
   * attachBehaviors ignores weight and runs behaviours in script load order,
   * and states.js is a dependency of conditional_fields.js, so on first paint
   * Drupal.states runs before that comparison exists. Every regex condition
   * therefore evaluates false until something changes. Re-firing the state
   * with a different value and then the real one busts the cached value in
   * Drupal.states.Dependent.update() and re-runs the comparison, which by this
   * point is installed.
   *
   * This behaviour is registered after conditional_fields.js, so its attach
   * always runs last.
   *
   * @param {HTMLInputElement} input
   *   The hidden count input.
   *
   * @todo Remove once conditional_fields installs its comparisons at script
   *   scope rather than from a weighted behaviour.
   */
  const reevaluate = (input) => {
    if (!$ || !Drupal.states) {
      return;
    }

    $(input)
      .trigger({ type: 'state:value', value: null, oldValue: null })
      .trigger({ type: 'state:value', value: input.value, oldValue: null });
  };

  Drupal.behaviors.orbitBannerImageCount = {
    attach(context) {
      // Deliberately not guarded by once(): every media library add or remove
      // re-attaches behaviours, and that is exactly when the count changes.
      document
        .querySelectorAll('[data-orbit-banner-image-count]')
        .forEach((input) => {
          const count = String(
            countSelected(input.dataset.orbitBannerImageCount),
          );

          if (input.value !== count) {
            input.value = count;

            // The States API listens for change; without this the dependency
            // would only be evaluated on the next full page load.
            input.dispatchEvent(new Event('change', { bubbles: true }));
          }

          // The server already seeded the right count, so on first paint the
          // value never changes and the dispatch above never happens.
          once('orbit-banner-image-count', input, context).forEach(reevaluate);
        });
    },
  };
})(Drupal, once, window.jQuery);
