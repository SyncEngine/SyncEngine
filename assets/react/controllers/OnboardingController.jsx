import React, { useCallback, useEffect, useRef, useState, lazy, Suspense } from 'react';
import { Modal, Spinner } from 'react-bootstrap';
import usePreference from '../hooks/usePreference';

function getOnboardingRef() {
	if ( ! window._onboardingRef ) {
		window._onboardingRef = { active: false };
	}
	return window._onboardingRef;
}

const OnboardingOverlay = lazy(
	() => import( '../components/onboarding/OnboardingOverlay' )
);

export default function OnboardingController( props ) {
	const [ active, setActive ] = useState( false );
	const [ steps, setSteps ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ preferences, setPreference ] = usePreference( 'onboarding' );
	const mountedRef = useRef( false );
	const onboardingRef = getOnboardingRef();
	const currentSequenceRef = useRef( null );

	const getSequence = () => window.SyncEngine?.onboarding?.sequence ?? null;
	const getCompleted = () => preferences?.completed ?? {};

	const updatePreference = useCallback( ( key, value ) => {
		if ( 'object' === typeof key ) {
			setPreference( { ...preferences, ...key } );
		} else  {
			setPreference( { ...preferences, [ key ]: value} );
		}
	}, [ setPreference, preferences ] );

	const startOnboarding = useCallback( async ( sequence ) => {
		var targetSequence = sequence || getSequence();
		if ( ! targetSequence ) return;

		if ( onboardingRef.active ) return;
		onboardingRef.active = true;

		setLoading( true );
		setError( null );
		currentSequenceRef.current = targetSequence;

		try {
			const endpoint = window.SyncEngine?.onboarding?.endpoint || window.SyncEngine.endpoints.user.onboarding;
			const response = await fetch( `${endpoint}?sequence=${encodeURIComponent( targetSequence )}` );
			const data = await response.json();

			if ( ! data.success || ! data.data || data.data.length === 0 ) {
				setError( 'Failed to load onboarding steps' );
				onboardingRef.active = false;
				return;
			}

			setSteps( data.data );
			setActive( true );
		} catch ( e ) {
			setError( e.message || 'Network error' );
			onboardingRef.active = false;
		} finally {
			setLoading( false );
		}
	}, [] );

	// Auto-start on mount for new users
	useEffect( () => {
		if ( mountedRef.current ) return;
		mountedRef.current = true;

		var sequence = getSequence();
		var completed = getCompleted();

		if ( preferences?.autoStart && ! completed?.[ sequence ] ) {
			var timer = setTimeout( () => {
				startOnboarding( sequence );
			}, 1500 );

			return () => clearTimeout( timer );
		}
	}, [ preferences, startOnboarding ] );

	const handleComplete = useCallback( () => {
		setActive( false );
		setSteps( [] );
		onboardingRef.active = false;

		var sequence = currentSequenceRef.current;
		var completed = getCompleted();

		if ( sequence ) {
			completed = { ...completed, [ sequence ]: true };
		}

		updatePreference( {
			"onboardingComplete": true,
			"onboardingAutoStart": false,
			"completed": completed,
		} );
	}, [ updatePreference, preferences, currentSequenceRef ] );

	const sequence = getSequence();
	var completed = getCompleted();
	var hasOnboarding = !!sequence;
	var isCompleted = completed?.[ sequence ];

	return (
		<>
			{ hasOnboarding && (
				<a
					id="onboarding-help"
					href="#"
					className="btn btn-link btn-sm text-body-secondary"
					data-bs-toggle="tooltip"
					title={ isCompleted ? 'Restart tour' : 'Start tour' }
					aria-label="Start tour"
					onClick={ ( e ) => {
						e.preventDefault();
						startOnboarding();
					} }
				>
					<span className="bi bi-question-circle fs-5"></span>
				</a>
			) }

			{ error && (
				<Modal
					show={ true }
					onHide={ () => { setActive( false ); onboardingRef.active = false; } }
					centered
					dialogClassName="onboarding-dialog"
				>
					<Modal.Header closeButton>
						<Modal.Title>Onboarding</Modal.Title>
					</Modal.Header>
					<Modal.Body className="text-center py-4">
						<p className="mb-3">Unable to load onboarding: { error }</p>
						<button className="btn btn-sm btn-secondary" onClick={ () => { setActive( false ); onboardingRef.active = false; } }>Dismiss</button>
					</Modal.Body>
				</Modal>
			) }

			{ loading && steps.length === 0 && (
				<Modal
					show={ true }
					centered
					backdrop="static"
					dialogClassName="onboarding-dialog onboarding-loading-dialog"
				>
					<Modal.Body className="text-center py-4">
						<Spinner animation="border" variant="primary" role="status">
							<span className="visually-hidden">Loading...</span>
						</Spinner>
						<p className="mt-3 mb-0 text-muted">Loading onboarding...</p>
					</Modal.Body>
				</Modal>
			) }

			{ active && (
				<Suspense fallback={
					<Modal
						show={ true }
						centered
						backdrop="static"
						dialogClassName="onboarding-dialog onboarding-loading-dialog"
					>
						<Modal.Body className="text-center py-4">
							<Spinner animation="border" variant="primary" role="status">
								<span className="visually-hidden">Loading...</span>
							</Spinner>
							<p className="mt-3 mb-0 text-muted">Loading onboarding...</p>
						</Modal.Body>
					</Modal>
				}>
					<OnboardingOverlay
						steps={ steps }
						onComplete={ handleComplete }
					/>
				</Suspense>
			) }
		</>
	);
}
