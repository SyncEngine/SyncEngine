import React from 'react';
import { TAG_END_CHAR, TAG_SEPARATOR, TAG_START_CHAR } from '../../../utils/tags';
import useToggle from '../../../hooks/useToggle';

export default function TagsLabel( props ) {

	const {
		startChar = TAG_START_CHAR + ' ',
		endChar = ' ' + TAG_END_CHAR,
		separator = TAG_SEPARATOR,
		parent,
		prefix = parent,
		postfix,
		label,
		tag,
	} = props;

	const [ showLabel, toggleLabel ] = useToggle( !! props.label );

	return (
		<div className={ props.className } title={ props.title } style={ { whiteSpace: 'pre' } } onDoubleClick={ toggleLabel }>
			<span>{ startChar }</span>
			{ prefix && <span className="opacity-50">{ prefix }</span> }
			{ showLabel ? <span title={ tag }>{ label }</span> : <span>{ tag }</span> }
			{ postfix }
			<span>{ endChar }</span>
		</div>
	);
}
