import { useCallback, useEffect, useRef, useState } from 'react';

export default function useMediaQuery( query ) {
	const register = useRef( {} );
	const [ mediaQuery ] = useState( () => {
		if ( ! register.current.hasOwnProperty( query ) ) {
			register.current[ query ] = {
				mediaQuery: window.matchMedia( query ),
				callbacks: [],
				listener: null,
			};
		}
		return register.current[ query ].mediaQuery;
	} );
	const [ match, setMatch ] = useState( mediaQuery.matches );

	const callback = useCallback( ( matches ) => {
		setMatch( matches );
	}, [] );

	useEffect( () => {
		const entry = register.current[ query ];

		if ( ! entry.listener ) {
			entry.listener = ( e ) => {
				entry.callbacks.forEach( ( cb ) => cb( e.matches ) );
			};
			mediaQuery.addEventListener( 'change', entry.listener );
		}

		entry.callbacks.push( callback );

		return () => {
			const index = entry.callbacks.indexOf( callback );
			if ( -1 !== index ) {
				entry.callbacks.splice( index, 1 );
			}

			if ( entry.callbacks.length === 0 && entry.listener ) {
				mediaQuery.removeEventListener( 'change', entry.listener );
				entry.listener = null;
			}
		};
	}, [ callback, query ] );

	return match;
}
