import React, { useContext } from 'react';
import TagsGroup from './Group';
import { OverlayTrigger, Popover } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';
import { isEmpty } from '../../../utils/conditionals';

export default function Tags( props ) {

	const {
		startChar = '{{ ',
		endChar = ' }}',
		separator = '.',
		tags = useContext( TagsContext ),
		callback,
		trigger,
	} = props;

	if ( isEmpty( tags ) ) {
		return;
	}

	const selectTag = ( tag ) => {
		callback( startChar + tag + endChar );
	}

	const body = (
		<TagsGroup tags={ tags } onClick={ selectTag } startChar={ startChar } endChar={ endChar } separator={ separator } />
	);

	if ( trigger ) {
		const popover = (
			<Popover className="w-auto" style={ { minWidth: '200px' } }>
				<Popover.Header>Select tag</Popover.Header>
				<Popover.Body>{ body }</Popover.Body>
			</Popover>
		);

		return (
			<OverlayTrigger trigger="click" overlay={ popover }>
				{trigger}
			</OverlayTrigger>
		);
	}

	return body;
}
