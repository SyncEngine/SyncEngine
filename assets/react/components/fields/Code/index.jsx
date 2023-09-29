import React, { useCallback, useContext, useEffect, useState } from 'react';
import { default as ReactCodeMirror } from '@uiw/react-codemirror';
import { createTheme } from '@uiw/codemirror-themes';
import { tags as t } from '@lezer/highlight';

import { Button } from 'react-bootstrap';
import Tags from '../../services/Tags';

import { TagsContext } from '../../../context/TagsContext';
import { subscribe, unsubscribe } from '../../../utils/events';

const themes = {
	light: {
		theme: 'light',
		fontSize: "11pt",
		settings: {
			background: 'transparent',
			foreground: '#212529',
			caret: '#151515',
			selection: '#036dd626',
			selectionMatch: '#036dd626',
			lineHighlight: '#8a91991a',
			gutterBackground: '#21252906',
			gutterForeground: '#21252966',
		},
		styles: [
			{ tag: t.comment, color: '#787b8099' },
			{ tag: t.variableName, color: '#3b3b3b' },
			{ tag: [t.string, t.special( t.brace )], color: '#5c6166' },
			{ tag: t.number, color: '#5c6166' },
			{ tag: t.bool, color: '#5c6166' },
			{ tag: t.null, color: '#5c6166' },
			{ tag: t.keyword, color: '#363636' },
			{ tag: t.operator, color: '#5c6166' },
			{ tag: t.className, color: '#363636' },
			{ tag: t.definition( t.typeName ), color: '#5c6166' },
			{ tag: t.typeName, color: '#5c6166' },
			{ tag: t.angleBracket, color: '#5c6166' },
			{ tag: t.tagName, color: '#5c6166' },
			{ tag: t.attributeName, color: '#5c6166' },
		],
	},
	dark: {
		theme: 'dark',
		fontSize: "11pt",
		settings: {
			background: 'transparent',
			foreground: '#adb5bd',
			caret: '#d9d9d9',
			selection: '#036dd626',
			selectionMatch: '#036dd626',
			lineHighlight: '#8a91991a',
			gutterBackground: '#adb5bd06',
			gutterForeground: '#adb5bd66',
		},
		styles: [
			{ tag: t.comment, color: '#787b8099' },
			{ tag: t.variableName, color: '#868686' },
			{ tag: [t.string, t.special(t.brace)], color: '#5c6166' },
			{ tag: t.number, color: '#5c6166' },
			{ tag: t.bool, color: '#5c6166' },
			{ tag: t.null, color: '#5c6166' },
			{ tag: t.keyword, color: '#949494' },
			{ tag: t.operator, color: '#5c6166' },
			{ tag: t.className, color: '#949494' },
			{ tag: t.definition(t.typeName), color: '#5c6166' },
			{ tag: t.typeName, color: '#5c6166' },
			{ tag: t.angleBracket, color: '#5c6166' },
			{ tag: t.tagName, color: '#5c6166' },
			{ tag: t.attributeName, color: '#5c6166' },
		],
	}
};

export default function Code( props ) {
	const {
		onChange,
		taggable,
	} = props;

	const [ theme, setTheme ] = useState( window.app.theme.getTheme() );

	const tags = taggable && useContext( TagsContext );

	useEffect( () => {
		function switchTheme() {
			setTheme( window.app.theme.getTheme() );
		}

		subscribe( 'updateTheme', switchTheme );
		return function cleanup() {
			unsubscribe( 'updateTheme', switchTheme );
		}
	}, [] );

	const handleChange = useCallback( value => {
		onChange( value );
	}, [ onChange, props.id, props.name ] );

	const onInsert = useCallback( value => {
		// @todo insert at cursor.
		handleChange( props.value + value );
	}, [ handleChange ] );

	// @todo only pass props that are needed.
	return (
		<div className="position-relative">
			{ tags &&
				<Tags tags={ tags } callback={ onInsert } trigger={ <Button variant="outline-secondary" size="sm" className="position-absolute top-0 end-0"><span className="bi bi-braces" /></Button> } />
			}
			<ReactCodeMirror { ...props } onChange={ handleChange } taggable={ null } attr={ null } theme={ createTheme( themes[ theme ] ?? '' ) } />
		</div>
	);
}
