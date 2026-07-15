import React, { useCallback, useEffect, useRef, useState, lazy, Suspense } from 'react';
import { Modal, Spinner } from 'react-bootstrap';
import usePreference from '../hooks/usePreference';

function getOnboardingRef() {
	if ( ! window._onboardingRef ) {
		window._onboardingRef = { active: false };
	}
	return window._onboardingRef;
}

export default function OnboardingController( props ) {
	const [ active, setActive ] = useState( false );
	const [ steps, setSteps ] = useState( [] );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ preferences, setPreference ] = usePreference( 'onboarding' );
	const mountedRef = useRef( false );
	const onboardingRef = getOnboardingRef();

	const updatePreference = useCallback( ( key, value ) => {
		if ( 'object' === typeof key ) {
			setPreference( { ...preferences, ...key } );
		} else  {
			setPreference( { ...preferences, [ key ]: value} );
		}
	}, [ setPreference, preferences ] );

	const startOnboarding = useCallback( async () => {
		if ( onboardingRef.active ) return;
		onboardingRef.active = true;

		setLoading( true );
		setError( null );

		try {
			const response = await fetch( window.SyncEngine.endpoints.user.onboarding );
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

		const shouldAutoStart = ( preferences?.onboardingAutoStart && ! preferences?.onboardingComplete );

		if ( shouldAutoStart ) {
			const timer = setTimeout( () => {
				startOnboarding();
			}, 1500 );

			return () => clearTimeout( timer );
		}
	}, [ startOnboarding ] );

	// Wire up help icon click
	useEffect( () => {
		const helpIcon = document.getElementById( 'onboarding-help' );
		if ( ! helpIcon ) return;

		const handleClick = ( e ) => {
			e.preventDefault();
			startOnboarding();
		};

		helpIcon.addEventListener( 'click', handleClick );

		return () => {
			helpIcon.removeEventListener( 'click', handleClick );
		};
	}, [ startOnboarding ] );

	// Update help icon tooltip
	useEffect( () => {
		const helpIcon = document.getElementById( 'onboarding-help' );
		if ( ! helpIcon ) return;

		const updateTitle = () => {
			helpIcon.setAttribute( 'title',
				preferences?.onboardingComplete ? 'Restart tour' : 'Start tour'
			);
		};

		updateTitle();
	}, [ preferences?.onboardingComplete ] );

	const handleComplete = useCallback( () => {
		setActive( false );
		setSteps( [] );
		onboardingRef.active = false;

		updatePreference( {
			"onboardingComplete": true,
			"onboardingAutoStart": false,
		} );
	}, [ updatePreference ] );

	if ( ! active ) {
		return null;
	}

	if ( error ) {
		return (
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
		);
	}

	if ( loading && steps.length === 0 ) {
		return (
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
		);
	}

	const OnboardingOverlay = lazy(
		() => import( '../components/onboarding/OnboardingOverlay' )
	);

	return (
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
	);
}
