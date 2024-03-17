import { useState } from 'react';
import { publish, subscribe } from '../utils/events';
import { isSet } from '../utils/conditions';

/**
 * @param {string} eventName
 * @param {*} initial
 * @param {function} publishCallback
 * @param {function} fetchCallback
 * @returns {*,function,boolean}
 */
export default function useSyncedState( eventName, initial, publishCallback = null, fetchCallback = null ) {

	const [ state, setValue ] = useState( initial );

	const update = ( state ) => {
		if ( ! isSet( state ) ) {
			return;
		}

		let success = true;
		if ( 'function' === typeof fetchCallback ) {
			success = publishCallback( state );
		}
		if ( success ) {
			publish( eventName, state );
		}
	}

	subscribe( eventName, async ( data ) => {
		if ( 'function' === typeof fetchCallback ) {
			setValue( await fetchCallback() );
		} else {
			setValue( data.detail );
		}
	} );

	return [ state, update ];
}
