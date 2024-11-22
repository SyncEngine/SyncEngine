import React, { forwardRef, useState } from 'react';
import { func, string } from 'prop-types';
import { InputGroup } from 'react-bootstrap';
import Input from '../../fields/Input';
import { debounce } from '../../../utils/events';

function Search( props, ref ) {

	const {
		onSearch,
		column,
		value,
		placeholder,
	} = props;

	const [ currentSearch, setCurrentSearch ] = useState( value );

	const doSearch = React.useRef(
		debounce( ( search ) => {
			onSearch( search, column );
		}, 500 )
	).current;

	const handleSearch = ( search ) => {
		setCurrentSearch( search );
		doSearch( search );
	};

	return (
		<InputGroup ref={ ref }>
			<Input onChange={ handleSearch } value={ currentSearch } placeholder={ placeholder } />
		</InputGroup>
	);
}

export default forwardRef( Search );

Search.propTypes = {
	onSearch: func,
	column: string,
	value: string,
	placeholder: string,
}
