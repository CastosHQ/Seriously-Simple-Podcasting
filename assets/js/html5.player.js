// @todo fix deprecated getCurrentTime function
document.addEventListener("DOMContentLoaded", function () {
	(function ($) {
		window.ssp_player = [];

		var sspUpdateDuration = [];

		$('div.ssp-player-large').each(function () {

			var large_player = $(this);
			var player_instance_number = large_player.data('player-instance-number');
			//const player_waveform_colour = large_player.data('player-waveform-colour');
			var player_waveform_progress_colour = large_player.data('player-waveform-progress-colour');
			var source_file = large_player.data('source-file');
			sspUpdateDuration[player_instance_number] = '';

			// Create Player
			window.ssp_player[player_instance_number] = WaveSurfer.create({
				container: '#waveform' + player_instance_number,
				waveColor: '#444',
				progressColor: player_waveform_progress_colour,
				barWidth: 3,
				barHeight: 15,
				height: 8,
				hideScrollbar: true,
				skipLength: 30,
				backend: 'MediaElement'
			});

			//Set player track
			window.ssp_player[player_instance_number].track = source_file;

			/**
			 * Setting and drawing the peaks seems to be required for the 'load on play' functionality to work
			 */
			//Set peaks
			window.ssp_player[player_instance_number].backend.peaks = [0.0218, 0.0183, 0.0165, 0.0198, 0.2137, 0.2888, 0.2313, 0.15, 0.2542, 0.2538, 0.2358, 0.1195, 0.1591, 0.2599, 0.2742, 0.1447, 0.2328, 0.1878, 0.1988, 0.1645, 0.1218, 0.2005, 0.2828, 0.2051, 0.1664, 0.1181, 0.1621, 0.2966, 0.189, 0.246, 0.2445, 0.1621, 0.1618, 0.189, 0.2354, 0.1561, 0.1638, 0.2799, 0.0923, 0.1659, 0.1675, 0.1268, 0.0984, 0.0997, 0.1248, 0.1495, 0.1431, 0.1236, 0.1755, 0.1183, 0.1349, 0.1018, 0.1109, 0.1833, 0.1813, 0.1422, 0.0961, 0.1191, 0.0791, 0.0631, 0.0315, 0.0157, 0.0166, 0.0108];

			//Draw peaks
			window.ssp_player[player_instance_number].drawBuffer();

			//Variable to check if the track is loaded
			window.ssp_player[player_instance_number].loaded = false;

			// @todo Track Player errors

			// On Media Ready
			window.ssp_player[player_instance_number].on('ready', function (e) {
				if (!window.ssp_player[player_instance_number].loaded) {
					window.ssp_player[player_instance_number].loaded = true;
					window.ssp_player[player_instance_number].play();
				}
				$('#ssp_player_id_' + player_instance_number + ' #sspTotalDuration').text(window.ssp_player[player_instance_number].getDuration().toString().toFormattedDuration());
				$('#ssp_player_id_' + player_instance_number + ' #sspPlayedDuration').text(window.ssp_player[player_instance_number].getCurrentTime().toString().toFormattedDuration());
			});

			// On Media Played
			window.ssp_player[player_instance_number].on('play', function (e) {
				if (!window.ssp_player[player_instance_number].loaded) {
					window.ssp_player[player_instance_number].load(window.ssp_player[player_instance_number].track, window.ssp_player[player_instance_number].backend.peaks);
				}
				// @todo Track Podcast Specific Play
				$('#ssp_player_id_' + player_instance_number + ' #ssp-play-pause .ssp-icon').removeClass().addClass('ssp-icon ssp-icon-pause_icon');
				$('#ssp_player_id_' + player_instance_number + ' #sspPlayedDuration').text(window.ssp_player[player_instance_number].getCurrentTime().toString().toFormattedDuration());
				sspUpdateDuration[player_instance_number] = setInterval(function () {
					$('#ssp_player_id_' + player_instance_number + ' #sspPlayedDuration').text(window.ssp_player[player_instance_number].getCurrentTime().toString().toFormattedDuration());
				}, 100);
			});

			// On Media Paused
			window.ssp_player[player_instance_number].on('pause', function (e) {
				// @todo Track Podcast Specific Pause
				$('#ssp_player_id_' + player_instance_number + ' #ssp-play-pause .ssp-icon').removeClass().addClass('ssp-icon ssp-icon-play_icon');
				clearInterval(sspUpdateDuration[player_instance_number]);
			});

			// On Media Finished
			window.ssp_player[player_instance_number].on('finish', function (e) {
				$('#ssp_player_id_' + player_instance_number + ' #ssp-play-pause .ssp-icon').removeClass().addClass('ssp-icon ssp-icon-play_icon');
				// @todo Track Podcast Specific Finish
			});

			// On Play/Pause button clicked
			$('#ssp_player_id_' + player_instance_number + ' #ssp-play-pause').on('click', function (e) {
				window.ssp_player[player_instance_number].playPause();
			});

			// On Back 30 seconds clicked
			$('#ssp_player_id_' + player_instance_number + ' #ssp-back-thirty').on('click', function (e) {
				// @todo Track Podcast Specific Back 30
				window.ssp_player[player_instance_number].skipBackward();
			});

			// On clicking the playback speed button
			$('#ssp_player_id_' + player_instance_number + ' #ssp_playback_speed' + player_instance_number).on('click', function (e) {
				switch ($(e.currentTarget).parent().find('[data-ssp-playback-rate]').attr('data-ssp-playback-rate')) {
					case "1":
						$(e.currentTarget).parent().find('[data-ssp-playback-rate]').attr('data-ssp-playback-rate', '1.5');
						$(e.currentTarget).parent().find('[data-ssp-playback-rate]').text('1.5X');
						window.ssp_player[player_instance_number].setPlaybackRate(1.5);
						break;
					case "1.5":
						$(e.currentTarget).parent().find('[data-ssp-playback-rate]').attr('data-ssp-playback-rate', '2');
						$(e.currentTarget).parent().find('[data-ssp-playback-rate]').text('2X');
						window.ssp_player[player_instance_number].setPlaybackRate(2);
						break;
					case "2":
						$(e.currentTarget).parent().find('[data-ssp-playback-rate]').attr('data-ssp-playback-rate', '1');
						$(e.currentTarget).parent().find('[data-ssp-playback-rate]').text('1X');
						window.ssp_player[player_instance_number].setPlaybackRate(1);
					default:
						break;
				}
			});

		});

	}(jQuery))
});
