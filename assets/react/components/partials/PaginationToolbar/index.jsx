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
		limit,
		defaultLimit = limit,
		variant,
	} = props;

	return (
		<Stack direction="horizontal" gap={2} className="justify-content-between align-items-center">
			<Info
				totalItems={ totalItems }
				numItems={ numItems }
				limit={ limit }
				defaultLimit={ defaultLimit }
				variant={ variant }
				size={ size }
			/>
			{
				( callbackLoadMore && totalItems && items ) &&
				<LoadMore
					callback={ callbackLoadMore }
					totalItems={ totalItems }
					numItems={ numItems }
					variant={ variant }
					size={ size }
				/>
			}
			<Pager
				callbackFirstPage={ callbackFirstPage }
				callbackLastPage={ callbackLastPage }
				callbackToPage={ callbackToPage }
				size={ size }
				pages={ pages }
				current={ current }
				variant={ variant }
			/>
		</Stack>
	)
}
