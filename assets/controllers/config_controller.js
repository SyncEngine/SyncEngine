import * as React from 'react';
import * as ReactDOMClient from 'react-dom/client';
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
	    const root = ReactDOMClient.createRoot( this.element );

	    console.log( this.context );
	    root.render( <hr /> );
    }
}
