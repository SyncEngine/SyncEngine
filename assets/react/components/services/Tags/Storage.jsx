import React, { useState } from 'react';
import { ListGroup } from 'react-bootstrap';
import TagsList from './List';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { debounce } from '../../../utils/events';
import useEntity from '../../../hooks/useEntity';
import { TAG_END_CHAR, TAG_SEPARATOR, TAG_START_CHAR } from '../../../utils/tags';

export default function TagsItemStorage( props ) {

	const {
		startChar = TAG_START_CHAR + ' ',
		endChar = ' ' + TAG_END_CHAR,
		separator = TAG_SEPARATOR,
		parent,
		callback,
	} = props;

	const [ input, setInput ] = useState( '' );
	const [ entity, entityCallbacks, loading ] = useEntity( 'storage' );

	const tag = parent ? parent + separator + props.tag : props.tag;

	const searchStorage = React.useRef(
		debounce( async ( search, callback ) => {
			callback( search );
		} )
	).current;

	const updateInput = ( e ) => {
		setInput( e.target.value );
		searchStorage( e.target.value, entityCallbacks.set );
	}

	return (
		<ListGroup.Item className="p-0 ps-1">
			<div className="d-flex justify-content-between align-items-center">
				<div className="d-flex align-items-center me-4 flex-grow-1" style={ { whiteSpace: 'pre' } }>
					<span>{ startChar }</span>
					<span>{ tag }</span>
					<span>{ separator }</span>
					<input name={ tag } placeholder="ID or reference name" className="border-0 border-bottom bg-body-secondary bg-opacity-25 lh-1 m-1" onInput={ updateInput } />
					<span>{ endChar }</span>
				</div>
			</div>
			{ ( input && entity ) ?
				<TagsList separator={ separator } parent={ tag + separator + entity.ref } tags={ entity } callback={ callback } />
				:
				( input && loading ) && <LoadingPlaceholder />
			}
		</ListGroup.Item>
	);
}
