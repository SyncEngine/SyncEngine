import React, { useContext } from 'react';
import TagsGroup from './Group';
import { OverlayTrigger, Popover } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';

export default function Tags( props ) {

	const {
		startChar = '{{ ',
		endChar = ' }}',
		separator = '.',
		tags = useContext( TagsContext ),
		onClick,
		trigger,
	} = props;

	const selectTag = ( tag ) => {
		onClick( startChar + tag + endChar );
	}

	const body = (
		<TagsGroup tags={ tags } onClick={ selectTag } separator={ separator }/>
	);

	if ( trigger ) {
		const popover = (
			<Popover>
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
