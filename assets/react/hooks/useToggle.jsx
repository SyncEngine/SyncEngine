import { useState } from 'react';
import { suppress } from '../utils/events';

/**
 * @param {boolean} initial
 * @param {function} enableCallback
 * @param {function} disableCallback
 * @returns {[boolean,toggle,enable,disable,((value: unknown) => void)]}
 */
export default function useToggle( initial, enableCallback = null, disableCallback = null ) {
	const [ bool, setBool ] = useState( initial );

	const toggle = ( e ) => {
		suppress( e );
		( bool ) ? disable( e ) : enable( e );
		return ! bool;
	}

	const disable = ( e ) => {
		suppress( e );
		setBool( false );

		if ( disableCallback ) {
			disableCallback();
		}
	}

	const enable = ( e ) => {
		suppress( e );
		setBool( true );

		if ( enableCallback ) {
			enableCallback();
		}
	}

	return [ bool, toggle, enable, disable, setBool ];
}
