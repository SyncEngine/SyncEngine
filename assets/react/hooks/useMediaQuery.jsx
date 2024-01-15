import React, { useCallback, useEffect, useRef, useState } from 'react';
import useModels from './useModels';
import useGlobal from './useGlobal';

export default function useMediaQuery( query ) {
	const register = useRef( {} );
	const [ mediaQuery ] = useState( register.current.hasOwnProperty( query ) ? register.current[ query ].mediaQuery : window.matchMedia( query ) );
	const [ match, setMatch ] = useState( mediaQuery.matches );

	if ( ! register.current.hasOwnProperty( query ) ) {
		register.current[ query ] = {
			mediaQuery: mediaQuery,
			callbacks: [],
		};
	}

	const onchange = useCallback( ( e ) => {
		register.current[ query ].callbacks.forEach( function( callback ) {
			callback( e.matches );
		} );
	}, [ query ] );

	const callback = useCallback( ( matches ) => {
		setMatch( matches );
	}, [ setMatch ] );

	const subscribe = useCallback( ( query, callback ) => {
		if ( ! register.current[ query ].callbacks.includes( callback ) ) {
			register.current[ query ].callbacks.push( callback );
		}
	}, [] );

	const unsubscribe = useCallback( ( query, callback ) => {
		var index = register.current[ query ].callbacks.indexOf( callback );
		if ( -1 !== index ) {
			register.current[ query ].callbacks.splice( index, 1 );
		}
	}, [] );

	// Set listener.
	if ( mediaQuery.onchange !== onchange ) {
		mediaQuery.onchange = onchange;
	}

	// Subscribe
	subscribe( query, callback );

	useEffect( () => {
		return function cleanup() {
			unsubscribe( query, callback );
		}
	}, [] );

	return match;
}
