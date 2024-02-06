import React, { useContext } from 'react';
import { Popover } from 'react-bootstrap';

import { TagsContext } from '../../../context/TagsContext';

import TagsGroup from './Group';
import OverlayToggle from '../OverlayToggle';

import { isEmpty } from '../../../utils/conditions';

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
		<TagsGroup tags={ { ...tags, _storage: {} } } callback={ selectTag } startChar={ startChar } endChar={ endChar } separator={ separator } />
	);

	if ( trigger ) {
		return (
			<OverlayToggle trigger={ trigger }>
				<Popover className="w-auto" style={ { minWidth: '200px' } }>
					<Popover.Header>Select tag</Popover.Header>
					<Popover.Body style={ { maxHeight: '250px', overflow: 'auto' } }>{ body }</Popover.Body>
				</Popover>
			</OverlayToggle>
		);
	}

	return body;
}
