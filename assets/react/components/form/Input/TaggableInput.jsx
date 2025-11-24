import React, { useContext, useEffect, useMemo, useState } from 'react';

import { createEditor, Editor, Node, Path, Range, Text, Transforms } from 'slate';
import { Editable, ReactEditor, Slate, withReact } from 'slate-react';
import { withHistory } from 'slate-history';

import Tags from '../../services/Tags';
import Icon from '../../partials/Icon';
import TagsLabel, { isTagLabeled } from '../../services/Tags/Label';

import {
	isTag,
	splitByTags,
	TAG_END_CHAR,
	TAG_START_CHAR,
	TAG_SUB_END_CHAR,
	TAG_SUB_START_CHAR,
	trimTag,
} from '../../../utils/tags';
import { mergeClassNames } from '../../../utils/props';
import useToggle from '../../../hooks/useToggle';
import { TagsContext } from '../../../context/TagsContext';
import { publish, subscribe, unsubscribe } from '../../../utils/events';

// Add zero-width spaces around editable children to avoid Chrome cursor bugs
const InlineChromiumBugfix = () => (
	<span contentEditable={ false } style={ { fontSize: 0 } }>
		{ String.fromCodePoint( 160 ) /* Non-breaking space */ }
	</span>
);

const isElementFocused = ( editor, element ) => {
	if ( ! ReactEditor.isFocused( editor ) || ! editor.selection ) return false;

	const path = ReactEditor.findPath( editor, element );

	// Flatten the selection to all paths touched by the selection
	const { anchor, focus } = editor.selection;

	return (
		Path.isAncestor( path, anchor.path ) ||
		Path.isAncestor( path, focus.path ) ||
		Path.equals( path, anchor.path ) ||
		Path.equals( path, focus.path )
	);
};

const moveCursorToElement = ( editor, element ) => {
	if ( ! ReactEditor.isFocused( editor ) ) {
		// ensure editor is focused.
		ReactEditor.focus( editor );
	}

	if ( isElementFocused( editor, element ) ) {
		return;
	}

	const path = ReactEditor.findPath( editor, element );

	// @todo Check if the first node inside the element is a text node?
	//const firstText = Node.descendant( element, [0] ); // index 0

	const firstTextPath = [ ...path, 0 ]; // full path in the editor

	// place the cursor at the start
	Transforms.select(editor, {
		anchor: { path: firstTextPath, offset: 0 },
		focus: { path: firstTextPath, offset: 0 },
	});

	publish( 'taggableInput.focus', { element: element } );

	// focus the DOM
	ReactEditor.focus( editor );
};

// Renders a tag badge with an editable text child
const TagElement = ( { attributes, children, element, editor } ) => {
	const tags = useContext( TagsContext );
	const tag = Node.string( element );
	const isLabeled = tag && isTagLabeled( tag, tags );
	const [ edit, toggleEdit, enableEdit, disableEdit ] = useToggle( ! isLabeled );
	const [ focusChanged, toggleFocusChange ] = useToggle( null );
	const isFocused = isElementFocused( editor, element );

	const replaceTag = ( newValue ) => {
		newValue = trimTag( newValue );
		const path = ReactEditor.findPath( editor, element );
		Transforms.removeNodes( editor, { at: [ ...path, 0 ] } );
		Transforms.insertNodes( editor, { text: newValue }, { at: [ ...path, 0 ] } );
		const isLabeled = ! isTagLabeled( newValue );
		if ( isLabeled ) {
			disableEdit();
		}
	};

	const removeTag = e => {
		e.stopPropagation();
		const path = ReactEditor.findPath( editor, element );
		Transforms.removeNodes( editor, { at: path } );
	};

	const focus = () => moveCursorToElement( editor, element );

	useEffect( () => {
		subscribe( 'taggableInput.focus', toggleFocusChange );
		return function cleanup() {
			unsubscribe( 'taggableInput.focus', toggleFocusChange );
		}
	} );

	return (
		// style={ { paddingTop: '0.175em', paddingBottom: '0.175em' } } onClick={ e => e.preventDefault() }
		<span { ...attributes } onClick={ focus } className={ "badge d-inline position-relative bg-info border-1 border-info pointer" + ( isFocused ? ' border-info-focus' : '' ) }>
			{ ( edit || ! isLabeled )
				? <>
					<span contentEditable={ false }>{ TAG_START_CHAR } </span>
					<InlineChromiumBugfix/>{ children }<InlineChromiumBugfix/>
					<span contentEditable={ false }> { TAG_END_CHAR }</span>
				</>
				: isFocused
					? <Tags callback={ replaceTag } autoClose trigger={ <TagsLabel tag={ TAG_START_CHAR + tag + TAG_END_CHAR } /> }/>
					: <span contentEditable={ false }><TagsLabel tag={ TAG_START_CHAR + tag + TAG_END_CHAR } /></span>
			}
			{ ( isFocused ) &&
			  <span contentEditable={ false } className="position-absolute top-0 left-0 btn-group btn-group-sm bg-info-subtle p-0 g-1" style={ { left: '50%', transform: "translate(-50%, -100%)" } }>
				  { isLabeled && <Icon btn icon={ edit ? 'unlock' : 'lock' } onClick={ toggleEdit } className="btn p-1 py-1 border-0 lh-1 align-text-top" /> }
				  <Tags callback={ replaceTag } autoClose trigger={ <Icon btn icon="edit" onClick={ toggleEdit } className="btn p-1 py-1 border-0 lh-1 align-text-top" /> }/>
				  <Icon btn icon="clear" onClick={ removeTag } className="btn p-1 py-1 border-0 lh-1 align-text-top" />
			  </span>
			}
        </span>
	);
};

