/**
 * WordPress dependencies
 */
import {Component} from '@wordpress/element';

/**
 * @todo pass player meta data as props
 */

class PlayerMeta extends Component {
	render() {
		const {title, download, duration} = props;
		const downloadLink = download + '?ref=download';
		const openLink = download + '?ref=new_window';
		return (
			<p>
				<a href={downloadLink}
				   title={title}
				   className="podcast-meta-download">Download file</a> |
				<a href={openLink}
				   target="_blank"
				   title={title}
				   className="podcast-meta-new-window">Play in new window</a> |
				<span className="podcast-meta-duration">Duration: {duration}</span></p>
		);
	}
}

export default PlayerMeta;
