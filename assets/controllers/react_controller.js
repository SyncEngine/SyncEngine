import React from 'react';
import ReactDOMClient from 'react-dom/client';
import { Controller } from '@hotwired/stimulus';
import { ElementContext } from '../react/context/ElementContext';
import { ParentContext } from '../react/context/ParentContext';

export default class extends Controller {

	disconnect() {
		this.reactRoot.unmount();
		super.disconnect();
	}

	connect() {
		let root = this.element;
		let labelElement;

		switch ( this.element.tagName.toLowerCase() ) {
			case 'textarea':
			case 'input':
				root = document.createElement( 'div' );
				root.id = this.element.id + '_root';
				root.classList.add( 'react-root' );

				if ( this.element.nextSibling.tagName && 'label' === this.element.nextSibling.tagName.toLowerCase() ) {
					this.element.nextSibling.after( root );
				} else {
					this.element.after( root );
				}

				labelElement = document.querySelector( 'label[for="' + this.element.id + '"]' );

				if ( window.SyncEngine.debug ) {
					this.element.classList.add( 'mb-3' );
					this.element.classList.add( 'text-secondary' );
					this.element.classList.add( 'text-hover-secondary-emphasis' );
				} else {
					this.element.style.display = 'none';
					labelElement.style.display = 'none';
				}

				break;
			default:
				// Wrong element.
				return;
		}

		const {
			type,
			args,
			prop,
		} = this.element.dataset;

		const Controller = window.resolveReactComponent( type.charAt( 0 ).toUpperCase() + type.slice( 1 ) + 'Controller' );

		const setValue = ( value ) => {
			if ( 'object' === typeof value ) {
				value = JSON.stringify( value );
			}
			this.element.value = value;
		};

		const parseParams = ( value ) => {
			if ( 'string' !== typeof value ) {
				return value;
			}

			try {
				return JSON.parse( value );
			} catch ( $e ) {
				return value;
			}
		};

		const getElement = () => React.createElement( Controller, {
			args: parseParams( args ),
			value: parseParams( this.element.value ),
			element: this.element,
			label: labelElement && labelElement.textContent,
			prop: prop,
			onChange: setValue,
		} );

		this.reactRoot = null;

		const render = () => {
			if ( this.reactRoot ) {
				this.reactRoot.unmount();
			}
			this.reactRoot = ReactDOMClient.createRoot( root );

			const contextParent = this.element.closest( '[data-context]' );
			const context = contextParent && window.SyncEngine.context.get( contextParent.dataset.context );

			this.reactRoot.render(
				<ParentContext.Provider value={ context ?? {} }>
					<ElementContext.Provider value={ this.element }>
						{ getElement() }
					</ElementContext.Provider>
				</ParentContext.Provider>
			);
		};

		// Manual update.
		this.element.onchange = ( event ) => {
			setValue( parseParams( event.target.value ) );
			render();
		};

		render();
	}
}
