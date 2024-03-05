import React, { useRef } from 'react';
import { Stack, Tab, Tabs } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import Trace from './Trace';
import { ParentContext } from '../../../context/ParentContext';

export default function TraceControl( props ) {
	const {
		data,
	} = props;

	const parent = useRef();

	return (
		<Stack className="pt-2 mw-100" ref={ parent }>
			<ParentContext.Provider value={ { container: parent } }>
				<Tabs gap={2}>
					{
						objectToMappable( data, 'iteration' ).map( ( row, index ) => {
							const {
								iteration = index,
								trace = {},
							} = row;

							return (
								<Tab key={ index } eventKey={ index } title={ index }>
									<Trace key={ iteration } data={ trace } />
								</Tab>
							)
						} )
					}
				</Tabs>
			</ParentContext.Provider>
		</Stack>
	);
}
