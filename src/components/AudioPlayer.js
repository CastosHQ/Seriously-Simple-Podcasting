import {Component} from '@wordpress/element';

import { Interweave } from 'interweave';

class AudioPlayer extends Component {
	render() {
		return (
			<p className={this.props.className}>
				<Interweave content={this.props.audioPlayer} />
			</p>
		);
	}
}

export default AudioPlayer;
