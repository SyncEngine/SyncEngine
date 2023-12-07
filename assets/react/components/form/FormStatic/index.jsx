import React, { forwardRef, useContext, useEffect } from 'react';
import { createRefId } from "../../../utils/globals";
import { TagsContext } from '../../../context/TagsContext';
import { ParentContext } from '../../../context/ParentContext';

export default forwardRef( function FormStatic( props, ref ) {
	const {
		id,
		type,
		html,
		footer,
	} = props;

	const contextRef = createRefId();
	const tags = useContext( TagsContext );
	const parentContext = useContext( ParentContext );

	useEffect( () => {
		const form = document.querySelector( '#form_' + type + '_' + id + ' form' );
		form.id = contextRef;
		window.SyncEngine.forms.register( form );

		window.SyncEngine.context.register( contextRef, {
			tags: structuredClone( tags ),
			scope: structuredClone( parentContext.scope ?? [] ),
		} );

		return function cleanup() {
			window.SyncEngine.context.clear( contextRef );
		}
	}, [] );

	return (
		<>
			<div id={ 'form_' + type + '_' + id } ref={ ref } data-context={ contextRef } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
} )
