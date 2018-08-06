/**
 * SSP HTML 5 media player functionality
 * Created by Jonathan Bossenger on 2018/08/06.
 */

// @todo _paq variable declaration

String.prototype.toFormattedDuration = function () {
	var sec_num = parseInt(this, 10); // don't forget the second param
	var hours = Math.floor(sec_num / 3600);
	var minutes = Math.floor((sec_num - (hours * 3600)) / 60);
	var seconds = sec_num - (hours * 3600) - (minutes * 60);

	if (hours < 10) {
		hours = "0" + hours;
	}
	if (minutes < 10) {
		minutes = "0" + minutes;
	}
	if (seconds < 10) {
		seconds = "0" + seconds;
	}
	return hours > 0 ? (hours + ':' + minutes + ':' + seconds) : (minutes + ':' + seconds);
};

//@todo move all media player specific functionality here