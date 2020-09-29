<?php

$html =
	'<p>
		Subscribe:
		<a href="'. $itunes['link'] .'" target="_blank" class="podcast-meta-itunes">' . $itunes['title'] .'</a>
		|
		<a href="'. $stitcher['link'] .'" target="_blank" class="podcast-meta-itunes">' . $stitcher['title'] .'</a>
		|
		<a href="'. $googlePlay['link'] .'" target="_blank" class="podcast-meta-itunes">' . $googlePlay['title'] .'</a>
		|
		<a href="'. $spotify['link'] .'" target="_blank" class="podcast-meta-itunes">' . $spotify['title'] .'</a>
	</p>';

echo $html;