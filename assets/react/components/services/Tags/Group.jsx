import React from 'react';
import { ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import TagsItem from './Item';

export default function TagsGroup( props ) {

	const {
		tags,
	} = props;

	return (
		<ListGroup className="list-group-flush border-start">
			{
				objectToMappable( { ...tags }, 'tag', 'children', true ).map( ( item, index ) => {
					return <TagsItem key={ index } { ...props } tag={ item.tag } children={ item.children } />
				} )
			}
		</ListGroup>
	);
}
