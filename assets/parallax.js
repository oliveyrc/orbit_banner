/**
 * @file
 * Parallax scrolling for the Orbit Banner media layer.
 */

((Drupal, once) => {
  'use strict';

  // Fraction of the banner height the media travels across a full pass through
  // the viewport. The media layer is over-sized by the same fraction in CSS so
  // no gap can ever appear at either edge.
  const TRAVEL = 0.5;

  Drupal.behaviors.orbitBannerParallax = {
    attach(context) {
      const banners = once(
        'orbit-banner-parallax',
        '.orbit-banner.has-parallax',
        context,
      );

      if (!banners.length) {
        return;
      }

      // Honour the visitor's motion preference; the banner stays static.
      if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
      }

      const items = banners
        .map((banner) => ({
          banner,
          layer: banner.querySelector('[data-orbit-banner-media]'),
        }))
        .filter((item) => item.layer !== null);

      if (!items.length) {
        return;
      }

      items.forEach((item) => item.layer.classList.add('is-parallax-active'));

      let ticking = false;

      const update = () => {
        ticking = false;
        const viewport = window.innerHeight;

        items.forEach(({ banner, layer }) => {
          // Measured on the banner, not the layer: the layer carries the
          // transform, so measuring it would feed its own offset back in.
          const rect = banner.getBoundingClientRect();

          // Nothing off screen needs repainting.
          if (rect.bottom < 0 || rect.top > viewport) {
            return;
          }

          // -1 when the banner has just left the top of the viewport, 0 when
          // it is centred, +1 when it is about to enter from the bottom.
          const centre = rect.top + rect.height / 2 - viewport / 2;
          const progress = centre / ((viewport + rect.height) / 2);
          const offset = progress * rect.height * (TRAVEL / 2);

          layer.style.transform = `translate3d(0, ${offset.toFixed(2)}px, 0)`;
        });
      };

      const onScroll = () => {
        if (ticking) {
          return;
        }

        ticking = true;
        window.requestAnimationFrame(update);
      };

      window.addEventListener('scroll', onScroll, { passive: true });
      window.addEventListener('resize', onScroll, { passive: true });
      update();
    },
  };
})(Drupal, once);
