import React, { forwardRef, useRef, useState } from 'react';
import { func, string } from 'prop-types';
import { InputGroup } from 'react-bootstrap';
import Input from '../../fields/Input';
import { debounce } from '../../../utils/events';
import Icon from '../Icon';

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

	const clearSearch = useRef(
			() => handleSearch( '' )
	).current;

	const btn = (
		<InputGroup.Text style={ { border: 0, background: 'transparent', transform: 'translate(-100%,0)', zIndex: 6 } }>
			{ currentSearch ? <Icon btn icon="clear" onClick={ clearSearch } /> : <Icon btn icon="search" /> }
		</InputGroup.Text>
	)

	return (
		<InputGroup ref={ ref }>
			<Input onChange={ handleSearch } value={ currentSearch } placeholder={ placeholder } attr={ { className: "pe-4" } } postfix={ btn } />
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
