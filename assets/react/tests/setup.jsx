import { beforeAll, afterEach, vi } from 'vitest';
import { cleanup } from '@testing-library/react';
import '@testing-library/jest-dom/vitest';
//import '../../translator';

// Mock useTranslator to avoid issues since global translator is not set.
// @todo make the translator compatible and test translations.
vi.mock('@syncengine/hooks/useTranslator', () => ({
	useTranslator: () => ({
		t: (key, params = {}) => {
			if (typeof key === 'string') return key;
			return JSON.stringify(key);
		},
	}),
}));

// Mock react-bootstrap to avoid portal/DOM issues in tests
vi.mock('react-bootstrap', async () => {
	const actual = await vi.importActual('react-bootstrap');
	
	// Create simple wrapper components for Bootstrap Modal sub-components
	const createModalComponent = ( name ) => {
		return ({ children, ...props }) => (
			<div {...props} data-testid={`${name.toLowerCase()}`}>{children}</div>
		);
	};

	const ModalWrapper = createModalComponent( 'Modal' );
	ModalWrapper.Header = createModalComponent( 'ModalHeader' );
	ModalWrapper.Body = createModalComponent( 'ModalBody' );
	ModalWrapper.Footer = createModalComponent( 'ModalFooter' );
	ModalWrapper.Dialog = createModalComponent( 'ModalDialog' );

	return {
		...actual,
		Accordion: actual.Accordion || actual,
		Modal: ModalWrapper,
		Form: {
			Check: ({ children, ...props }) => (
				<div {...props} data-testid="form-check">{children}</div>
			),
			...actual.Form,
		},
	};
});

// Mock window.matchMedia for useMediaQuery hook used by Repeatable/Actions
if ( !window.matchMedia ) {
	window.matchMedia = vi.fn( ( query ) => ( {
		matches: false,
		media: query,
		onchange: null,
		addEventListener: vi.fn(),
		removeEventListener: vi.fn(),
		dispatchEvent: vi.fn(),
	} ) );
}

// Mock the Code component to avoid app.theme.getTheme() issues
vi.mock('@syncengine/components/fields/Code', () => ({
	default: ({ label, default: defaultValue }) => (
		<div data-testid="code-field">
			{label && <label>{label}</label>}
			<textarea data-testid="code-textarea" defaultValue={defaultValue} />
		</div>
	),
}));

// Build a realistic SyncEngine mock
function createSyncEngineMock() {
	const breakpoints = {
		get: (bp) => {
			const map = { xs: 0, sm: 576, md: 768, lg: 992, xl: 1200, xxl: 1400 };
			return map[ bp ] ?? 0;
		},
	};

	return {
		routes: { get: (type, params) => `/api/${type}${params ? '/' + JSON.stringify(params) : ''}` },
		endpoints: {
			models: '/api/models',
			entities: '/api/entities',
			requests: '/api/requests',
			user: '/api/user',
			secrets: '/api/secrets',
			tasks: '/api/tasks',
			webservices: '/api/webservices',
			columns: '/api/columns',
			blueprints: '/api/blueprints',
			codecs: '/api/codecs',
		},
		baseUrl: '',
		models: {},
		entities: {},
		breakpoints,
		context: {
			register: vi.fn(),
			clear: vi.fn(),
			get: vi.fn(() => undefined),
		},
		theme: { theme: 'light', setTheme: vi.fn() },
		preferences: {},
		forms: {
			changed: [],
			register: vi.fn(),
			unregister: vi.fn(),
		},
		icons: {},
	};
}

beforeEach(() => {
	vi.clearAllMocks();
	vi.clearAllTimers();
	window.SyncEngine = createSyncEngineMock();
});

afterEach(() => {
	cleanup();
	vi.clearAllMocks();
	vi.clearAllTimers();
	delete window.SyncEngine;
});
