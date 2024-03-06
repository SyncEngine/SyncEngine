import React, { useRef } from 'react';
import { Stack, Tab, Tabs } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import Trace from './Trace';
import { ParentContext } from '../../../context/ParentContext';
import OverlayToggle from '../OverlayToggle';

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
						objectToMappable( data ).map( ( row, index ) => {
							const {
								iterator = {},
								trace = {},
							} = row;

							const title = iterator ? (
								<OverlayToggle overlay={ iterator } trigger={ [ 'hover', 'focus' ] } raw>
									<span>{ iterator.current }<span className="bi bi-info"/></span>
								</OverlayToggle>
							) : index;

							return (
								<Tab key={ index } eventKey={ index } title={ title }>
									<Trace data={ trace } />
								</Tab>
							)
						} )
					}
				</Tabs>
			</ParentContext.Provider>
		</Stack>
	);
}
