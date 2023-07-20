import React from 'react';

export default function ListHead( props ) {
	const {
		columns,
	} = props;

	return (
		<thead>
			<tr>
			{
				columns.map( ( type, index ) => {
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
