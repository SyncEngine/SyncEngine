import * as React from 'react';
import * as ReactDOMClient from 'react-dom/client';
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
	    this.element.textContent = 'Admin Controller';
	    //const root = ReactDOMClient.createRoot( this.element );
	    //root.render( <ChakraLoader {...this.context} /> );
    }
}
