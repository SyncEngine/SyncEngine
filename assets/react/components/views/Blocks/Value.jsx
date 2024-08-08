import { parseTagString } from '../../../utils/tags';
import useDateFormatter from '../../../hooks/useDateFormatter';

export default function Value( props ) {
	const {
		item = {},
		type = item.type,
		prop = 'label',
		fallback = 'name',
		parse = 'item',
		prefix = '',
		postfix = '',
	} = props;

	const dateFormatter = useDateFormatter();
	//const [ models, callbacks ] = useModels( type );

	let value = props.value ?? item[ prop ] ?? item[ fallback ];

	parse.split( '|' ).map( name => {
		switch ( name ) {
			case 'date':
				value = dateFormatter.format( value * 1000 );
				break;
			case 'tag':
				value = parseTagString( value, item );
				break;
			/*case 'model':
				value = callbacks.get( value );
				break;*/
		}
	} );

	return value ? prefix + value + postfix : props.default;
}
