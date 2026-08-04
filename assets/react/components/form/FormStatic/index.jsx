import React, { forwardRef, useContext, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import { Idiomorph } from 'idiomorph';
import useGlobal from '../../../hooks/useGlobal';
import { TagsContext } from '../../../context/TagsContext';
import { ParentContext } from '../../../context/ParentContext';
import { ContainerContext } from '../../../context/ContainerContext';
import { createRefId } from '../../../utils/globals';
import { deepClone } from '../../../utils/data';

const FormStatic = forwardRef( function FormStatic( props, ref ) {
	const app = useGlobal();
	const {
		type,
		html,
		footer,
		parentRef,
	} = props;
	const contextRefId = parentRef.current.key ?? createRefId();
	const tagsContext = useContext( TagsContext );
	const parentContext = useContext( ParentContext );
	const containerContext = useContext( ContainerContext );

	const containerRef = useRef( null );
	const prevHtmlRef = useRef( null );

	// Register context outside of effect to trigger before form init.
	app.context.register( contextRefId, {
		tags: deepClone( tagsContext ),
		scope: deepClone( parentContext.scope ?? [] ),
		container: containerContext.current,
	} );

	// Merge our internal container ref with whatever ref the parent passed in.
	const setRefs = ( node ) => {
		containerRef.current = node;
		if ( typeof ref === 'function' ) {
			ref( node );
		} else if ( ref ) {
			ref.current = node;
		}
	};

	function updateHtml( content ) {
		const container = containerRef.current;

		if ( ! prevHtmlRef.current ) {
			container.innerHTML = content;
			prevHtmlRef.current = content;
		} else if ( content !== prevHtmlRef.current ) {

			// Only morph the form contents to keep the form element reference.
			const template = document.createElement('template');
			template.innerHTML = content;
			const newForm = template.content.querySelector('form');

			Idiomorph.morph( container.querySelector('form'), newForm.innerHTML, {
				morphStyle: 'innerHTML',
				callbacks: {
					// Same rules as Turbo's data-turbo-permanent: leave anything
					// Stimulus owns alone so it isn't disconnected/reconnected.
					beforeNodeMorphed( oldNode ) {
						if ( oldNode.nodeType === 1 && oldNode.hasAttribute ) {
							if (
								oldNode.hasAttribute( 'data-controller' )
								||
								oldNode.hasAttribute( 'data-controlled-by' )
							) {
								return false;
							}
						}
						return true;
					},
					beforeNodeRemoved( node ) {
						if ( node.nodeType === 1 && node.hasAttribute ) {
							if (
								node.hasAttribute( 'data-controller' )
								||
								node.hasAttribute( 'data-controlled-by' )
							) {
								return false;
							}
						}
						return true;
					}
				},
			} );
			prevHtmlRef.current = content;
		}

		const form = container.querySelector( 'form' );

		if ( parentRef.current.element === form ) {
			return;
		}

		form.id = contextRefId;
		app.forms.register( form );
		parentRef.current.element = form;
		parentRef.current.submit = ( callback, params = {} ) => {
			parentRef.current.submitListener = function ( e ) {
				e.preventDefault();
				if ( this.checkValidity() ) {
					form.removeEventListener( 'submit', parentRef.current.submitListener );
					callback( {
						...params,
						form: form,
					} );
				}
				this.reportValidity();
			};
			form.removeEventListener( 'submit', parentRef.current.submitListener );
			form.addEventListener( 'submit', parentRef.current.submitListener, false );
			form.dispatchEvent( new Event( 'submit' ) );
		}
	}

	// Initial mount: inject markup once and register the form.
	useEffect( () => {
		updateHtml( html );
		return () => {
			app.context.clear( contextRefId );
			delete parentRef.current.element;
			delete parentRef.current.submit;
			delete parentRef.current.submitListener;
		}
	}, [ contextRefId, html ] );
	parentRef.current.updateHtml = updateHtml;

	return (
		<>
			<div key={ contextRefId } id={ 'form_' + type + '_' + contextRefId } ref={ setRefs } data-context={ contextRefId } />
			{ footer }
		</>
	);
} );

FormStatic.propTypes = {
	type: PropTypes.string,
	html: PropTypes.string,
	footer: PropTypes.node,
	parentRef: PropTypes.object,
};
export default FormStatic;
