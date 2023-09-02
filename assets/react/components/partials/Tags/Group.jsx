import React, { useState } from 'react';
import { Collapse, ListGroup } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

export default function TagsGroup( props ) {

	const {
		separator = '.',
		parent,
		list,
		onClick,
	} = props;

	const [ open, setOpen ] = useState( false );

	const toggleChildren = () => setOpen( ! open );

	return (
		<ListGroup className="list-group-flush">
			{
				objectToMappable( list, 'tag', 'children' ).map( ( item ) => {
					const tag = parent + separator + item.tag;
					const hasChildren = 'object' === typeof item.children && ! isEmpty( item.children );
					const collapseId = tag.replaceAll( '.', '-' ) + '_collapse';
					return <ListGroup.Item>
						<div className="d-flex justify-content-between align-items-start">
							{ tag }
							{ hasChildren &&
								<span className="bi" onClick={ toggleChildren } aria-controls={ collapseId } aria-expanded={ open }></span>
							}
						</div>
						{ hasChildren &&
							<Collapse in={ open }>
								<div id={ collapseId }>
									<TagsGroup parent={ tag } list={ items.children } onClick={ onClick } />
								</div>
							</Collapse>
						}
					</ListGroup.Item>
				} )
			}
		</ListGroup>
	);
}
