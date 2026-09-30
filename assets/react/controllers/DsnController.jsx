import React, { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import PropTypes from 'prop-types';
import Fields from '../components/form/Fields';
import FieldContainer from '../components/form/Field/Container';
import { isEmpty, validate } from '../utils/conditions';
import { deepClone } from '../utils/data';
import { FieldsContext } from '../context/FieldsContext';
import { createRefId } from '../utils/globals';
import { publishFieldValue } from '../hooks/useFieldValue';

function parseDsnString( dsn ) {
	if ( ! dsn || 'string' !== typeof dsn ) {
		return { protocol: '', username: '', password: '', host: '', port: '', path: '', query: {} };
	}

	const schemeMatch = dsn.match( /^([^:]+):\/\// );
	const protocol = schemeMatch ? schemeMatch[ 1 ] : '';
	const remainder = schemeMatch ? dsn.slice( schemeMatch[ 0 ].length ) : dsn;

	let username = '', password = '', host = '', port = '', path = '';
	let query = {};

	try {
		const url = new URL( 'https://' + remainder );

		username = decodeURIComponent( url.username ?? '' );
		password = decodeURIComponent( url.password ?? '' );
		host = decodeURIComponent( url.hostname ?? '' );
		port = url.port ?? '';
		path = url.pathname?.slice( 1 ) ?? '';

		if ( url.search ) {
			const searchParams = new URLSearchParams( url.search );
			for ( const [ key, val ] of searchParams.entries() ) {
				query[ key ] = val;
			}
		}
	} catch ( $e ) {
		const atIdx = remainder.indexOf( '@' );
		if ( atIdx !== -1 ) {
			const authPart = remainder.slice( 0, atIdx );
			const colonIdx = authPart.lastIndexOf( ':' );
			if ( colonIdx !== -1 ) {
				username = decodeURIComponent( authPart.slice( 0, colonIdx ) );
				password = decodeURIComponent( authPart.slice( colonIdx + 1 ) );
			} else {
				username = decodeURIComponent( authPart );
			}
			const rest = remainder.slice( atIdx + 1 );
			const bracketEnd = rest.indexOf( ']' );
			if ( rest.startsWith( '[' ) && bracketEnd !== -1 ) {
				host = rest.slice( 0, bracketEnd + 1 );
				const afterBracket = rest.slice( bracketEnd + 1 );
				if ( afterBracket.startsWith( ':' ) ) {
					port = afterBracket.slice( 1 ).split( '/' )[ 0 ];
					const pathPart = afterBracket.slice( afterBracket.indexOf( '/' ) + 1 );
					path = pathPart.startsWith( '/' ) ? pathPart.slice( 1 ) : pathPart;
				} else {
					path = afterBracket.startsWith( '/' ) ? afterBracket.slice( 1 ) : afterBracket;
				}
			} else {
				const colonIdx2 = rest.indexOf( ':' );
				if ( colonIdx2 !== -1 ) {
					host = rest.slice( 0, colonIdx2 );
					const afterColon = rest.slice( colonIdx2 + 1 );
					const slashIdx = afterColon.indexOf( '/' );
					if ( slashIdx !== -1 ) {
						port = afterColon.slice( 0, slashIdx );
						path = afterColon.slice( slashIdx + 1 );
					} else {
						port = afterColon;
						path = '';
					}
				} else {
					const slashIdx = rest.indexOf( '/' );
					if ( slashIdx !== -1 ) {
						host = rest.slice( 0, slashIdx );
						path = rest.slice( slashIdx + 1 );
					} else {
						host = rest;
					}
				}
			}
		} else {
			const atHost = remainder.indexOf( '/' );
			if ( atHost !== -1 ) {
				host = remainder.slice( 0, atHost );
				path = remainder.slice( atHost + 1 );
			} else {
				host = remainder;
			}
		}

		const queryIdx = path.indexOf( '?' );
		if ( queryIdx !== -1 ) {
			const queryString = path.slice( queryIdx + 1 );
			path = path.slice( 0, queryIdx );
			const searchParams = new URLSearchParams( queryString );
			for ( const [ key, val ] of searchParams.entries() ) {
				query[ key ] = val;
			}
		}

		if ( ! path.startsWith( '/' ) ) {
			path = '/' + path;
		}
	}

	return { protocol, username, password, host, port, path, query };
}

function compileDsnString( dsnObj ) {
	const protocol = dsnObj.protocol ?? '';
	const username = dsnObj.username ?? '';
	const password = dsnObj.password ?? '';
	const host = dsnObj.host ?? '';
	const port = dsnObj.port ?? '';
	const path = dsnObj.path ?? '';

	let dsnString = '';
	let dnsCreds = '';

	if ( protocol ) {
		dsnString += protocol + '://';
	} else {
		dsnString += '//';
	}

	if ( username || password ) {
		dnsCreds = ( username || '' );
		if ( password ) {
			dnsCreds += ':' + password;
		}
		dsnString += dnsCreds + '@';
	}

	if ( host ) {
		if ( host.includes( ':' ) && ! host.startsWith( '[' ) ) {
			dsnString += '[' + host + ']';
		} else {
			dsnString += host;
		}
		if ( port ) {
			dsnString += ':' + port;
		}
	}

	if ( path ) {
		dsnString += path;
	}

	const queryKeys = Object.keys( dsnObj.query );
	if ( queryKeys.length > 0 ) {
		const queryString = queryKeys.map( key => key + '=' + ( dsnObj.query[ key ] || '' ) ).join( '&' );
		dsnString += '?' + queryString;
	}

	return dsnString;
}

export default function DsnController( props ) {

	const {
		args = {},
		value,
		onChange,
		label,
	} = props;

	const passwordPlaceholder = ':*******';
	const defaults = args.defaults ?? {};

	const [ parsedDsn, setParsedDsn ] = useState( parseDsnString( value ?? '' ) );
	const [ compiledDsn, setCompiledDsn ] = useState( value ? value.replace( ':' + parsedDsn.password, passwordPlaceholder ) : '' );

	const ref = useRef( createRefId() );
	const fieldsContext = FieldsContext.create( 'dsn', parsedDsn, ref.current );

	const dsnFields = useMemo( () => args.fields ?? {}, [ args.fields ] );

	useEffect( () => {
		const parsed = parseDsnString( value ?? '' );
		setParsedDsn( parsed );
		setCompiledDsn( value ? value.replace( ':' + parsed.password, passwordPlaceholder ) : '' );
	}, [ value ] );

	const update = useCallback( ( dsnObj ) => {

		if ( parsedDsn.protocol !== dsnObj.protocol ) {
			if ( dsnObj.port === parsedDsn.port && ! isEmpty( defaults.port ) && defaults.port.hasOwnProperty( dsnObj.protocol ) ) {

				const hasCurrentDefault = defaults.port.hasOwnProperty( parsedDsn.protocol );
				const isCurrentDefault = hasCurrentDefault && String( defaults.port[ parsedDsn.protocol ] ) === String( parsedDsn.port );

				if ( ! hasCurrentDefault || isCurrentDefault ) {
					dsnObj.port = defaults.port[ dsnObj.protocol ];

					publishFieldValue( 'port', fieldsContext, dsnObj.port );
				}
			}
		}

		let dnsString = compileDsnString( validateDsn( dsnObj ) );
		onChange( dnsString );
		setParsedDsn( deepClone( dsnObj ) );
		setCompiledDsn( dnsString ? dnsString.replace( ':' + dsnObj.password, passwordPlaceholder ) : '' );
	}, [ onChange, parsedDsn.protocol ] );

	const dsnFields = useMemo( () => args.fields ?? {}, [ args.fields ] );
	const validateDsn = useCallback( ( dsnObj ) => {
		const validated = { ...dsnObj };
		const fields = dsnFields ?? {};

		for ( const key in dsnFields ) {
			if ( ! fields.hasOwnProperty( key ) || ! fields[ key ].conditions ) {
				continue;
			}

			if ( ! validate( fields[ key ].conditions, dsnObj ) ) {
				delete validated[ key ];
			}
		}

		return validated;
	}, [ dsnFields ] );

	return (
		<FieldContainer label={ label } description={ compiledDsn } collapsed={ false } collapsible={ false }>
			<Fields value={ deepClone( parsedDsn ) } onChange={ update } fields={ dsnFields } editable={ true } fieldsContext={ fieldsContext }></Fields>
		</FieldContainer>
	);
}

DsnController.propTypes = {
	args: PropTypes.object,
	value: PropTypes.string,
	onChange: PropTypes.func,
	label: PropTypes.string,
};
