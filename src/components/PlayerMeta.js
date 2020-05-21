const {__} = wp.i18n;
const {Component} = wp.element;

class PlayerMeta extends Component {
	render() {
		const {className, title, download, duration} = this.props;
		const downloadLink = download + '?ref=download';
		const openLink = download + '?ref=new_window';
		return (
			<p className={className}>
				<a href={downloadLink} title={title} className="podcast-meta-download">{__('Download File', 'seriously-simple-podcasting')}</a> |&nbsp;
				<a href={openLink} target="_blank" title={title} className="podcast-meta-new-window">{__('Play in new window', 'seriously-simple-podcasting')}</a> |&nbsp;
				<span className="podcast-meta-duration">{__('Duration', 'seriously-simple-podcasting')}: {duration}</span>
			</p>
		);
	}
}

export default PlayerMeta;
