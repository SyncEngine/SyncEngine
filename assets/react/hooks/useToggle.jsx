import { useState } from 'react';

/**
 * @param {boolean} initial
 * @param {function} enableCallback
 * @param {function} disableCallback
 * @returns {[boolean,toggle,enable,disable,((value: unknown) => void)]}
 */
export default function useToggle( initial, enableCallback = null, disableCallback = null ) {
	const [ bool, setBool ] = useState( initial );

	const toggle = ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}

		( bool ) ? disable( e ) : enable( e );
		return ! bool;
	}

	const disable = ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setBool( false );

		if ( disableCallback ) {
			disableCallback();
		}
	}

	const enable = ( e ) => {
		if ( e && 'function' === typeof e.preventDefault ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setBool( true );

		if ( enableCallback ) {
			enableCallback();
		}
	}

	return [ bool, toggle, enable, disable, setBool ];
}
