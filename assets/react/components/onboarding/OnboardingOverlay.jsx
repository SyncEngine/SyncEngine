import React, { useCallback, useEffect, useState } from 'react';
import { Overlay, Popover, Modal } from 'react-bootstrap';
import ProgressBar from './ProgressBar';
import Highlight from './Highlight';
import Button from '../partials/Button';
import { HStack } from '../partials/Stack';
import Icon from '../partials/Icon';
import { mergeClassNames } from '../../utils/props';

function getTargetElement( target ) {
	if ( ! target ) return null;
	return document.querySelector( target );
}

function formatBody( body ) {
	return body.split( '\n' ).map( ( line, i ) => {
		if ( line.startsWith( '• ' ) ) {
			return (
				<div key={ i } className="d-flex gap-2 mb-1">
					<span className="text-primary fw-bold">•</span> { formatInline( line.substring( 2 ) ) }
				</div>
			);
		}
		if ( line.trim() === '' ) {
			return <br key={ i } />;
		}
		return <div key={ i }>{ formatInline( line ) }</div>;
	} );
}

function formatInline( text ) {
	return text.split( /(\*\*[^*]+\*\*)/g ).map( ( part, i ) => {
		if ( part.startsWith( '**' ) && part.endsWith( '**' ) ) {
			return <strong key={ i }>{ part.slice( 2, -2 ) }</strong>;
		}
		return part;
	} );
}

export default function OnboardingOverlay( props ) {
	const { steps, onComplete } = props;
	const [ currentStep, setCurrentStep ] = useState( 0 );
	const [ show, setShow ] = useState( true );

	const step = steps[ currentStep ];
	const target = step.target ? getTargetElement( step.target ) : null;

	const handleNext = () => {
		if ( currentStep < steps.length - 1 ) {
			setCurrentStep( currentStep + 1 );
		} else {
			onComplete();
		}
	};

	const handlePrev = () => {
		if ( currentStep > 0 ) {
			setCurrentStep( currentStep - 1 );
		}
	};

	const handleKeyDown = useCallback( ( e ) => {
		if ( e.key === 'Escape' ) {
			onComplete();
		} else if ( e.key === 'ArrowRight' || e.key === 'Enter' ) {
			handleNext();
		} else if ( e.key === 'ArrowLeft' ) {
			handlePrev();
		}
	}, [ currentStep, steps.length, onComplete ] );

	useEffect( () => {
		document.addEventListener( 'keydown', handleKeyDown );
		return () => document.removeEventListener( 'keydown', handleKeyDown );
	}, [ handleKeyDown ] );

	const isCentered = step.placement === 'center' || ! step.target;

	const stepHeader = ( props = {} ) => {
		const {
			className = ''
		} = props;

		return (
			<HStack gap={ 2 } className={ mergeClassNames( className, 'justify-content-between w-100' ) }>
				<Icon icon={ step.icon || 'info-circle' } />
				<ProgressBar total={ steps.length } current={ currentStep } />
				<Icon btn icon={ 'close' } onClick={ onComplete } />
			</HStack>
		)
	}

	const stepBody = () => (
		<>
			<div className="h5 fw-bold mb-2">{ step.title }</div>
			<div>{ formatBody( step.body ) }</div>
			<HStack gap={ 2 } className="mt-3 justify-content-between">
				{ currentStep > 0 && (
					<Button icon="previous" variant="outline-secondary" size="sm" onClick={ handlePrev }>
						Previous
					</Button>
				) }
				<Button icon="next" variant="primary" size="sm" onClick={ handleNext }>
					{ currentStep === steps.length - 1 ? 'Finish' : 'Next' }
				</Button>
			</HStack>
		</>
	);

	if ( isCentered ) {
		return (
			<Modal show={ show } centered backdropClassName="onboarding-backdrop" onClick={ () => {} }>
				<Modal.Header>
					{ stepHeader( { className: 'px-2' } ) }
				</Modal.Header>
				<Modal.Body className="p-4">
					{ stepBody() }
				</Modal.Body>
			</Modal>
		);
	}

	return (
		<>
			<div className="onboarding-backdrop" onClick={ () => {} } />

			{ target && (
				<Highlight target={ target } />
			) }

			<Overlay
				target={ target }
				placement={ step.placement || 'top' }
				show={ show }
				flip
				popperConfig={ { strategy: 'fixed' } }
			>
				{ ( overlayProps ) => (
					<Popover { ...overlayProps } className="onboarding-popover shadow-lg">
						<Popover.Header className="d-flex justify-content-between align-items-center">
							{ stepHeader() }
						</Popover.Header>
						<Popover.Body className="pt-2">
							{ stepBody() }
						</Popover.Body>
					</Popover>
				) }
			</Overlay>
		</>
	);
}
