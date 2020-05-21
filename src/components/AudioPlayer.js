const {Component} = wp.element;

import Interweave from 'interweave';

class AudioPlayer extends Component {
	render() {
		return (
			<Interweave content={this.props.audioPlayer} />
		);
	}
}

export default AudioPlayer;
