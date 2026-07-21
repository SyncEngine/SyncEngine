import React from 'react';
import PropTypes from 'prop-types';

export default function ProgressBar( props ) {
	const { total, current } = props;

	return (
		<div className="onboarding-progress">
			{ Array.from( { length: total }, ( _, i ) => (
				<span
					key={ i }
					className={ "onboarding-dot" + ( i === current ? ' active' : '' ) + ( i < current ? ' completed' : '' ) }
				/>
			) ) }
		</div>
	);
}

ProgressBar.propTypes = {
	total: PropTypes.number,
	current: PropTypes.number,
};
