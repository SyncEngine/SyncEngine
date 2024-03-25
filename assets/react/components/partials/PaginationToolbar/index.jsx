import React from 'react';
import { Stack } from 'react-bootstrap';
import LoadMore from './LoadMore';
import Info from './Info';
import Pager from './Pager';

export default function PaginationToolbar( props ) {
	const {
		callbackFirstPage,
		callbackLastPage,
		callbackToPage,
		callbackLoadMore,
		callbackSetLimit,
		pages,
		current,
		numItems,
		totalItems,
		size = 'sm',
		offset,
		limit,
		defaultLimit = limit,
		variant,
	} = props;

	const showLoadMore = ( callbackLoadMore && totalItems && numItems && ( ( offset + numItems ) < totalItems ) );

	return (
		<Stack direction="horizontal" gap={2} className="justify-content-between align-items-center">
			<Info
				callbackSetLimit={ callbackSetLimit }
				totalItems={ totalItems }
				numItems={ numItems }
				offset={ offset }
				limit={ limit }
				defaultLimit={ defaultLimit }
				variant={ variant }
				size={ size }
			/>
			{
				showLoadMore &&
				<LoadMore
					callback={ callbackLoadMore }
					callbackReset={ () => { callbackSetLimit( limit ) } }
					totalItems={ totalItems }
					numItems={ numItems }
					limit={ limit }
					variant={ variant }
					size={ size }
				/>
			}
			{ ( pages > 1 ) &&
				<Pager
					callbackFirstPage={ callbackFirstPage }
					callbackLastPage={ callbackLastPage }
					callbackToPage={ callbackToPage }
					size={ size }
					pages={ pages }
					current={ current }
					variant={ variant }
				/>
			}
		</Stack>
	)
}
