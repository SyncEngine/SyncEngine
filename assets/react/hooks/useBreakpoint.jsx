import React, { useCallback, useEffect, useState } from 'react';
import useModels from './useModels';
import useGlobal from './useGlobal';

export default function useBreakpoint( breakpoint ) {
	const breakpoints = useGlobal( 'breakpoints' );
	const [ match, setMatch ] = useState( breakpoints.match( breakpoint ) );

	const callback = useCallback( ( matches ) => {
		setMatch( matches );
	}, [ setMatch ] );

	// Subscribe
	breakpoints.subscribe( breakpoint, callback );

	useEffect( () => {
		return function cleanup() {
			breakpoints.unsubscribe( breakpoint, callback );
		}
	}, [] );

	return match;
}
