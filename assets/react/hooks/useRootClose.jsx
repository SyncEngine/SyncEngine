import { useEffect, useRef } from 'react';

/**
 * @param callback
 * @param condition
 * @returns {React.MutableRefObject<null>}
 */
export default function useRootClose( callback, condition = true ) {
	const root = useRef( null );

	useEffect( () => {
		function handleClickOutside( event ) {
			if ( condition && root.current && ! root.current.contains( event.target ) ) {
				callback();
			}
		}
		// Bind the event listener
		document.addEventListener("mousedown", handleClickOutside );
		return () => {
			// Unbind the event listener on clean up
			document.removeEventListener("mousedown", handleClickOutside );
		};
	}, [ root, condition, callback ] );

	return root;
}
