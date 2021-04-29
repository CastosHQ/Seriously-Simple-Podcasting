'use strict';

function docReady(fn) {
	// see if DOM is already available
	if (document.readyState === "complete" || document.readyState === "interactive") {
		setTimeout(fn, 1); // call on next available tick
	} else {
		document.addEventListener("DOMContentLoaded", fn);
	}
}

docReady(function() {
	/* Get Our Elements */
	var players = document.querySelectorAll('.castos-player');

	players.forEach(function (player) {
		var episodeId = player.dataset.episode,
			playback = document.querySelector('.playback-' + episodeId),
			audio = document.querySelector('.clip-' + episodeId),
			playBtn = document.querySelector('.play-btn-' + episodeId),
			pauseBtn = document.querySelector('.pause-btn-' + episodeId),
			cover = document.querySelector('.player__artwork-' + episodeId),
			duration = document.querySelector('#duration-' + episodeId),
			timer = document.querySelector('#timer-' + episodeId),
			progress = document.querySelector('.progress-' + episodeId),
			progressBar = document.querySelector('.progress__filled-' + episodeId),
			skipButtons = playback.querySelectorAll('[data-skip]'),
			volumeBtn = document.querySelector('.player-btn__volume-' + episodeId),
			speedBtn = document.querySelector('.player-btn__speed-' + episodeId),
			loader = document.querySelector('.loader-' + episodeId);

		/* Helper functions */
		function padNum(num) {
			return ('' + (num + 100)).substring(1);
		}

		function formatTime(totalSeconds) {
			var hours = Math.floor(totalSeconds / 3600);
			totalSeconds %= 3600;
			var minutes = Math.floor(totalSeconds / 60);
			var seconds = Math.floor(totalSeconds % 60);
			var output = void 0;
			hours > 0 ? output = padNum(hours) + ' : ' + padNum(minutes) + ' : ' + padNum(seconds) : output = padNum(minutes) + ':' + padNum(seconds);
			return output;
		}

		/* Build out functions */
		function togglePlayback() {
			if (audio.paused) {
				audio.play();
				pauseBtn.classList.remove('hide');
				playBtn.classList.add('hide');
			} else {
				audio.pause();
				pauseBtn.classList.add('hide');
				playBtn.classList.remove('hide');
			}
		}

		function syncPlayButton() {
			if (audio.paused) {
				pauseBtn.classList.add('hide');
				playBtn.classList.remove('hide');
			} else {
				pauseBtn.classList.remove('hide');
				playBtn.classList.add('hide');
			}
		}

		function updateDuration() {
			duration.innerHTML = formatTime(audio.duration);
		}

		audio.ontimeupdate = function () {
			timer.innerHTML = formatTime(audio.currentTime);
		};

		audio.onended = function () {
			pauseBtn.classList.add('hide');
			playBtn.classList.remove('hide');
		};

		function handleProgress() {
			var percent = audio.currentTime / audio.duration * 100;
			progressBar.style.flexBasis = percent + '%';
		}

		function scrub(e) {
			var scrubTime = e.offsetX / progress.offsetWidth * audio.duration;
			audio.currentTime = scrubTime;
		}

		function skip() {
			audio.currentTime += parseFloat(this.dataset.skip);
		}

		function toggleMute() {
			if (audio.volume === 1) {
				audio.volume = 0;
				volumeBtn.classList.add('off');
			} else {
				audio.volume = 1;
				volumeBtn.classList.remove('off');
			}
		}

		function handleSpeedChange() {
			var newSpeed = this.dataset.speed < 2 ? (parseFloat(this.dataset.speed) + 0.2).toFixed(1) : 1;
			speedBtn.setAttribute('data-speed', newSpeed);
			speedBtn.innerHTML = newSpeed + 'x';
			audio.playbackRate = newSpeed;
		}

		function handleWaiting() {
			loader.classList.remove('hide');
		}

		function handleCanPlay() {
			loader.classList.add('hide');
		}

		/* Hook up the event listeners */
		playBtn.addEventListener('click', togglePlayback);
		pauseBtn.addEventListener('click', togglePlayback);
		cover.addEventListener('click', togglePlayback);
		speedBtn.addEventListener('click', handleSpeedChange);
		audio.addEventListener('play', syncPlayButton);
		audio.addEventListener('pause', syncPlayButton);
		audio.addEventListener('playing', syncPlayButton);
		audio.addEventListener('playing', updateDuration);
		audio.addEventListener('timeupdate', handleProgress);
		skipButtons.forEach(function (button) {
			return button.addEventListener('click', skip);
		});
		volumeBtn.addEventListener('click', toggleMute);
		audio.addEventListener('waiting', handleWaiting);
		audio.addEventListener('canplay', handleCanPlay);

		var mousedown = false;
		progress.addEventListener('click', scrub);
		progress.addEventListener('mousemove', function (e) {
			return mousedown && scrub(e);
		});
		progress.addEventListener('mousedown', function () {
			return mousedown = true;
		});
		progress.addEventListener('mouseup', function () {
			return mousedown = false;
		});

		//
		// PANELS
		//
		/* Get Our Elements */
		var subscribeBtn = document.querySelector('#subscribe-btn-' + episodeId),
			subscribePanel = document.querySelector('.player-panels-' + episodeId + ' .subscribe-' + episodeId),
			subscribePanelClose = document.querySelector('.player-panels-' + episodeId + ' .subscribe-' + episodeId + ' .close-btn-' + episodeId),
			shareBtn = document.querySelector('#share-btn-' + episodeId),
			sharePanel = document.querySelector('.player-panels-' + episodeId + ' .share-' + episodeId),
			sharePanelClose = document.querySelector('.player-panels-' + episodeId + ' .share-' + episodeId + ' .close-btn-' + episodeId),
			linkCopyElm = document.querySelector('.input-link-' + episodeId),
			linkCopyBtn = document.querySelector('.copy-link-' + episodeId),
			embedCopyElm = document.querySelector('.input-embed-' + episodeId),
			embedCopyBtn = document.querySelector('.copy-embed-' + episodeId),
			rssCopyElm = document.querySelector('.input-rss-' + episodeId),
			rssCopyBtn = document.querySelector('.copy-rss-' + episodeId);

		/* Build out functions */
		function togglePanel(panel) {
			panel.classList.contains('open') ? panel.classList.remove('open') : panel.classList.add('open');
		}

		function copyLink(elm) {
			elm.select();
			document.execCommand('Copy');
		}

		/* Hook up the event listeners */
		if (subscribeBtn) {
			subscribeBtn.addEventListener('click', function () {
				return togglePanel(subscribePanel);
			});
		}

		subscribePanelClose.addEventListener('click', function () {
			return togglePanel(subscribePanel);
		});

		if (shareBtn) {
			shareBtn.addEventListener('click', function () {
				return togglePanel(sharePanel);
			});
		}

		sharePanelClose.addEventListener('click', function () {
			return togglePanel(sharePanel);
		});

		linkCopyBtn.addEventListener('click', function () {
			return copyLink(linkCopyElm);
		});
		embedCopyBtn.addEventListener('click', function () {
			return copyLink(embedCopyElm);
		});
		rssCopyBtn.addEventListener('click', function () {
			return copyLink(rssCopyElm);
		});
	});
});
