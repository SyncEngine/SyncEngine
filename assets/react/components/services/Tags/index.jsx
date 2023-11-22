import React, { useContext, useRef } from 'react';
import { Overlay, Popover } from 'react-bootstrap';

import { TagsContext } from '../../../context/TagsContext';

import useToggle from '../../../hooks/useToggle';

import TagsGroup from './Group';

import { isEmpty } from '../../../utils/conditionals';
import useRootClose from '../../../hooks/useRootClose';

export default function Tags( props ) {
	const [ show, toggleShow ] = useToggle( false );
	const target = useRef( null );
	const rootClose = useRootClose( toggleShow );

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
		<TagsGroup tags={ { ...tags, _dataset: {} } } callback={ selectTag } startChar={ startChar } endChar={ endChar } separator={ separator } />
	);

	if ( trigger ) {
		return (
			<>
				{ React.cloneElement( trigger, { onClick: toggleShow, ref: target } ) }
				<Overlay
					ref={ rootClose }
					show={ show }
					target={ target.current }
					//rootClose={ true }
					//onHide={ toggleShow }
				>
					<Popover className="w-auto" style={ { minWidth: '200px' } } >
						<Popover.Header>Select tag</Popover.Header>
						<Popover.Body style={ { maxHeight: '250px', overflow: 'auto' } }>{ body }</Popover.Body>
					</Popover>
				</Overlay>
			</>
		);
	}

	return body;
}
