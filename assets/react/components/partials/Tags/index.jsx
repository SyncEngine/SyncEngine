import React, { useContext, useRef, useState } from 'react';
import TagsGroup from './Group';
import { Overlay, Popover } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';
import { ElementContext } from '../../../context/ElementContext';
import { isEmpty } from '../../../utils/conditionals';

export default function Tags( props ) {
	const [ show, setShow ] = useState( false );
	const target = useRef(null);

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
		<TagsGroup tags={ tags } callback={ selectTag } startChar={ startChar } endChar={ endChar } separator={ separator } />
	);

	if ( trigger ) {
		const container = useContext( ElementContext );

		return (
			<>
				{ React.cloneElement( trigger, { onClick: () => setShow( ! show ), ref: target } ) }
				<Overlay
					target={ target.current }
					show={ show }
					container={ container.closest( '.modal' ) }
					rootClose={ true }
					rootCloseEvent="click"
					onHide={ () => setShow( false ) }
				>
					<Popover className="w-auto" style={ { minWidth: '200px' } } >
						<Popover.Header>Select tag</Popover.Header>
						<Popover.Body>{ body }</Popover.Body>
					</Popover>
				</Overlay>
			</>
		);
	}

	return body;
}
