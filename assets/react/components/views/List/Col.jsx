import React from 'react';
import PropTypes from 'prop-types';
import Block from '../Blocks';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { mergeClassNames } from '../../../utils/props';

export default function ListCol( props ) {
	const {
		column,
		item,
		content,
		blockType = column.block ?? column.key ?? column.name ?? column,
		className = '',
	} = props;

	if ( 'placeholder' === item ) {
		return <LoadingPlaceholder/>
	}

	return (
		<div className={ ( className || column.classes ) && mergeClassNames( className, column.classes ?? '' ) }>
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
