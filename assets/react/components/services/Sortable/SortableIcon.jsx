import React from 'react';
import PropTypes from 'prop-types';
import Icon from '../../partials/Icon';

export default function SortableIcon( props ) {
	const {
		direction,
		attributes,
		listeners,
		icon = 'sort-handle',
		className,
	} = props;

	const style = {
		cursor: 'grab',
	};

	if ( className ) {
		attributes.className += className;
	}

	return (
		<span {...attributes} {...listeners} style={ style }><Icon icon={ icon } /></span>
	)
}

SortableIcon.propTypes = {
	direction: PropTypes.string,
	attributes: PropTypes.object,
	listeners: PropTypes.object,
	icon: PropTypes.string,
	className: PropTypes.string,
};
