import {Component} from '@wordpress/element';

class PlayerMeta extends Component {
	render() {
		const {title, download, duration} = this.props;
		const downloadLink = download + '?ref=download';
		const openLink = download + '?ref=new_window';
		return (
			<p>
				<a href={downloadLink} title={title} className="podcast-meta-download">Download file</a> |&nbsp;
				<a href={openLink} target="_blank" title={title} className="podcast-meta-new-window">Play in new window</a> |&nbsp;
				<span className="podcast-meta-duration">Duration: {duration}</span></p>
		);
	}
}

export default PlayerMeta;
