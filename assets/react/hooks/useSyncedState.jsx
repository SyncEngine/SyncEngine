import { useCallback, useEffect, useRef, useState } from 'react';
import { publish, subscribe, unsubscribe } from '../utils/events';
import { isSet } from '../utils/conditions';

/**
 * @param {string} eventName
 * @param {*} initial
 * @param {function} publishCallback
 * @param {function} fetchCallback
 * @returns {*,function,boolean}
 */
export default function useSyncedState( eventName, initial, publishCallback = null, fetchCallback = null ) {

	const [ state, setState ] = useState( initial );

	// Use refs to track the latest callbacks without triggering resubscription loops.
	const fetchCallbackRef = useRef( fetchCallback );
	const publishCallbackRef = useRef( publishCallback );

	// Ensure latest state of callbacks is always used in the effect and update function.
	fetchCallbackRef.current = fetchCallback;
	publishCallbackRef.current = publishCallback;

	const update = useCallback( ( state, force, silent ) => {
		if ( ! force && ! isSet( state ) ) {
			return;
		}

		let success = true;
		if ( 'function' === typeof publishCallbackRef.current && ! silent ) {
			success = publishCallbackRef.current( state );
		}
		if ( success ) {
			publish( eventName, state );
		}
	}, [ eventName ] );

	useEffect( () => {
		setState( initial );

		const callback = async ( data ) => {
			// Always use the latest fetchCallback from the ref
			if ( 'function' === typeof fetchCallbackRef.current ) {
				setState( await fetchCallbackRef.current() );
			} else {
				setState( data.detail );
			}
		};

		subscribe( eventName, callback );

		return () => {
			unsubscribe( eventName, callback );
		}
	}, [ eventName ] ); // Only resubscribe when the event name changes

	return [ state, update, publish ];
}
