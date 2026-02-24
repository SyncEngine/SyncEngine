import React, { useCallback, useContext, useEffect, useState } from 'react';
import { bool, func, object, string } from 'prop-types';
import { default as ReactCodeMirror } from '@uiw/react-codemirror';
// @todo Dynamically load languages from separate public files isntead of including them in module export.
import { loadLanguage } from '@uiw/codemirror-extensions-langs';
import { createTheme } from '@uiw/codemirror-themes';
import { tags as t } from '@lezer/highlight';

import Tags from '../../services/Tags';
import Button from '../../partials/Button';

import { TagsContext } from '../../../context/TagsContext';
import { subscribe, unsubscribe } from '../../../utils/events';
import useGlobal from '../../../hooks/useGlobal';

import './style.scss';
import Icon from '../../partials/Icon';
import { isFieldEditable } from '../../../utils/conditions';

const themes = {
	light: {
		theme: 'light',
		settings: {
			background: 'var(--bs-tertiary-bg)',
			foreground: '#212529',
			caret: '#151515',
			selection: '#036dd626',
			selectionMatch: '#036dd626',
			lineHighlight: '#8a91991a',
			gutterBackground: '#f8f9fa',
			gutterForeground: '#5c6166',
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
		settings: {
			background: 'var(--bs-tertiary-bg)',
			foreground: '#adb5bd',
			caret: '#d9d9d9',
			selection: '#036dd626',
			selectionMatch: '#036dd626',
			lineHighlight: '#8a91991a',
			gutterBackground: '#1a1d21',
			gutterForeground: '#5c6166',
		},
		styles: [
			{ tag: t.comment, color: '#787b8099' },
			{ tag: t.variableName, color: '#868686' },
			{ tag: t.special(t.brace), color: '#5c6166' },
			{ tag: t.string, color: '#868686' },
			{ tag: t.number, color: '#868686' },
			{ tag: t.bool, color: '#868686' },
			{ tag: t.null, color: '#868686' },
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
	const app = useGlobal();
	const editable = isFieldEditable( props );
	const {
		onChange,
		contained = false,
		taggable = false,
	} = props;

	const [ theme, setTheme ] = useState( app.theme.getTheme() );

	const tagsContext = useContext( TagsContext );
	const tags = ( editable && props.taggable ) && tagsContext;

	useEffect( () => {
		function switchTheme() {
			setTheme( app.theme.getTheme() );
		}

		subscribe( 'updateTheme', switchTheme );
		return function cleanup() {
			unsubscribe( 'updateTheme', switchTheme );
		}
	}, [] );

	const handleChange = useCallback( value => {
		if ( ! editable ) return;
		onChange( value );
	}, [ onChange, editable ] );

	const onInsert = useCallback( value => {
		if ( ! editable ) return;
		// @todo insert at cursor.
		handleChange( props.value + value );
	}, [ handleChange, editable ] );

	// @todo only pass props that are needed.
	return (
		<div className={ "position-relative" + ( contained ? ' code-contained' : '' ) }>
			{ tags &&
				<Tags tags={ tags } callback={ onInsert } trigger={ <Button variant="secondary" outline size="sm" className="position-absolute top-0 end-0 z-1"><Icon icon="tag" /></Button> } />
			}
			<ReactCodeMirror
				{ ...props }
				className={ 'small' + ( props.className ? ' ' + props.className : '' ) }
				basicSetup={ {
					lineNumbers: true,
					foldGutter: true,
					allowMultipleSelections: true,
					editable: props.editable ?? true,
					...( props.basicSetup ?? {} )
				} }
				onChange={ handleChange }
				taggable={ null }
				contained={ null }
				editable={ editable }
				disabled={ props.disabled }
				attr={ null }
				theme={ createTheme( themes[ theme ] ?? '' ) }
				// @todo useMemo?
				extensions={ props.language ? [ loadLanguage( props.language ) ] : undefined }
			/>
		</div>
	);
}

Code.propTypes = {
	value: string,
	default: string,
	required: bool,
	onChange: func,
	contained: bool,
	taggable: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	basicSetup: object,
};
