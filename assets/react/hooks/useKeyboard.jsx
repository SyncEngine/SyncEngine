import { useCallback, useEffect } from 'react';

/**
 * @param {string|object} keys
 * @param {function} callback
 * @param {element} ref
 * @param {string} eventType
 */
export default function useKeyboard( keys, callback, ref, eventType = 'keyup' ) {

	const element = ref ? ref.current : document;

	const handleEvent = useCallback( ( event ) => {

		let key = event.key;
		if ( event.ctrlKey ) {
			key = 'CTRL+' + key;
		}

		if ( Array.isArray( keys ) && keys.includes( event.key ) ) {
			event.stopPropagation();
			callback();
		} else if ( event.key === keys ) {
			event.stopPropagation();
			callback();
		}
	}, [ keys, callback ] );

	useEffect( () => {
		if ( ! element || ! element.hasOwnProperty( 'addEventListener' ) ) {
			return;
		}

		element.addEventListener( eventType, handleEvent, false );

		return () => {
			element.removeEventListener( eventType, handleEvent, false );
		};
	}, [ handleEvent, element ] );
}
