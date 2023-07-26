
import React from 'react';
import { Pagination } from 'react-bootstrap';

export default function PaginationView( props ) {
	const {
		callbackFirstPage,
		callbackLastPage,
		callbackToPage,
		pages,
		current,
		size,
		className,
		variant,
	} = props;

	return (
		<Pagination className={ className } size={ size }>
			{ ( current > 1 && callbackFirstPage ) &&
			  <Pagination.First onClick={ callbackFirstPage } />
			}
			{ callbackToPage &&
				Array.from(Array(pages).keys()).map( ( pageIndex ) => {
					const pageNumber = pageIndex + 1;
					return <Pagination.Item active={ ( current === pageNumber ) } key={ pageIndex } onClick={ () => { callbackToPage( pageIndex ) } }>{ pageNumber }</Pagination.Item>
				} )
			}
			{ ( current < pages && callbackLastPage ) &&
			  <Pagination.Last onClick={ callbackLastPage } />
			}
		</Pagination>
	)
}