const Element = ( { attributes, children, element, editor } ) => {
	return element.type === 'tag'
		? <TagElement attributes={ attributes } children={ children } element={ element } editor={ editor }/>
		: <span { ...attributes }>{ children }</span>;
};

const Leaf = ( { attributes, children, leaf } ) => (
	<span style={ leaf.text === '' ? { paddingLeft: '0.1px' } : undefined } { ...attributes }>{ children }</span>
);

// Plugin for tags: inline, auto-insert, normalize
const withTags = editor => {
	const { isInline, normalizeNode, insertText: origInsertText } = editor;
	editor.isInline = el => el.type === 'tag' || isInline( el );

	// Trigger tag creating when typing "{{"
	editor.insertText = text => {
		const { selection } = editor;
		origInsertText( text );

		if (
			text === TAG_START_CHAR.charAt( TAG_START_CHAR.length - 1 ) &&
			selection && Range.isCollapsed( selection ) &&
			! Editor.above( editor, { match: n => n.type === 'tag' } )
		) {
			setTimeout( () => {
				if ( ! editor.selection ) {
					return;
				}
				const { anchor } = editor.selection;
				const one = Editor.before( editor, anchor, { unit: 'character', distance: 1 } );
				const two = one && Editor.before( editor, one, { unit: 'character', distance: 1 } );
				if ( two && Editor.string( editor, { anchor: two, focus: anchor } ) === TAG_START_CHAR ) {
					Editor.withoutNormalizing( editor, () => {
						Transforms.delete( editor, { at: { anchor: two, focus: anchor } } );
						const tagNode = { type: 'tag', children: [ { text: ' ' } ] };
						Transforms.insertNodes( editor, tagNode, { select: true } );
						Transforms.collapse( editor, { edge: 'start' } );
					} );
					ReactEditor.focus( editor );
				}
			}, 0 );
		}
	};

	// Parse tags from strings.
	editor.normalizeNode = entry => {
		const [ node, path ] = entry;
		if ( Text.isText( node ) && path.length === 1 ) {
			const text = node.text;
			const parts = splitByTags( text );
			if ( parts.length > 1 ) {
				const nodes = partsToNodes( parts );
				Editor.withoutNormalizing( editor, () => {
					Transforms.removeNodes( editor, { at: path } );
					Transforms.insertNodes( editor, nodes, { at: path } );
				} );
				return; // skip further normalize on this entry
			}
		}
		normalizeNode( entry );
	};

	return editor;
};

