import React, { useRef, useState } from 'react';
import { Stack, Tab, Tabs } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import TraceContainer from './Container';

function parseTrace( traceData, callbacks, ancestors ) {
	traceData = objectToMappable( traceData, '_key', 'message' ).map( ( step ) => {

		step.title = step.name ? step.name : 'string' === typeof step.info ? step.info : '';

		step._ref = useRef();
		step._ancestors = [ ...ancestors ];

		step._isLog = step.log || step._key.startsWith( 'Log:' );
		step._isError = step.error || step._key.startsWith( 'Error:' );

		if ( step._isLog || step._isError ) {
			const keyParts = step._key.split(':');
			step._timestamp = step.timestamp || keyParts[1].trim() * 1000;
			step.title = keyParts[0] + ': ' + step.message;
		} else {
			if ( ! step.time_leave || step.time_leave === step.time_enter ) {
				step._timestamp = step.time_enter * 1000;
			} else {
				step._timestamp = [
					step.time_enter * 1000,
					step.time_leave * 1000
				]
			}
		}

		// Parse recursively.
		step.trace = parseTrace( step.trace, callbacks, [ ...ancestors, step ] );

		if ( step._isLog && callbacks.hasOwnProperty( 'addLog' ) ) {
			step.log = step.log ?? 'log';
			step._isLog && callbacks.addLog( step );
		}
		if ( step._isError && callbacks.hasOwnProperty( 'addError' ) ) {
			step.log = step.log ?? 'error';
			step._isError && callbacks.addError( step );
		}

		return step;
	} );

	return traceData;
}

export default function TraceControl( props ) {
	const [ selected, setSelected ] = useState();

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
							trace,
						} = row;

						if ( ! trace ) {
							return;
						}

						const errors = [];
						const logs = [];

						const addLog = ( log ) => {
							logs.push( log );
						}
						const addError = ( error ) => {
							errors.push( error );
						}

						const traceData = parseTrace( trace, {
							addLog: addLog,
							addError: addError
						}, [] );

						return (
							<Tab key={ index } eventKey={ index } title={ iterator.current ?? index }>
								<TraceContainer { ...row } trace={ traceData } errors={ errors } logs={ logs } />
							</Tab>
						)
					} )
				}
			</Tabs>
		</Stack>
	);
}
