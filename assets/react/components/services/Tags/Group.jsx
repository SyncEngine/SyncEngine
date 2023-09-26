import React from 'react';
import { ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import TagsItem from './Item';
import TagsItemDataset from './Dataset';

export default function TagsGroup( props ) {

	const {
		tags,
	} = props;

	return (
		<ListGroup className="list-group-flush border-start">
			{
				objectToMappable( { ...tags }, 'tag', 'children', true ).map( ( item, index ) => {
					if ( '_dataset' === item.tag ) {
						return <TagsItemDataset key={ index } { ...props } tag="dataset" />
					}
					return <TagsItem key={ index } { ...props } tag={ item.tag } children={ item.children } />
				} )
			}
		</ListGroup>
	);
}