const partsToNodes = parts => {
	const nodes = [];
	if ( ! parts.length ) {
		return nodes;
	}
	for ( const i in parts ) {
		const content = parts[ i ];

		if ( isTag( content ) ) {
			// Tag node (strip outer `{{` and `}}`)
			const raw = trimTag( content );
			nodes.push( { type: 'tag', children: [ { text: raw || '' } ] } );
		} else {
			// Text node
			nodes.push( { text: content || '' } );
		}
	}
	return nodes;
}

const textToNodes = value => {
	const parts = splitByTags( value );

	if ( ! parts.length ) {
		return [];
	}

	return partsToNodes( parts );
}

const parseValue = value => {
	const nodes = textToNodes( value );

	if ( ! nodes.length ) {
		return [ { type: 'paragraph', children: [ { text: value } ] } ];
	}

	// Ensure first node is text
	if ( nodes.length && nodes[0].type === 'tag' ) {
		nodes.unshift( { text: '' } );
	}
	// Ensure last node is text
	if ( nodes.length && nodes[nodes.length - 1].type === 'tag' ) {
		nodes.push( { text: '' } );
	}

	return [ { type: 'paragraph', children: nodes } ];
}

// Serialize Slate tree to raw string with tags
const serializeNode = node => {
	if ( ! node ) {
		return '';
	}
	if ( node.type === 'tag' ) {
		const textChild = node.children[ 0 ] || { text: '' };
		return `{{ ${ textChild.text } }}`;
	}
	if ( Text.isText( node ) ) {
		return node.text;
	}
	if ( node.children ) {
		return node.children.map( serializeNode ).join( '' );
	}
	return '';
};

const serialize = nodes => nodes.map( serializeNode ).join( '' );

// Main component
export default function TaggableInput( props ) {
	const {
		name,
		onChange,
		controlRef,
	} = props;

	const editor = useMemo( () => withTags( withHistory( withReact( createEditor() ) ) ), [] );
	const [ value, setValue ] = useState( parseValue( props.value ) );

	const handleChange = newValue => {
		const string = serialize( newValue );
		setValue( parseValue( string ) );
		onChange && onChange( string );
	};

	const handleCopy = e => {
		e.preventDefault();
		e.clipboardData.setData( 'text/plain', serialize( editor.getFragment() ) );
	};

	const style = {
		outline: 'none',
	};

	if ( controlRef && controlRef.editor !== editor ) {
		// Register editor and callbacks in controlRef
		controlRef.editor = editor;
		controlRef.callbacks = {
			set: newValue => {
				Transforms.delete( editor, {
					at: {
						anchor: Editor.start( editor, [] ),
						focus: Editor.end( editor, [] ),
					},
				} );
				Transforms.insertNodes( editor, parseValue( newValue ) );
			},
			insert: insertValue => {
				const parts = textToNodes( insertValue );
				parts.map( value => {
					if ( value.type !== 'tag' ) {
						if ( value.text ) {
							editor.insertText( value.text );
						}
						return;
					}

					const { selection } = editor;
					if ( Editor.above( editor, { match: n => n.type === 'tag' } ) ) {
						// Insert as subtag.
						editor.insertText( TAG_SUB_START_CHAR + ' ' + trimTag( value.children[ 0 ].text ) + ' ' + TAG_SUB_END_CHAR );
					} else {
						editor.insertNode( value );
						if ( selection ) {
							const after = Editor.after( editor, selection.focus, { unit: 'offset' } );
							if ( after ) {
								const next = Editor.node( editor, after );
								if ( next && next[ 0 ]?.type === 'tag' ) {
									// Insert empty text node in between tags
									Transforms.insertNodes( editor, { text: '' }, { at: after } );
								}
							} else {
								// No node after, append empty text
								Transforms.insertNodes( editor, { text: '' } );
							}
						}
					}
				} );
			},
		};
	}

	return (
		<div className={ mergeClassNames( props.className, 'form-control h-auto' ) }>
			<Slate editor={ editor } initialValue={ value } onChange={ handleChange }>
				<Editable
					renderElement={ props => <Element { ...props } editor={ editor }/> }
					renderLeaf={ Leaf }
					style={ style }
					placeholder={ props.placeholder }
					spellCheck={ props.spellCheck }
					autoFocus={ props.autoFocus }
					onCopy={ handleCopy }
				/>
			</Slate>
		</div>
	);
}
