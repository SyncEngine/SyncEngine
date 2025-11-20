import React from 'react';
import { ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import TagsItem from './Item';
import TagsItemStorage from './Storage';

export default function TagsList( props ) {

	const {
		tags,
	} = props;

	return (
		<ListGroup className="list-group-flush border-start">
			{
				objectToMappable( { ...tags }, 'tag', 'children', true ).map( ( item, index ) => {
					if ( '_storage' === item.tag ) {
						return <TagsItemStorage key={ index } { ...props } tag="storage" />
					}
					return <TagsItem key={ index } { ...props } tag={ item.tag } children={ item.children } />
				} )
			}
		</ListGroup>
	);
}
