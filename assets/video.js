(function (Drupal, once) {
  'use strict';

  var getToggles = function (context) {
    if (typeof once === 'function') {
      return once('orbit-banner-video-toggle', '.orbit-banner .play-pause-toggle', context);
    }

    // Fallback when core/once is unavailable for any reason.
    var toggles = Array.prototype.slice.call(context.querySelectorAll('.orbit-banner .play-pause-toggle'));
    return toggles.filter(function (toggle) {
      if (toggle.dataset.orbitBannerBound === '1') {
        return false;
      }
      toggle.dataset.orbitBannerBound = '1';
      return true;
    });
  };

  Drupal.behaviors.orbitBannerVideo = {
    attach: function (context) {
      getToggles(context).forEach(function (toggle) {
          var banner = toggle.closest('.orbit-banner');
          var video = banner ? banner.querySelector('video') : null;
          var iconPlay = toggle.querySelector('.icon-play');
          var iconPause = toggle.querySelector('.icon-pause');

          if (!video || !iconPlay || !iconPause) {
            return;
          }

          var syncButtonState = function (isPlaying) {
            toggle.setAttribute('aria-pressed', isPlaying ? 'true' : 'false');
            toggle.setAttribute('aria-label', isPlaying ? 'Pause' : 'Play');
            iconPlay.hidden = isPlaying;
            iconPause.hidden = !isPlaying;
            iconPlay.style.display = isPlaying ? 'none' : 'inline-block';
            iconPause.style.display = isPlaying ? 'inline-block' : 'none';
          };

          // Sync initial icon and ARIA state with actual media state.
          syncButtonState(!video.paused);

          toggle.addEventListener('click', function (event) {
            event.preventDefault();
            if (video.paused) {
              var playPromise = video.play();
              if (playPromise && typeof playPromise.then === 'function') {
                playPromise
                  .then(function () {
                    syncButtonState(true);
                  })
                  .catch(function () {
                    syncButtonState(false);
                  });
              }
              else {
                syncButtonState(true);
              }
            }
            else {
              video.pause();
              syncButtonState(false);
            }
          });

          video.addEventListener('play', function () {
            syncButtonState(true);
          });

          video.addEventListener('pause', function () {
            syncButtonState(false);
          });
        });
    },
  };
})(Drupal, once);
