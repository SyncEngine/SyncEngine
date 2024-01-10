import { useState } from 'react';

/**
 * @param {boolean} initial
 * @returns {[boolean,toggle,enable,disable,((value: unknown) => void)]}
 */
export default function useToggle( initial ) {
	const [ bool, setBool ] = useState( initial );

	const toggle = ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setBool( ! bool );
	}

	const disable = ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setBool( false );
	}

	const enable = ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		setBool( true );
	}

	return [ bool, toggle, enable, disable, setBool ];
}
