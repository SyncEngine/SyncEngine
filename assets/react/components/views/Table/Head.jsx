import React from 'react';
import { objectToMappable } from '../../../utils/data';

export default function TableHead( { columns } ) {
	return (
		<thead>
			<tr>
			{
				objectToMappable( columns, 'key', 'label' ).map( ( column, index ) => {

					let classes = '';
					switch ( column.key ) {
						case 'ref':
						case 'id':
							classes += 'text-start table-cell-shrink';
							break;
						case 'actions':
							classes += 'text-end table-cell-shrink';
							break;
					}

					return (
						<th key={ column.key ?? index } className={ classes }>
							<span
								className="text-uppercase small text-secondary fw-semibold"
								style={ { '--bs-bg-opacity': '.05' } }
							>
								{ column.label ?? column.key ?? '' }
							</span>
						</th>
					)
				} )
			}
			</tr>
		</thead>
	);
}
