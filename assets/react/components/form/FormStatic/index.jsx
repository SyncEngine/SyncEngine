import React, { useContext, useEffect } from 'react';
import { createRefId } from "../../../utils/globals";
import { TagsContext } from '../../../context/TagsContext';
import { EntityContext } from '../../../context/EntityContext';
import { ParentContext } from '../../../context/ParentContext';

export default function FormStatic( props ) {
	const {
		id,
		entity,
		html,
		footer,
	} = props;

	const contextRef = createRefId();
	const tags = useContext( TagsContext );
	const parentContext = useContext( ParentContext );

	useEffect( () => {
		const form = document.querySelector( '#form_' + entity + '_' + id + ' form' );
		form.id = contextRef;
		window.app.forms.register( form );

		window.app.context.register( contextRef, {
			tags: structuredClone( tags ),
			scope: structuredClone( parentContext.scope ?? [] ),
		} );

		return function cleanup() {
			window.app.context.clear( contextRef );
		}
	}, [] );

	return (
		<>
			<div id={ 'form_' + entity + '_' + id } data-context={ contextRef } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
}
