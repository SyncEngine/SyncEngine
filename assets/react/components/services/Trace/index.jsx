import React, { useRef } from 'react';
import { Stack, Tab, Tabs } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import Trace from './Trace';
import OverlayToggle from '../OverlayToggle';
import TraceLog from './Log';

export default function TraceControl( props ) {
	const {
		data,
	} = props;

	const parent = useRef();

	return (
		<Stack className="pt-2 mw-100 overflow-hidden" ref={ parent }>
			<Tabs gap={2}>
				{
					objectToMappable( data ).map( ( row, index ) => {
						const {
							iterator = {},
							trace = {},
						} = row;

						const title = iterator ? (
							<OverlayToggle overlay={ <div><TraceLog data={ iterator } /></div> } trigger={ [ 'hover' ] }>
								<div>{ iterator.current }<span className="bi bi-info"/></div>
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
		</Stack>
	);
}
