import * as React from 'react';
import * as ReactDOMClient from 'react-dom/client';
import { Controller } from '@hotwired/stimulus';

import StepController from '../react/controllers/StepController';

const views = {
	step: StepController,
}

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

		if ( 'undefined' === typeof views[ type ] ) {
			return 'Could not find view';
		}

		const onChange = ( value ) => {
			this.element.value = JSON.stringify( value );
		};

		//<textarea {...this.element}></textarea>
	    ReactDOMClient.createRoot( root ).render(
			React.createElement( views[ type ], {
				args: ( 'string' === typeof args ) ? JSON.parse( args ) : args,
				value: ( 'string' === typeof this.element.value ) ? JSON.parse( this.element.value ) : this.element.value,
				onChange: onChange,
			} )
	    );
    }
}
