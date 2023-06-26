import * as React from 'react';
import * as ReactDOMClient from 'react-dom/client';
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    connect() {
	    let root = this.element;

	    switch ( this.element.type ) {
		    case 'textarea':
		    case 'input':
			    root = document.createElement('div');
				root.id = this.element.id + '_root';
				// @todo Hide textarea?
				this.element.after( root );
				break;
		    default:
				// Wrong element.
				return;
				break;
	    }

		const {
			type,
			args,
		} = this.element.dataset;

	    const Controller = window.resolveReactComponent( type.charAt(0).toUpperCase() + type.slice(1) + 'Controller' );

		const setValue = ( value ) => {
			this.element.value = JSON.stringify( value );
		};

	    const getElement = () => React.createElement( Controller, {
		    args: ( 'string' === typeof args ) ? JSON.parse( args ) : args,
		    value: ( 'string' === typeof this.element.value ) ? JSON.parse( this.element.value ) : this.element.value,
		    element: this.element,
		    onChange: setValue,
	    } );

		let reactRoot = null;

		const render = () => {
			if ( reactRoot ) {
				reactRoot.unmount();
			}
			reactRoot = ReactDOMClient.createRoot( root );
			reactRoot.render( getElement() );
		}

	    // Manual update.
	    this.element.onchange = ( event ) => {
			setValue( JSON.parse( event.target.value ) );
		    render();
	    }

	    render();
    }
}
