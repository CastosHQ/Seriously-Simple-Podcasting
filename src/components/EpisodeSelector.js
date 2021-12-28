import {Component} from '@wordpress/element';

class EpisodeSelector extends Component {
	render(){
		const {className, episodeRef, episodeId, episodes, activateEpisode} = this.props;

		return (
			<div className={className}>
				Select podcast Episode
				<select ref={episodeRef} className={"castos-select"} defaultValue={episodeId}>
					{episodes.map((item, key) =>
						<option key={item.id} value={item.id} >{item.title}</option>
					)}
				</select>
				<button className={"button"} onClick={activateEpisode}>Go</button>
			</div>
		);
	}
}

export default EpisodeSelector;
