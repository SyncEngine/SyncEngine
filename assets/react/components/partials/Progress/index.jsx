import React from 'react';
import PropTypes from 'prop-types';
import { ProgressBar as ProgressBarBootstrap } from 'react-bootstrap';

export default function ProgressBar( props ) {
	return <ProgressBarBootstrap { ...props } />
}

ProgressBar.propTypes = {
	progress: PropTypes.number,
	total: PropTypes.number,
	label: PropTypes.string,
	variant: PropTypes.string,
};
