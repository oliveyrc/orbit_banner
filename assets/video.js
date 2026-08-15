/**
 * @file
 * Play/pause control for the Orbit Banner video.
 */

((Drupal, once) => {
  'use strict';

  /**
   * Shows or hides an icon.
   *
   * The hidden attribute is set through the DOM rather than the .hidden
   * property: that property is defined on HTMLElement, and these icons are
   * SVG elements, so assigning to it would silently create a plain JavaScript
   * property and leave the markup — and therefore the rendering — untouched.
   *
   * @param {SVGElement} icon
   *   The icon to toggle.
   * @param {boolean} hidden
   *   Whether the icon should be hidden.
   */
  const setHidden = (icon, hidden) => {
    if (hidden) {
      icon.setAttribute('hidden', 'hidden');
    }
    else {
      icon.removeAttribute('hidden');
    }
  };

  /**
   * Reflects the media state on the toggle button.
   *
   * @param {HTMLButtonElement} toggle
   *   The play/pause button.
   * @param {boolean} isPlaying
   *   Whether the video is currently playing.
   */
  const syncToggle = (toggle, isPlaying) => {
    const iconPlay = toggle.querySelector('.icon-play');
    const iconPause = toggle.querySelector('.icon-pause');

    // The accessible name describes the action the button performs, so it is
    // the only state a screen reader needs. aria-pressed is deliberately not
    // used alongside it, as that would announce the state twice.
    toggle.setAttribute(
      'aria-label',
      isPlaying
        ? toggle.dataset.labelPause || Drupal.t('Pause video')
        : toggle.dataset.labelPlay || Drupal.t('Play video'),
    );

    if (iconPlay) {
      setHidden(iconPlay, isPlaying);
    }
    if (iconPause) {
      setHidden(iconPause, !isPlaying);
    }
  };

  Drupal.behaviors.orbitBannerVideo = {
    attach(context) {
      once('orbit-banner-video', '.orbit-banner__video-toggle', context).forEach(
        (toggle) => {
          const video = document.getElementById(
            toggle.getAttribute('aria-controls'),
          );

          if (!video) {
            return;
          }

          toggle.addEventListener('click', () => {
            if (video.paused) {
              // Autoplay policies can reject play(); leave the button showing
              // "play" when that happens instead of lying about the state.
              const played = video.play();

              if (played && typeof played.catch === 'function') {
                played.catch(() => syncToggle(toggle, false));
              }
            }
            else {
              video.pause();
            }
          });

          // The media element is the source of truth for the button state.
          video.addEventListener('play', () => syncToggle(toggle, true));
          video.addEventListener('pause', () => syncToggle(toggle, false));

          syncToggle(toggle, !video.paused);
        },
      );

      // Autoplay is started here rather than with the autoplay attribute so it
      // can be skipped for visitors who prefer reduced motion.
      once('orbit-banner-video-autoplay', '.orbit-banner__video', context).forEach(
        (video) => {
          if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
          }

          video.muted = true;
          const played = video.play();

          if (played && typeof played.catch === 'function') {
            played.catch(() => {
              // Blocked by the browser; the play button remains available.
            });
          }
        },
      );
    },
  };
})(Drupal, once);
