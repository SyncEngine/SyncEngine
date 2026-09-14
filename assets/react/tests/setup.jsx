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
	return {
		...actual,
		Accordion: actual.Accordion || actual,
		Modal: ({ children, ...props }) => (
			<div {...props} data-testid="modal-wrapper">
				{children}
			</div>
		),
		Form: {
			Check: ({ children, ...props }) => (
				<div {...props} data-testid="form-check">
					{children}
				</div>
			),
			...actual.Form,
		},
	};
});

// Build a realistic SyncEngine mock
function createSyncEngineMock() {
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
	window.SyncEngine = createSyncEngineMock();
});

afterEach(() => {
	cleanup();
	vi.clearAllMocks();
	delete window.SyncEngine;
});
