import useGlobal from './useGlobal';
import useMediaQuery from './useMediaQuery';

export default function useBreakpoint( breakpoint ) {
	const breakpoints = useGlobal( 'breakpoints' );
	const query = '(min-width: ' + breakpoints.get( breakpoint ) + 'px)';

	return useMediaQuery( query );
}
