import React from 'react';
import PropTypes from 'prop-types';
import { Stack } from 'react-bootstrap';
import Info from '../../views/Blocks/Info';
import ListRow from '../../views/List/Row';
import { mergeClassNames } from '../../../utils/props';

export default function Header( props ) {

	const classes = mergeClassNames( 'flex-grow-1', props.className ) + ( ( props._disabled ?? false ) ? ' opacity-50' : '' );

	if ( props.columns ) {
		return (
			<Stack gap={3} direction="horizontal" className={ classes + ' flex-grow-1' }>
				<ListRow { ...props } wrap={ false } />
			</Stack>
		)
	}

	return (
		<Info className={ classes } item={ props.item ?? { ...props } } inline={ true } />
	);
}

Header.propTypes = {
	className: PropTypes.string,
	item: PropTypes.object,
	columns: PropTypes.object,
	_disabled: PropTypes.bool,
	_ref: PropTypes.string,
};
