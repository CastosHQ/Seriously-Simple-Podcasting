///////////////////////////////////////////////////////////////////////////////
// PLAYER
///////////////////////////////////////////////////////////////////////////////
/* Get Our Elements */

const playback = document.querySelector('.playback');
const audio = document.querySelector('.clip');
const playBtn = document.querySelector('.play-btn');
const pauseBtn = document.querySelector('.pause-btn');
const cover = document.querySelector('.player__artwork');
const duration = document.querySelector('#duration');
const timer = document.querySelector('#timer');
const progress = document.querySelector('.progress');
const progressBar = document.querySelector('.progress__filled');
const skipButtons = playback.querySelectorAll('[data-skip]');
const volumeBtn = document.querySelector('.player-btn__volume');
const speedBtn = document.querySelector('.player-btn__speed');
const loader = document.querySelector('.loader');

/* Helper functions */
function padNum(num) {
    return `${num + 100}`.substring(1);
}

function formatTime(totalSeconds) {
    hours = Math.floor(totalSeconds / 3600);
    totalSeconds %= 3600;
    minutes = Math.floor(totalSeconds / 60);
    seconds = Math.floor(totalSeconds % 60);

    let output;
    hours > 0
        ? (output = `${padNum(hours)}:${padNum(minutes)}:${padNum(seconds)}`)
        : (output = `${padNum(minutes)}:${padNum(seconds)}`);

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

audio.ontimeupdate = function() {
    timer.innerHTML = formatTime(audio.currentTime);
};

audio.onended = function() {
    pauseBtn.classList.add('hide');
    playBtn.classList.remove('hide');
};

function handleProgress() {
    const percent = (audio.currentTime / audio.duration) * 100;
    progressBar.style.flexBasis = `${percent}%`;
}

function scrub(e) {
    const scrubTime = (e.offsetX / progress.offsetWidth) * audio.duration;
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
    const newSpeed =
        this.dataset.speed < 2
            ? (parseFloat(this.dataset.speed) + 0.2).toFixed(1)
            : 1;
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
skipButtons.forEach((button) => button.addEventListener('click', skip));
volumeBtn.addEventListener('click', toggleMute);
audio.addEventListener('waiting', handleWaiting);
audio.addEventListener('canplay', handleCanPlay);

let mousedown = false;
progress.addEventListener('click', scrub);
progress.addEventListener('mousemove', (e) => mousedown && scrub(e));
progress.addEventListener('mousedown', () => (mousedown = true));
progress.addEventListener('mouseup', () => (mousedown = false));

///////////////////////////////////////////////////////////////////////////////
// PANELS
///////////////////////////////////////////////////////////////////////////////
/* Get Our Elements */
const subscribeBtn = document.querySelector('#subscribe-btn');
const subscribePanel = document.querySelector('.player-panels .subscribe');
const subscribePanelClose = document.querySelector(
    '.player-panels .subscribe .close-btn'
);
const shareBtn = document.querySelector('#share-btn');
const sharePanel = document.querySelector('.player-panels .share');
const sharePanelClose = document.querySelector('.player-panels .share .close-btn');
const linkCopyElm = document.querySelector('.input-link');
const linkCopyBtn = document.querySelector('.copy-link');
const embedCopyElm = document.querySelector('.input-embed');
const embedCopyBtn = document.querySelector('.copy-embed');
const rssCopyElm = document.querySelector('.input-rss');
const rssCopyBtn = document.querySelector('.copy-rss');

/* Build out functions */
function togglePanel(panel) {
    panel.classList.contains('open')
        ? panel.classList.remove('open')
        : panel.classList.add('open');
}

function copyLink(elm) {
    elm.select();
    document.execCommand('Copy');
}

/* Hook up the event listeners */
subscribeBtn.addEventListener('click', () => togglePanel(subscribePanel));
subscribePanelClose.addEventListener('click', () =>
    togglePanel(subscribePanel)
);

shareBtn.addEventListener('click', () => togglePanel(sharePanel));
sharePanelClose.addEventListener('click', () => togglePanel(sharePanel));

linkCopyBtn.addEventListener('click', () => copyLink(linkCopyElm));
embedCopyBtn.addEventListener('click', () => copyLink(embedCopyElm));
rssCopyBtn.addEventListener('click', () => copyLink(rssCopyElm));
