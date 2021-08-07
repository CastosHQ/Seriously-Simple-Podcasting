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
	let players = document.querySelectorAll('.castos-player');

	players.forEach(function (player) {
		let episodeId = player.dataset.episode,
			playback = player.querySelector('.ssp-playback'),
			audio,
			playBtn = player.querySelector('.play-btn'),
			pauseBtn = player.querySelector('.pause-btn'),
			cover = player.querySelector('.player__artwork'),
			duration = player.querySelector('.ssp-duration'),
			timer = player.querySelector('.ssp-timer'),
			progress = player.querySelector('.ssp-progress'),
			progressBar = player.querySelector('.progress__filled'),
			skipButtons = playback.querySelectorAll('[data-skip]'),
			volumeBtn = player.querySelector('.player-btn__volume'),
			speedBtn = player.querySelector('.player-btn__speed'),
			loader = player.querySelector('.ssp-loader'),
			playlistItems = player.querySelectorAll('.playlist__item'),
			podcastTitle = player.querySelector('.player__podcast-title'),
			episodeTitle = player.querySelector('.player__episode-title');

		/* Helper functions */
		function padNum(num) {
			return ('' + (num + 100)).substring(1);
		}

		function formatTime(totalSeconds) {
			let hours = Math.floor(totalSeconds / 3600);
			totalSeconds %= 3600;
			let minutes = Math.floor(totalSeconds / 60),
				seconds = Math.floor(totalSeconds % 60),
			 	output = 0;
			hours > 0 ? output = padNum(hours) + ' : ' + padNum(minutes) + ' : ' + padNum(seconds) : output = padNum(minutes) + ':' + padNum(seconds);
			return output;
		}

		/* Build out functions */
		function togglePlayback() {
			if (audio.paused) {
				playAudio();
			} else {
				pauseAudio();
			}
		}

		function playAudio(){
			audio.play();
			syncPlayButton();
		}

		function pauseAudio(){
			audio.pause();
			syncPlayButton();
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

		function handleProgress() {
			let percent = audio.currentTime / audio.duration * 100;
			progressBar.style.flexBasis = percent + '%';
		}

		function scrub(e) {
			audio.currentTime = e.offsetX / progress.offsetWidth * audio.duration;
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
			let newSpeed = this.dataset.speed < 2 ? (parseFloat(this.dataset.speed) + 0.2).toFixed(1) : 1;
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

		function initAudio(){
			handleCanPlay();
			audio = player.querySelector('.clip-' + episodeId );
			audio.addEventListener('play', syncPlayButton);
			audio.addEventListener('pause', syncPlayButton);
			audio.addEventListener('playing', syncPlayButton);
			audio.addEventListener('playing', updateDuration);
			audio.addEventListener('timeupdate', handleProgress);

			audio.ontimeupdate = function () {
				timer.innerHTML = formatTime(audio.currentTime);
			};

			audio.onended = function () {
				hideElement(pauseBtn);
				showElement(playBtn);
				let currentActiveItem = player.querySelector('.playlist__item.active'),
					nextItem = currentActiveItem.nextElementSibling;

				currentActiveItem.classList.remove('active');
				if (nextItem) {
					let event = document.createEvent('HTMLEvents');
					event.initEvent('click', true, false);
					nextItem.dispatchEvent(event);
				}
			};

			audio.addEventListener('waiting', handleWaiting);
			audio.addEventListener('canplay', handleCanPlay);
		}

		function hideElement(element) {
			element.classList.add('hide');
		}

		function showElement(element) {
			element.classList.remove('hide');
		}

		/* Hook up the event listeners */
		playBtn.addEventListener('click', togglePlayback);
		pauseBtn.addEventListener('click', togglePlayback);
		cover.addEventListener('click', togglePlayback);
		speedBtn.addEventListener('click', handleSpeedChange);
		skipButtons.forEach(function (button) {
			return button.addEventListener('click', skip);
		});
		volumeBtn.addEventListener('click', toggleMute);


		let mousedown = false;
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
		let subscribeBtn = player.querySelector('.subscribe-btn'),
			subscribePanel = player.querySelector('.player-panels .subscribe'),
			subscribePanelClose = player.querySelector('.player-panels .subscribe .close-btn'),
			shareBtn = player.querySelector('.share-btn'),
			sharePanel = player.querySelector('.player-panels .share'),
			sharePanelClose = player.querySelector('.player-panels .share .close-btn'),
			linkCopyElm = player.querySelector('.input-link'),
			linkCopyBtn = player.querySelector('.copy-link'),
			embedCopyElm = player.querySelector('.input-embed'),
			embedCopyBtn = player.querySelector('.copy-embed'),
			rssCopyElm = player.querySelector('.input-rss'),
			rssCopyBtn = player.querySelector('.copy-rss');

		/* Build out functions */
		function togglePanel(panel) {
			panel.classList.contains('open') ? panel.classList.remove('open') : panel.classList.add('open');
		}

		function copyLink(elm) {
			elm.select();
			document.execCommand('Copy');
		}

 		function handleChangePlaylistItem() {
			if( this.dataset.episode === episodeId ){
				return;
			}

			playlistItems.forEach(function (item) {
				item.classList.remove('active')
			});

			this.classList.add('active');

			let playlistEpisodeTitle = this.querySelector('.playlist__episode-title'),
				episodeCover = this.querySelector('.playlist__item__cover img');

			podcastTitle.textContent = playlistEpisodeTitle.dataset.podcast;
			episodeTitle.textContent = playlistEpisodeTitle.textContent;
			console.log("Herere!", cover, episodeCover);
			cover.querySelector('img').src = episodeCover.src;

			pauseAudio();

			episodeId = this.dataset.episode;

			initAudio();

			setTimeout(function () {
				togglePlayback();
			}, 500);
		}

		function initEventListeners(){
			/* Hook up the event listeners */
			if (subscribeBtn) {
				subscribeBtn.addEventListener('click', function () {
					return togglePanel(subscribePanel);
				});
			}

			if (subscribePanelClose) {
				subscribePanelClose.addEventListener('click', function () {
					return togglePanel(subscribePanel);
				});
			}

			if (shareBtn) {
				shareBtn.addEventListener('click', function () {
					return togglePanel(sharePanel);
				});
			}

			if (sharePanelClose) {
				sharePanelClose.addEventListener('click', function () {
					return togglePanel(sharePanel);
				});
			}

			if (linkCopyBtn) {
				linkCopyBtn.addEventListener('click', function () {
					return copyLink(linkCopyElm);
				});
			}

			if (embedCopyBtn) {
				embedCopyBtn.addEventListener('click', function () {
					return copyLink(embedCopyElm);
				});
			}

			if (rssCopyBtn) {
				rssCopyBtn.addEventListener('click', function () {
					return copyLink(rssCopyElm);
				});
			}

			if (playlistItems) {
				playlistItems.forEach(function (item) {
					item.addEventListener('click', handleChangePlaylistItem)
				});
			}
		}

		function init(){
			initEventListeners();
			initAudio();
		}

		init();
	});
});
