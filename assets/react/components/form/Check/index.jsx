
export { default as CheckSingle } from './Single';
export { default as CheckMulti } from './Multi';

export function getCheckButtonVariant( props ) {
	return props.variant ?? ( 'string' === typeof props.button ? props.button : undefined );
}
