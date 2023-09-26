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
		callback,
	} = props;

	const [ open, setOpen ] = useState( false );
	const [ input, setInput ] = useState( '' );

	const toggleChildren = () => setOpen( ! open );

	if ( ! props.tag || props.tag.startsWith('_') ) {
		return;
	}

	const tag = parent ? parent + separator + props.tag : props.tag;
	const hasChildren = 'object' === typeof props.children && ! isEmpty( props.children );
	const hasInput = '_input' === props.children;
	const collapseId = tag.replaceAll( '.', '-' ) + '_collapse';

	const selectTag = () => {
		if ( input ) {
			callback( tag + separator + input );
		} else {
			callback( tag );
		}
	}

	const updateInput = ( e ) => {
		setInput( e.target.value );
	}

	return (
		<ListGroup.Item className="p-0 ps-1">
			<div className="d-flex justify-content-between align-items-center">
				<div className="d-flex align-items-center me-4 flex-grow-1" style={ { whiteSpace: 'pre' } }>
					<span>{ startChar }</span>
					<span>{ tag }</span>
					{ hasInput &&
					  <>
					    <span>{ separator }</span><input name={ tag } className="border-0 border-bottom bg-body-secondary bg-opacity-25 lh-1 m-1" onInput={ updateInput } />
					  </>
					}
					<span>{ endChar }</span>
				</div>
				{ ! hasChildren ?
					<Button title="Insert tag" variant="link" disabled={ ! ( ! hasInput || ! isEmpty( input ) ) } className={ "p-0 px-1 bi bi-braces-asterisk" } onClick={ selectTag } aria-controls={ collapseId } aria-expanded={ open }></Button>
					:
					<Button variant="link" className={ "p-0 px-1 bi bi-" + ( open ? "chevron-up" : "chevron-down" ) } onClick={ toggleChildren } aria-controls={ collapseId } aria-expanded={ open }></Button>
				}
			</div>
			{ hasChildren &&
			  <Collapse in={ open }>
				  <div id={ collapseId }>
					  <TagsGroup separator={ separator } parent={ tag } tags={ props.children } callback={ callback } />
				  </div>
			  </Collapse>
			}
		</ListGroup.Item>
	);
}
