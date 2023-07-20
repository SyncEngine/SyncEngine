import React from 'react';
import { objectToMappable } from '../../../utils/data';

export default function ListHead( props ) {
	const {
		columns,
	} = props;

	return (
		<thead>
			<tr>
			{
				objectToMappable( columns, 'name', 'label' ).map( ( type, index ) => {
					return (
						<th key={ type.name ?? index } >
							<span
								className="text-uppercase small text-secondary fw-semibold"
								style={ { '--bs-bg-opacity': '.05' } }
							>
								{ type.label ?? type.name ?? '' }
							</span>
						</th>
					)
				} )
			}
			</tr>
		</thead>
	);
}
