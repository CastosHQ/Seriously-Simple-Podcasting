import Bell from '../../img/bell-purple.svg';
import Arrow from '../../img/arrow-right-up-purple.svg';

const Promo = ( { description, title, url } ) => {
	return (
		<div className="ssp-promo-sidebar">
			<div className="ssp-promo-sidebar__description-row">
				<div className="ssp-promo-sidebar__bell">
					<img src={ Bell } alt={ title }/>
				</div>
				<div className="ssp-promo-sidebar__description">
					{ description }
				</div>
			</div>

			<a className="ssp-promo-sidebar__btn" target="_blank"
			   rel="noopener noreferrer"
			   href={ url }>
				{ title }
				<img src={ Arrow } alt={ title }/>
			</a>
		</div>
	);
};

export default Promo;
