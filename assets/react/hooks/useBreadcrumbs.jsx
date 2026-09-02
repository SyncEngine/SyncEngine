import useGlobal from './useGlobal';
import useSyncedState from './useSyncedState';

export default function useBreadcrumbs() {
	const screen = useGlobal( 'screen' );

	const updateGlobal = ( value ) => {
		if ( screen.breadcrumbs !== value ) {
			screen.breadcrumbs = value;
		}
		return true;
	}

	const getGlobal = () => {
		return screen.breadcrumbs ?? [];
	};

	return useSyncedState( 'breadcrumbs.update', getGlobal(), updateGlobal, getGlobal );
}
