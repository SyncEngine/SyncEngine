import React from 'react';
import Fields from '../components/form/Fields';
import FieldContainer from '../components/form/Field/Container';

export default function DsnController( props ) {

	const {
		args = {},
		value,
		onChange,
	} = props;

	const scheme = value.split( '://' );

	let url, dsn;
	try {
		url = new URL( 'https://' + scheme[ 1 ] );

		dsn = {
			username: decodeURIComponent( url.username ?? '' ),
			password: decodeURIComponent( url.password ?? '' ),
			host: decodeURIComponent( url.hostname ?? '' ),
			port: decodeURIComponent( url.port ?? '' ),
			path: decodeURIComponent( url.pathname ?? '' ),
			protocol: scheme[ 0 ],
		};
	} catch ( $e ) {
		dsn = {
			protocol: scheme[ 0 ],
			path: scheme[ 1 ],
		}
	}

	const update = ( values = {} ) => {

		let value;

		if ( url ) {
			url.username = values.username ?? '';
			url.password = values.password ?? '';
			url.hostname = values.host ?? '';
			url.port = values.port ?? '';
			url.pathname = values.path ?? '';

			value = decodeURIComponent(
				url.toString().replace( 'https://', values.protocol + '://' )
			);
		} else {
			value = (
				( values.protocol ? values.protocol + '://' : '//' ) +
				( values.username ?
					values.username + ( values.password ? ':' + values.password : '' ) + '@'
					: ''
				) +
				( values.host ? values.host + ( values.port ? ':' + values.port : '' ) : '' ) +
				( values.path ? values.path : '' )
			)
		}

		onChange( value );
	};

	const parseFields = ( fields ) => {
		for ( const key in fields ) {
			if ( !fields.hasOwnProperty( key ) ) {
				continue;
			}
			let field = fields[ key ];
			const defaults = {
				name: key,
				label: (
					'string' === typeof fields[ key ]
				) ? fields[ key ] : key,
				type: 'text',
			};

			if ( 'object' !== typeof field ) {
				field = defaults;
			} else {
				field = { ...field, ...defaults };
			}

			switch ( key ) {
				case 'protocol':
					if ( field.choices ) {
						field.type = 'select';
					}
					break;
				case 'port':
					field.type = 'number';
					break;
			}

			fields[ key ] = field;
		}

		return { dsn: { inline: 'fixed', col: 'col-md-4 col-lg-2', name: 'dsn', fields:fields } };
	};

	return (
		<FieldContainer label={ props.label } classHeader={ 'p-input' } collapsible={ false }>
			<Fields { ...args } fields={ parseFields( args.fields ?? {} ) } value={ dsn } onChange={ update }></Fields>
		</FieldContainer>
	);
}
