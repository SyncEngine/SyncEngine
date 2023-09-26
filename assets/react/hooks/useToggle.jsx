import { useState } from 'react';

/**
 * @param {boolean} initial
 * @returns {[boolean,toggle,enable,disable,((value: unknown) => void)]}
 */
export default function useToggle( initial ) {
	const [ bool, setBool ] = useState( initial );

	const toggle = () => {
		setBool( ! bool );
	}

	const disable = () => {
		setBool( false );
	}

	const enable = () => {
		setBool( true );
	}

	return [ bool, toggle, enable, disable, setBool ];
}
