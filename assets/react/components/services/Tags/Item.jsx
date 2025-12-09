import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { Collapse, ListGroup } from 'react-bootstrap';

import TagsList from './List';
import TagsLabel from './Label';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import { isEmpty } from '../../../utils/conditions';
import { suppress } from '../../../utils/events';
import { TAG_END_CHAR, TAG_SEPARATOR, TAG_START_CHAR } from '../../../utils/tags';

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

	if ( ! props.tag || props.tag.startsWith( '_' ) ) {
		return;
	}

	const tag = parent ? parent + separator + props.tag : props.tag;
	const hasChildren = 'object' === typeof props.children && ! isEmpty( props.children );
	const hasInput = '_input' === props.children;
	const collapseId = tag.replaceAll( '.', '-' ) + '_collapse';

	const selectTag = ( e ) => {
		suppress( e );
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
		<ListGroup.Item className="p-0 ps-2 bg-hover-primary-subtle">
			<div className="d-flex py-1 justify-content-between align-items-center" onClick={ toggleChildren }>
				<TagsLabel
					className="d-flex align-items-center me-4 flex-grow-1"
					startChar={ startChar }
					endChar={ endChar }
					separator={ separator }
					tag={ props.tag }
					label={ label }
					prefix={ prefix ? prefix + separator : undefined }
					title={ tag }
					postfix={ hasInput &&
						<>
							<span>{ separator }</span><input name={ tag } className="border-0 border-bottom bg-body-secondary bg-opacity-25 lh-1 m-1" onInput={ updateInput } />
						</>
					}
				/>
				{ ! hasChildren ? // @todo Convert to Icon component and move icons to config.
					<Button title="Insert tag" variant="link" disabled={ ! ( ! hasInput || ! isEmpty( input ) ) } className="p-0 px-1" onClick={ selectTag } aria-controls={ collapseId } aria-expanded={ open }>
						<Icon icon="insert" />
					</Button>
					:
					<Button variant="link" className="p-0 px-1" onClick={ toggleChildren } aria-controls={ collapseId } aria-expanded={ open }>
						<Icon icon={ ( open ? "chevron-up" : "chevron-down" ) } />
					</Button>
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

TagsItem.propTypes = {
	tag: PropTypes.string,
	label: PropTypes.string,
	parent: PropTypes.string,
	children: PropTypes.oneOfType( [ PropTypes.object, PropTypes.array, PropTypes.string ] ),
}
