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

				let operator = conditional.hasOwnProperty( 'operator' ) ? conditional.operator : null,
					compare = conditional.hasOwnProperty( 'compare' ) ? conditional.compare : conditional;

				switch ( operator ) {
					case 'isset':
						valid = data.hasOwnProperty( key );
						break;
					case 'empty':
						valid = data.hasOwnProperty( key ) || isEmpty( data[ key ] );
						break;
					case 'in':
						valid = data.hasOwnProperty( key ) && -1 !== compare.indexOf( data[ key ] );
						break;
					case 'not':
						valid = ! data.hasOwnProperty( key ) || -1 === compare.indexOf( data[ key ] );
						break;
					case '<':
						valid = compare < data[ key ];
						break;
					case '>':
						valid = compare > data[ key ];
						break;
					case '<=':
						valid = compare <= data[ key ];
						break;
					case '>=':
						valid = compare >= data[ key ];
						break;
					case '!=':
						valid = compare != data[ key ];
						break;
					case '==':
						valid = compare == data[ key ];
						break;
					case '!==':
						valid = compare !== data[ key ];
						break;
					case '===':
					default:
						valid = compare === data[ key ];
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
