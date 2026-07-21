import React from 'react';
import PropTypes from 'prop-types';
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
				objectToMappable( { ...tags }, '_tag', '_children' ).map( ( item, index ) => {
					if ( '_storage' === item._tag ) {
						return <TagsItemStorage key={ index } { ...props } tag="storage" />
					}
					return <TagsItem key={ index } { ...props } label={ item.label } tag={ item._tag } children={ item._children } />
				} )
			}
		</ListGroup>
	);
}

TagsList.propTypes = {
	tags: PropTypes.object,
};
