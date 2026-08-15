/**
 * @file
 * Swiper slideshow for banners that hold more than one image.
 */

((Drupal, once) => {
  'use strict';

  const AUTOPLAY_DELAY = 6000;

  /**
   * Reflects the autoplay state on the play/pause button.
   *
   * @param {HTMLButtonElement} toggle
   *   The play/pause button.
   * @param {boolean} isPlaying
   *   Whether autoplay is currently running.
   */
  const syncToggle = (toggle, isPlaying) => {
    const iconPlay = toggle.querySelector('.icon-play');
    const iconPause = toggle.querySelector('.icon-pause');

    toggle.setAttribute(
      'aria-label',
      isPlaying
        ? toggle.dataset.labelPause || Drupal.t('Pause slideshow')
        : toggle.dataset.labelPlay || Drupal.t('Play slideshow'),
    );

    if (iconPlay) {
      iconPlay.hidden = isPlaying;
    }
    if (iconPause) {
      iconPause.hidden = !isPlaying;
    }
  };

  Drupal.behaviors.orbitBannerSlider = {
    attach(context) {
      if (typeof window.Swiper === 'undefined') {
        return;
      }

      once('orbit-banner-slider', '[data-orbit-banner-slider]', context).forEach(
        (element) => {
          const banner = element.closest('.orbit-banner');
          const controls = banner
            ? banner.querySelector('.orbit-banner__slider-controls')
            : null;
          const toggle = controls
            ? controls.querySelector('.orbit-banner__autoplay-toggle')
            : null;
          const reduceMotion = window.matchMedia(
            '(prefers-reduced-motion: reduce)',
          ).matches;
          const fade = element.dataset.effect === 'fade';

          const options = {
            effect: fade ? 'fade' : 'slide',
            loop: true,
            speed: fade ? 800 : 600,
            slidesPerView: 1,
            watchSlidesProgress: true,
            a11y: {
              enabled: true,
              prevSlideMessage: Drupal.t('Previous image'),
              nextSlideMessage: Drupal.t('Next image'),
              paginationBulletMessage: Drupal.t('Go to image {{index}}'),
              slideLabelMessage: Drupal.t('{{index}} of {{slidesLength}}'),
            },
            keyboard: {
              enabled: true,
              onlyInViewport: true,
            },
          };

          if (fade) {
            options.fadeEffect = { crossFade: true };
          }

          if (controls) {
            options.navigation = {
              prevEl: controls.querySelector('.orbit-banner__nav--prev'),
              nextEl: controls.querySelector('.orbit-banner__nav--next'),
            };
            options.pagination = {
              el: controls.querySelector('.orbit-banner__pagination'),
              clickable: true,
            };
          }

          // WCAG 2.2.2: movement must not start automatically for visitors who
          // have asked for reduced motion, and must always be pausable.
          if (!reduceMotion) {
            options.autoplay = {
              delay: AUTOPLAY_DELAY,
              disableOnInteraction: false,
              pauseOnMouseEnter: true,
            };
          }

          const swiper = new window.Swiper(element, options);

          if (!toggle) {
            return;
          }

          if (reduceMotion) {
            // There is no autoplay to pause, so the control has nothing to do.
            toggle.hidden = true;
            return;
          }

          // Tracked here rather than read back from Swiper: pauseOnMouseEnter
          // also flips autoplay.paused, and a hover must not turn the button
          // into a "play" control while the slideshow is still running.
          let playing = true;
          syncToggle(toggle, playing);

          toggle.addEventListener('click', () => {
            playing = !playing;

            if (playing) {
              swiper.autoplay.start();
            }
            else {
              swiper.autoplay.stop();
            }

            syncToggle(toggle, playing);
          });
        },
      );
    },
  };
})(Drupal, once);
