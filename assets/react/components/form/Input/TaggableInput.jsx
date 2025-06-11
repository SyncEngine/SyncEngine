import React, { useMemo, useState } from 'react';

import { createEditor, Editor, Range, Text, Transforms } from 'slate';
import { Editable, ReactEditor, Slate, withReact } from 'slate-react';
import { withHistory } from 'slate-history';

import Icon from '../../partials/Icon';

import { isTag, splitByTags, TAG_START_CHAR, TAG_SUB_END_CHAR, TAG_SUB_START_CHAR, trimTag } from '../../../utils/tags';
import { mergeClassNames } from '../../../utils/props';

// Add zero-width spaces around editable children to avoid Chrome cursor bugs
const InlineChromiumBugfix = () => (
	<span contentEditable={ false } style={ { fontSize: 0 } }>
		{ String.fromCodePoint( 160 ) /* Non-breaking space */ }
	</span>
);

// Renders a tag badge with an editable text child
const TagElement = ( { attributes, children, element, editor } ) => {
	const openTagModal = () => {
		// @todo.
		const current = element.children[ 0 ]?.text || '';
		const newValue = window.prompt( 'Edit tag:', current );
		if ( newValue != null ) {
			const path = ReactEditor.findPath( editor, element );
			Transforms.setNodes( editor, { children: [ { text: newValue } ] }, { at: [ ...path, 0 ] } );
		}
	};

	const removeTag = e => {
		e.stopPropagation();
		const path = ReactEditor.findPath( editor, element );
		Transforms.removeNodes( editor, { at: path } );
	};

	return (
		<span { ...attributes } onClick={ e => e.preventDefault() } className="badge d-inline bg-info pointer">
			<Icon btn icon="tag" onClick={ openTagModal } className="me-1 ms-n1 mt-n1 btn p-0 border-0"/>
			<InlineChromiumBugfix/>{ children }<InlineChromiumBugfix/>
			<Icon btn icon="clear" onClick={ removeTag } className="ms-1 me-n1 mt-n1 btn p-0 border-0"/>
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

function partsToNodes( parts ) {
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

function parseValue( value ) {
	const parts = splitByTags( value );

	if ( ! parts.length ) {
		return [ { type: 'paragraph', children: [ { text: value } ] } ];
	}

	return partsToNodes( parts );
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

	const handleUpdate = newValue => {
		newValue = parseValue( newValue );
		setValue( newValue );
		editor.setValue( newValue );
	};

	const handleChange = newValue => {
		const string = serialize( newValue );
		setValue( parseValue( string ) );
		onChange && onChange( string );
	};

	const handleInsert = insertValue => {
		const parts = parseValue( insertValue );
		parts.map( value => {
			if ( value.type !== 'tag' ) {
				editor.insertText( value.text );
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
	};

	const style = {
		outline: 'none',
	};

	if ( controlRef ) {
		controlRef.editor = editor;
		controlRef.callbacks = { insert: handleInsert };
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
				/>
			</Slate>
		</div>
	);
}
