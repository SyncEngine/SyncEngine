import * as React from 'react';
import * as ReactDOMClient from 'react-dom/client';
import { Controller } from '@hotwired/stimulus';
import { Loader } from '../components/Loader';

export default class extends Controller {
    connect() {
	    const root = ReactDOMClient.createRoot( this.element );
	    root.render( <Loader {...this.context} /> );
    }
}
