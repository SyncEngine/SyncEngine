import React, { useCallback, useState } from 'react';
import PaginationToolbar from '../components/partials/PaginationToolbar';

export default function usePagination( list = [], initialLimit = 10, initialPage = 1, autoHide = true ) {
	const [ items, setItems ] = useState( list );
	const [ page, setPage ] = useState( initialPage );
	const [ limit, setLimit ] = useState( initialLimit );
	const [ length, setLength ] = useState( initialLimit );

	const totalItems = items.length;
	const index = page - 1;
	const offset = index * limit;

	const numPages = Math.floor( ( totalItems - 1 ) / limit ) + 1;
	const currentPage = ( offset ) ? Math.floor( offset / limit ) + 1 : 1;

	const firstPage = useCallback( () => {
		setPage( 1 );
	}, [ setPage ] );

	const lastPage = useCallback( () => {
		setPage( numPages );
	}, [ setPage, numPages ] );

	const toPage = useCallback( ( page ) => {
		setPage( Math.max( 1, parseInt( page, 10 ) ) );
	}, [ setPage ] );

	const loadMore = useCallback( () => {
		setLength( length + limit );
	}, [ limit, length, setLength ] );

	const updateLimit = useCallback( ( newLimit ) => {
		newLimit = parseInt( newLimit, 10 );

		setLimit( newLimit );
		setLength( newLimit );

		// Recalculate current page.
		let currentPage = Math.floor( offset / newLimit ) + 1;
		if ( 0 >= currentPage ) {
			currentPage = 1;
		} else {
			let numPages = Math.floor( ( totalItems - 1 ) / newLimit ) + 1;
			if ( numPages < currentPage ) {
				currentPage = numPages;
			}
		}
		setPage( currentPage );
	}, [ setLimit, setLength, setPage, offset, totalItems ] );

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
