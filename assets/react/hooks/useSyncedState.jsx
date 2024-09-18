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

	const [ state, setValue ] = useState( initial );

	const update = ( state, force ) => {
		if ( ! force && ! isSet( state ) ) {
			return;
		}

		let success = true;
		if ( 'function' === typeof publishCallback ) {
			success = publishCallback( state );
		}
		if ( success ) {
			publish( eventName, state );
		}
	}

	useEffect( () => {
		const callback = async ( data ) => {
			if ( 'function' === typeof fetchCallback ) {
				setValue( await fetchCallback() );
			} else {
				setValue( data.detail );
			}
		};

		subscribe( eventName, callback );

		return () => {
			unsubscribe( eventName, callback );
		}
	}, [] );

	return [ state, update, publish ];
}
