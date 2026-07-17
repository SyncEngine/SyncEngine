import { beforeAll, afterEach, vi } from 'vitest';
import { cleanup } from '@testing-library/react';
import '@testing-library/jest-dom/vitest';

// Mock i18next — all components use useTranslation()
vi.mock('react-i18next', () => ({
	useTranslation: () => ({
		t: (key) => (typeof key === 'string' ? key : JSON.stringify(key)),
		i18n: { language: 'en', changeLanguage: vi.fn() },
	}),
	Trans: ({ children }) => children,
}));

// Mock react-bootstrap to avoid portal/DOM issues in tests
vi.mock('react-bootstrap', () => {
	const actual = vi.importActual('react-bootstrap');
	return {
		...actual,
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
		forms: { changed: [] },
		icons: {},
	};
}

beforeAll(() => {
	window.SyncEngine = createSyncEngineMock();
});

afterEach(() => {
	cleanup();
	vi.clearAllMocks();
	delete window.SyncEngine;
});
