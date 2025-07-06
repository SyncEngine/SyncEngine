import React, { useRef, useState } from 'react';
import { Stack, Tab, Tabs } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import TracesContainer from './TracesContainer';

function stringToTimestamp( string ) {
	let timestamp = string / 1000;
	if ( timestamp < 10000000 ) { // 1970-04-26 is way to old.
		return timestamp * 1000;
	}
	return timestamp;
}

function parseTrace( traceData, callbacks, ancestors ) {
	traceData = objectToMappable( traceData, '_key', 'message' ).map( ( entry, index ) => {
		if ( ! entry.hasOwnProperty('_key') ) {
			entry._key = '' + index;
		}

		entry.title = entry.name ? entry.name : 'string' === typeof entry.info ? entry.info : '';

		if ( entry.progress ) {
			entry.progress.percent = entry.progress.percent ?? ( entry.progress.current / entry.progress.total ) * 100;
			if ( ! entry.progress.label ) {
				entry.progress.label = parseInt( entry.progress.percent, 10 ) + '%';
				if ( entry.progress.current ) {
					entry.progress.label = entry.progress.current + '/' + entry.progress.total + ' (' + entry.progress.label + ')';
				}
			}
		}

		entry._ref = useRef();
		entry._ancestors = [ ...ancestors ];

		entry._isLog = entry.log || entry._key.startsWith( 'Log:' );
		entry._isError = entry.error || entry._key.startsWith( 'Error:' );

		if ( entry._isLog || entry._isError ) {
			const keyParts = entry._key.split(':');
			entry._timestamp = entry.timestamp || stringToTimestamp( keyParts[1].trim() );
			entry.title = keyParts[0] + ': ' + entry.message;
		} else {
			if ( ! entry.time_leave || entry.time_leave === entry.time_enter ) {
				entry._timestamp = entry.time_enter;
				entry._duration  = ( entry.time_duration || 0 );
			} else {
				entry._timestamp = [	entry.time_enter, entry.time_leave ];
				entry._duration = entry.time_duration || ( entry.time_leave - entry.time_enter );
			}
		}

		// Parse recursively.
		entry.trace = parseTrace( entry.trace, callbacks, [ ...ancestors, entry ] );

		if ( entry._isLog && callbacks.hasOwnProperty( 'addLog' ) ) {
			entry.log = entry.log ?? 'log';
			entry._isLog && callbacks.addLog( entry );
		}
		if ( entry._isError && callbacks.hasOwnProperty( 'addError' ) ) {
			entry.log = entry.log ?? 'error';
			entry._isError && callbacks.addError( entry );
		}

		return entry;
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
								<TracesContainer { ...row } trace={ traceData } errors={ errors } logs={ logs } />
							</Tab>
						)
					} )
				}
			</Tabs>
		</Stack>
	);
}
