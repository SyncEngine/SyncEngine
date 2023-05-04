import * as React from 'react';
import * as ReactDOMClient from 'react-dom/client';
import { Controller } from '@hotwired/stimulus';
import { StepView } from './views/StepView';

const views = {
	step: StepView,
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
			config,
		} = this.element.dataset;

		//<textarea {...this.element}></textarea>
	    ReactDOMClient.createRoot( root ).render(
			React.createElement( views[ type ], {
				config: config
			} )
	    );
    }
}
