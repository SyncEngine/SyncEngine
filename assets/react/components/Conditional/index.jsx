import React from 'react';
import { hasValue, isEmpty } from "../../utils/conditionals";

export default function Conditional( props ) {
	const {
		children,
		conditionals,
		data,
	} = props;

	const validate = () => {
		let valid = true;
		if ( conditionals && Object.keys( conditionals ).length ) {
			for ( let key in conditionals ) {
				const conditional = conditionals[ key ];

				const {
					compare,
					key,
					value
				} = conditional;

				switch ( compare ) {
					case 'isset':
						valid = data.hasOwnProperty( key );
						break;
					case 'empty':
						valid = data.hasOwnProperty( key ) || isEmpty( data[ key ] );
						break;
					case '<':
						valid = value < data[ key ];
						break;
					case '>':
						valid = value > data[ key ];
						break;
					case '<=':
						valid = value <= data[ key ];
						break;
					case '>=':
						valid = value >= data[ key ];
						break;
					case '!=':
						valid = value != data[ key ];
						break;
					case '==':
						valid = value == data[ key ];
						break;
					case '!==':
						valid = value !== data[ key ];
						break;
					case '===':
						valid = value === data[ key ];
						break;
					default:
						if ( ! data.hasOwnProperty( key ) ) {
							valid = isEmpty( value );
						}
						if ( value ) {
							if ( true === value ) {
								valid = hasValue( data[ key ] );
							}
							valid = value === data[ key ];
						} else {
							valid = isEmpty( data[ key ] );
						}

						if ( ! valid ) {
							break;
						}
						break;
				}
			}
		}

		return valid;
	}

	if ( validate() ) {
		return children;
	}
	return [];
}
