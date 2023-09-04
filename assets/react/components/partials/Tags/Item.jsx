import React, { useState } from 'react';
import { Button, Collapse, ListGroup } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditionals';
import TagsGroup from './Group';

export default function TagsItem( props ) {

	const {
		startChar = '{{ ',
		endChar = ' }}',
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

	return (
		<ListGroup.Item className="p-0 ps-1">
			<div className="d-flex justify-content-between align-items-center ps-1">
				<Button variant="link" className={ 'text-decoration-none p-0 py-1' + ( parent ? ' pe-3' : '' ) } onClick={ () => onClick( tag ) }>{ startChar + tag + endChar }</Button>
				{ hasChildren &&
				  <Button variant="link" className={ "p-0 px-1 ms-2 bi bi-" + ( open ? "chevron-up" : "chevron-down" ) } onClick={ toggleChildren } aria-controls={ collapseId } aria-expanded={ open }></Button>
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
