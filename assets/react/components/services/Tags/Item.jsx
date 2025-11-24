import React, { useState } from 'react';
import { Button, Collapse, ListGroup } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditions';
import TagsList from './List';
import { TAG_END_CHAR, TAG_SEPARATOR, TAG_START_CHAR } from '../../../utils/tags';
import TagsLabel from './Label';

export default function TagsItem( props ) {

	const {
		startChar = TAG_START_CHAR + ' ',
		endChar = ' ' + TAG_END_CHAR,
		separator = TAG_SEPARATOR,
		parent,
		prefix = parent,
		label,
		callback,
	} = props;

	const [ open, setOpen ] = useState( props.open );
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
		<ListGroup.Item className="p-2 py-1 pe-0">
			<div className="d-flex justify-content-between align-items-center">
				<TagsLabel
					className="d-flex align-items-center me-4 flex-grow-1"
					startChar={ startChar }
					endChar={ endChar }
					separator={ separator }
					tag={ props.tag }
					label={ label }
					prefix={ prefix + separator }
					title={ tag }
					postfix={ hasInput &&
						<>
							<span>{ separator }</span><input name={ tag } className="border-0 border-bottom bg-body-secondary bg-opacity-25 lh-1 m-1" onInput={ updateInput } />
						</>
					}
				/>
				{ ! hasChildren ? // @todo Convert to Icon component and move icons to config.
					<Button title="Insert tag" variant="link" disabled={ ! ( ! hasInput || ! isEmpty( input ) ) } className={ "p-0 px-1 bi bi-braces-asterisk" } onClick={ selectTag } aria-controls={ collapseId } aria-expanded={ open }></Button>
					:
					<Button variant="link" className={ "p-0 px-1 bi bi-" + ( open ? "chevron-up" : "chevron-down" ) } onClick={ toggleChildren } aria-controls={ collapseId } aria-expanded={ open }></Button>
				}
			</div>
			{ hasChildren &&
			  <Collapse in={ open }>
				  <div id={ collapseId }>
					  <TagsList separator={ separator } prefix={ prefix ? prefix + separator + label : label } parent={ tag } tags={ props.children } callback={ callback } />
				  </div>
			  </Collapse>
			}
		</ListGroup.Item>
	);
}
