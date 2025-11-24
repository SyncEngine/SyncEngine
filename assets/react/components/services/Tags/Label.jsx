import React, { forwardRef, useContext } from 'react';
import { getTagParts, isTag, TAG_END_CHAR, TAG_SEPARATOR, TAG_START_CHAR } from '../../../utils/tags';
import { TagsContext } from '../../../context/TagsContext';
import { isEmpty } from '../../../utils/conditions';
import { ReactNode } from 'react';

export function isTagLabeled( tag, tags = {} ) {
	const parts = getTagParts( tag );
	let context = tags;
	for ( let i = 0; i < parts.length; i++ ) {
		if ( context.hasOwnProperty( parts[i] ) ) {
			if ( context[ parts[i] ]._tag ) {
				if ( ! isEmpty( context[ parts[i] ].label ) ) {
					return true;
				}
				context = context[ parts[i] ]._children ?? {};
			}
			context = context[ parts[i] ];
		} else {
			break;
		}
	}
	return false;
}

export default forwardRef( function TagsLabel( props, ref ) {
	const tags = useContext( TagsContext );

	const {
		startChar = TAG_START_CHAR + ' ',
		endChar = ' ' + TAG_END_CHAR,
		separator = TAG_SEPARATOR,
		parent,
		postfix,
		label,
	} = props;

	let refProps = { ...props };
	delete refProps.startChar;
	delete refProps.endChar;
	delete refProps.separator;
	delete refProps.parent;
	delete refProps.postfix;
	delete refProps.label;
	delete refProps.tag;

	let prefix = props.prefix ?? parent;
	let tag = props.label ?? props.tag;
	let title = props.title ?? tag;

	if ( isTag( tag ) && isEmpty( prefix ) && isEmpty( label ) ) {
		const parts = getTagParts( tag );
		tag = parts.pop();
		prefix = '';
		let context = tags;
		for ( let i = 0; i < parts.length; i++ ) {
			if ( context.hasOwnProperty( parts[i] ) ) {
				if ( context[ parts[i] ].hasOwnProperty( '_tag' ) ) {
					if ( ! isEmpty( context[ parts[i] ].label ) ) {
						prefix += context[ parts[i] ].label + separator;
					} else {
						prefix += parts[i] + separator;
					}
					context = context[ parts[i] ]._children ?? {};
				} else {
					context = context[ parts[i] ];
					prefix += parts[i] + separator;
				}
			}
		}

		if ( context.hasOwnProperty( tag ) ) {
			if ( context[ tag ].hasOwnProperty( '_tag' ) && ! isEmpty( context[ tag ].label ) ) {
				tag = context[ tag ].label;
			}
		}
	}

	const style = {
		whiteSpace: 'pre'
	}

	if ( refProps.onClick ) {
		style.cursor = 'pointer';
	}

	return (
		<span { ...refProps } title={ title } style={ { ...style, ...( refProps.style ?? {} ) } }>
			<span>{ startChar }</span>
			{ prefix && React.isValidElement( prefix ) ? prefix : <span className="opacity-50">{ prefix }</span> }
			<span>{ tag }</span>
			{ postfix }
			<span>{ endChar }</span>
		</span>
	);
} );
