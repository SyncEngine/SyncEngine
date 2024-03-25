import React, { useCallback } from 'react';
import { Pagination } from 'react-bootstrap';

export default function PaginationPager( props ) {
	const {
		callbackFirstPage,
		callbackLastPage,
		callbackToPage,
		pages,
		current,
		size,
		className = 'm-0',
		range = 2,
		variant,
	} = props;

	const pageLinks = useCallback( ( current, range, pages, callback ) => {
		const minPage = ( 0 < current - range ) ? current - range : 1;
		const maxPage = ( pages > current + range ) ? current + range : pages;

		const links = [];
		for ( let pageNumber = minPage; pageNumber <= maxPage; pageNumber++ ) {
			links.push(
				<Pagination.Item active={ ( current === pageNumber ) } key={ pageNumber } onClick={ () => { callback( pageNumber, pageNumber - 1 ) } }>{ pageNumber }</Pagination.Item>
			)
		}

		return links;
	} );

	return (
		<Pagination className={ className } size={ size }>
			{ ( current > 1 && callbackFirstPage ) &&
			  <Pagination.First onClick={ callbackFirstPage } />
			}
			{ callbackToPage && pageLinks( current, range, pages, callbackToPage ) }
			{ ( current < pages && callbackLastPage ) &&
			  <Pagination.Last onClick={ callbackLastPage } />
			}
		</Pagination>
	)
}
