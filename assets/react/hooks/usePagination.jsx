import React, { useCallback, useState } from 'react';
import PaginationToolbar from '../components/partials/PaginationToolbar';

export default function usePagination( list = [], initialLimit = 10, initialPage = 1, autoHide = true ) {
	const [ items, setItems ] = useState( list );
	const [ page, setPage ] = useState( initialPage );
	const [ limit, setLimit ] = useState( initialLimit );
	const [ length, setLength ] = useState( initialLimit );

	const totalItems = items.length;
	const index = page - 1;
	const offset = index * length;

	const numPages = parseInt( ( totalItems - 1 ) / length ) + 1;
	const currentPage = ( offset ) ? parseInt( offset / length, 10 ) + 1 : 1;

	const firstPage = useCallback( () => {
		setPage( 1 );
	}, [ items, setPage ] );

	const lastPage = useCallback( () => {
		setPage( numPages );
	}, [ items, setPage, numPages ] );

	const toPage = useCallback( ( page ) => {
		setPage( page );
	}, [ items, setPage ] );

	const loadMore = useCallback( () => {
		setLength( length + limit );
	}, [ items, limit, length, setLength ] );

	const updateLimit = useCallback( ( newLimit ) => {
		setLimit( newLimit );
		setLength( newLimit );
	}, [ items, setLimit, setLength ] );

	if ( autoHide && totalItems < initialLimit ) {
		return { items: items };
	}

	const currentItems = items.slice( offset, offset + length );

	if ( ! currentItems.length && page > 1 ) {
		// Fix page number if we've gone too far.
		setPage( page - 1 );
	}

	return {
		items: currentItems,
		setItems: setItems,
		toolbar: <PaginationToolbar
			size="sm"
			pages={ numPages }
			current={ currentPage }
			limit={ limit }
			defaultLimit={ initialLimit }
			offset={ offset }
			totalItems={ totalItems }
			numItems={ currentItems.length }
			callbackFirstPage={ firstPage }
			callbackLastPage={ lastPage }
			callbackToPage={ toPage }
			callbackLoadMore={ loadMore }
			callbackSetLimit={ updateLimit }
		/>
	}
}
