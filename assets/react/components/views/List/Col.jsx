import React from 'react';
import PropTypes from 'prop-types';
import Block from '../Blocks';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { mergeClassNames } from '../../../utils/props';
import { isItemDisabled } from '../../../utils/conditions';

export default function ListCol( props ) {
	const {
		column,
		item,
		content,
		blockType = column.block ?? column.key ?? column.name ?? column,
	} = props;

	if ( 'placeholder' === item ) {
		return <LoadingPlaceholder/>
	}

	let classes = '';

	if ( isItemDisabled( item ) ) {
		classes = 'text-muted';
	}

	return (
		<div className={ mergeClassNames( props.className, classes ) }>
			<Block { ...props } block={ blockType } content={ content } args={ column } />
		</div>
	);
}

ListCol.propTypes = {
	column: PropTypes.any,
	item: PropTypes.object,
	content: PropTypes.any,
	blockType: PropTypes.string,
	className: PropTypes.string,
};
