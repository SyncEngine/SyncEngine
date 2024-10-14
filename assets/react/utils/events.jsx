/**
 * @param {string} eventName
 * @param {function} listener
 */
function subscribe( eventName, listener ) {
	document.addEventListener( eventName, listener );
}

/**
 * @param {string} eventName
 * @param {function} listener
 */
function unsubscribe( eventName, listener ) {
	document.removeEventListener( eventName, listener );
}

/**
 * @param {string} eventName
 * @param {*} data
 */
function publish( eventName, data ) {
	const event = new CustomEvent( eventName, { detail: data } );
	document.dispatchEvent( event );
}

/**
 * Cancels default event actions and bubbling.
 * @param {Event} e
 */
function suppress( e ) {
	if ( e instanceof Event ) {
		e.preventDefault();
		e.stopPropagation();
	}
}

/**
 * @param {function} callback
 * @param {int} delay
 * @returns {(function(...[*]): void)|*}
 */
function debounce( callback, delay = 250 ) {
	let timeout;

	return ( ...args ) => {
		clearTimeout( timeout );
		timeout = setTimeout( () => {
			callback( ...args );
		}, delay );
	};
}

export {
	publish,
	subscribe,
	unsubscribe,
	suppress,
	debounce,
};
