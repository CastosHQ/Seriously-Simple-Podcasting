/**
 * WordPress dependencies
 */
import {Component} from '@wordpress/element';
import {Icon} from '@wordpress/components';
import {__} from '@wordpress/i18n';

class EpisodeSelector extends Component {

	render() {

		const activateEpisode = () => {
			this.setState( { editing: true } );
		};

		const episodes = [
			{id: 5, title: 'Lipsum'},
			{id: 42, title: 'shirt'},
			{id: 71, title: 'socks'}
		]
		return (
			<div>
				Select podcast Episode
				<select onChange={activateEpisode}>
					{episodes.map((item, key) =>
						<option value={item.id}>{item.title}</option>
					)}
				</select>

			</div>
		);
	}
}

export default EpisodeSelector;
