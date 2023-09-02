import React from 'react';
import { ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import TagsItem from './Item';

export default function TagsGroup( props ) {

	const {
		separator = '.',
		parent,
		tags,
		onClick,
	} = props;

	return (
		<ListGroup className="list-group-flush">
			{
				objectToMappable( { ...tags }, 'tag', 'children', true ).map( ( item, index ) => {
					return <TagsItem key={ index } separator={ separator } tag={ item.tag } children={ item.children } parent={ parent } onClick={ onClick } />
				} )
			}
		</ListGroup>
	);
}
