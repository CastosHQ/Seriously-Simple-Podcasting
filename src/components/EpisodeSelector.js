const {Component} = wp.element;

class EpisodeSelector extends Component {
	render(){
		const {className, episodeRef, episodes, activateEpisode} = this.props;
		return (
			<div className={className}>
				Select podcast Episode
				<select ref={episodeRef}>
					{episodes.map((item, key) =>
						<option key={item.id} value={item.id}>{item.title}</option>
					)}
				</select>
				<button onClick={activateEpisode}>Go</button>
			</div>
		);
	}
}

export default EpisodeSelector;
