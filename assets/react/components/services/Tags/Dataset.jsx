import React, { useState } from 'react';
import { ListGroup } from 'react-bootstrap';
import TagsGroup from './Group';
import useEntities from '../../../hooks/useEntities';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { debounce } from '../../../utils/events';
import useEntity from '../../../hooks/useEntity';

export default function TagsItemDataset( props ) {

	const {
		startChar = '{{ ',
		endChar = ' }}',
		separator = '.',
		parent,
		callback,
	} = props;

	const [ input, setInput ] = useState( '' );
	const [ entity, getEntity, loading ] = useEntity( 'dataset' );

	const tag = parent ? parent + separator + props.tag : props.tag;

	const searchDataset = React.useRef(
		debounce( async ( search, callback ) => {
			callback( search );
		} )
	).current;

	const updateInput = ( e ) => {
		setInput( e.target.value );
		searchDataset( e.target.value, getEntity );
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
				<TagsGroup separator={ separator } parent={ tag + separator + entity.ref } tags={ entity } callback={ callback } />
				:
				( input && loading ) && <LoadingPlaceholder />
			}
		</ListGroup.Item>
	);
}
