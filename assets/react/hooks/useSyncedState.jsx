import { useEffect, useState } from 'react';
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

	const update = ( state, force, silent ) => {
		if ( ! force && ! isSet( state ) ) {
			return;
		}

		let success = true;
		if ( 'function' === typeof publishCallback && ! silent ) {
			success = publishCallback( state );
		}
		if ( success ) {
			publish( eventName, state );
		}
	}

	useEffect( () => {
		setState( initial );

		const callback = async ( data ) => {
			if ( 'function' === typeof fetchCallback ) {
				setState( await fetchCallback() );
			} else {
				setState( data.detail );
			}
		};

		subscribe( eventName, callback );

		return () => {
			unsubscribe( eventName, callback );
		}
	}, [ eventName ] );

	return [ state, update, publish ];
}
