const SyncStatus = ({syncStatus}) => {
	return (<div className="ssp-sidebar-content">
		<div className="ssp-sidebar-field-section">
			<div className="ssp-sync-status">
				<div
					className={ `ssp-sync-label ssp-full-label ${ syncStatus.status }` }
					title={ syncStatus.title }>
					{ syncStatus.title }
				</div>
				<div className="ssp-sync-message">
					{ syncStatus.message }
				</div>
				<div className="ssp-sync-error">
					{ syncStatus.error }
				</div>
			</div>
		</div>

	</div>);
};

export default SyncStatus;
