import React, { useState } from 'react';
import { Collapse, ListGroup } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditionals';
import TagsGroup from './Group';

export default function TagsItem( props ) {

	const {
		separator = '.',
		parent,
		onClick,
	} = props;

	const [ open, setOpen ] = useState( false );

	const toggleChildren = () => setOpen( ! open );

	if ( ! props.tag || props.tag.startsWith('_') ) {
		return;
	}

	const tag = parent ? parent + separator + props.tag : props.tag;
	const hasChildren = 'object' === typeof props.children && ! isEmpty( props.children );
	const collapseId = tag.replaceAll( '.', '-' ) + '_collapse';

	console.log( props );

	return (
		<ListGroup.Item>
			<div className="d-flex justify-content-between align-items-start">
				<span onClick={ () => onClick( tag ) }>{ tag }</span>
				{ hasChildren &&
				  <span className={ "bi bi-" + ( open ? "chevron-up" : "chevron-down" ) } onClick={ toggleChildren } aria-controls={ collapseId } aria-expanded={ open }></span>
				}
			</div>
			{ hasChildren &&
			  <Collapse in={ open }>
				  <div id={ collapseId }>
					  <TagsGroup separator={ separator } parent={ tag } tags={ props.children } onClick={ onClick } />
				  </div>
			  </Collapse>
			}
		</ListGroup.Item>
	);
}
