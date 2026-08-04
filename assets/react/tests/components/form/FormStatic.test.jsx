import React from 'react';
import { describe, it, expect, beforeEach, afterEach } from 'vitest';
import { render, screen, act } from '@testing-library/react';
import FormStatic from '@syncengine/components/form/FormStatic';
import { TagsContext } from '@syncengine/context/TagsContext';
import { ParentContext } from '@syncengine/context/ParentContext';
import { ContainerContext } from '@syncengine/context/ContainerContext';

function createParentRef() {
	return {
		current: {
			element: null,
			submit: null,
			submitListener: null,
			updateHtml: null,
		},
	};
}

function createFormHtml( extraContent = '' ) {
	return `
		<form class="syncengine-form">
			<div class="form-floating mb-3">
				<input type="text" class="form-control" id="name" name="name" value="Test Name" />
				<label for="name">Name</label>
			</div>
			<div class="form-floating mb-3">
				<input type="text" class="form-control" id="description" name="description" value="Test Desc" />
				<label for="description">Description</label>
			</div>
			${ extraContent }
			<button type="submit" class="btn btn-primary">Save</button>
		</form>
	`;
}

function createFormHtmlWithControllers( extraContent = '' ) {
	return `
		<form class="syncengine-form">
			<div class="form-floating mb-3">
				<input type="text" class="form-control" id="name" name="name" value="Test Name" />
				<label for="name">Name</label>
			</div>
			<div data-controller="react" data-type="config" data-args='{"fields":{}}'>
				<div class="react-field" id="react-field-1">
					<span class="react-value">React Value 1</span>
				</div>
			</div>
			<div data-controlled-by="react" data-type="storage">
				<div class="controlled-field" id="controlled-field-1">
					<span class="controlled-value">Controlled Value 1</span>
				</div>
			</div>
			${ extraContent }
			<button type="submit" class="btn btn-primary">Save</button>
		</form>
	`;
}

// Wrapper component that holds the container ref and applies all providers
function FormStaticWrapper( props ) {
	const containerRef = React.useRef( null );
	return (
		<TagsContext.Provider value={{}}>
			<ParentContext.Provider value={{ scope: [] }}>
				<ContainerContext.Provider value={containerRef}>
					<FormStatic { ...props } />
				</ContainerContext.Provider>
			</ParentContext.Provider>
		</TagsContext.Provider>
	);
}

describe('FormStatic', () => {
	beforeEach(() => {
		document.body.innerHTML = '';
	});

	afterEach(() => {
		document.body.innerHTML = '';
	});

	it('renders initial HTML content', () => {
		const parentRef = createParentRef();
		const html = createFormHtml();

		const { container } = render(
			<FormStaticWrapper
				type="storage"
				html={ html }
				parentRef={ parentRef }
			/>
		);

		expect(screen.getByLabelText('Name')).toHaveValue('Test Name');
		expect(screen.getByLabelText('Description')).toHaveValue('Test Desc');
	});

	it('registers the form with the app', () => {
		const parentRef = createParentRef();
		const html = createFormHtml();

		render(
			<FormStaticWrapper
				type="storage"
				html={ html }
				parentRef={ parentRef }
			/>
		);

		expect(parentRef.current.element).toBeTruthy();
		expect(parentRef.current.element.tagName).toBe('FORM');
		expect(parentRef.current.submit).toBeTruthy();
		expect(parentRef.current.updateHtml).toBeTruthy();
	});

	it('preserves data-controller elements during updateHtml call', () => {
		const parentRef = createParentRef();
		const initialHtml = createFormHtmlWithControllers();

		render(
			<FormStaticWrapper
				type="storage"
				html={ initialHtml }
				parentRef={ parentRef }
			/>
		);

		// Verify initial state
		const reactField = document.getElementById('react-field-1');
		const controlledField = document.getElementById('controlled-field-1');
		expect(reactField).toBeTruthy();
		expect(controlledField).toBeTruthy();
		expect(reactField.querySelector('.react-value').textContent).toBe('React Value 1');
		expect(controlledField.querySelector('.controlled-value').textContent).toBe('Controlled Value 1');

		// Create updated HTML with different content but same data-controller elements
		const updatedHtml = createFormHtmlWithControllers(
			'<div class="updated-field"><input name="updated" value="updated value" /></div>'
		);

		// Modify the updated HTML to have different values for data-controller elements
		// (simulating what the server might return)
		const updatedHtmlParsed = updatedHtml.replace(
			'<span class="react-value">React Value 1</span>',
			'<span class="react-value">React Value 2</span>'
		).replace(
			'<span class="controlled-value">Controlled Value 1</span>',
			'<span class="controlled-value">Controlled Value 2</span>'
		);

		// Call updateHtml directly (simulating EntityModal behavior)
		act(() => {
			parentRef.current.updateHtml( updatedHtmlParsed );
		});

		// Verify data-controller elements are preserved
		const preservedReactField = document.getElementById('react-field-1');
		const preservedControlledField = document.getElementById('controlled-field-1');

		expect(preservedReactField).toBeTruthy();
		expect(preservedControlledField).toBeTruthy();

		// Verify their content is NOT changed (still the original values)
		expect(preservedReactField.querySelector('.react-value').textContent).toBe('React Value 1');
		expect(preservedControlledField.querySelector('.controlled-value').textContent).toBe('Controlled Value 1');
	});

	it('preserves data-controller elements even when they are removed in new HTML', () => {
		const parentRef = createParentRef();
		const initialHtml = createFormHtmlWithControllers();

		render(
			<FormStaticWrapper
				type="storage"
				html={ initialHtml }
				parentRef={ parentRef }
			/>
		);

		// Verify initial state
		const reactField = document.getElementById('react-field-1');
		expect(reactField).toBeTruthy();

		// Create updated HTML WITHOUT the data-controller elements
		const updatedHtml = createFormHtml(
			'<div class="new-content" id="new-content"><span>New content without controllers</span></div>'
		);

		act(() => {
			parentRef.current.updateHtml( updatedHtml );
		});

		// Verify data-controller elements are still there
		const preservedReactField = document.getElementById('react-field-1');
		const preservedControlledField = document.getElementById('controlled-field-1');

		expect(preservedReactField).toBeTruthy();
		expect(preservedControlledField).toBeTruthy();
		expect(preservedReactField.querySelector('.react-value').textContent).toBe('React Value 1');
		expect(preservedControlledField.querySelector('.controlled-value').textContent).toBe('Controlled Value 1');
	});

	it('morphs parent elements while preserving data-controller children', () => {
		const parentRef = createParentRef();
		const htmlWithNestedControllers = `
			<form class="syncengine-form">
				<div class="outer-container" id="outer-container">
					<div data-controller="react" data-type="nested">
						<div class="inner-react-field">
							<span class="inner-value">Inner React Value</span>
						</div>
					</div>
				</div>
				<button type="submit">Save</button>
			</form>
		`;

		render(
			<FormStaticWrapper
				type="storage"
				html={ htmlWithNestedControllers }
				parentRef={ parentRef }
			/>
		);

		const innerField = document.querySelector('.inner-react-field');
		expect(innerField).toBeTruthy();
		expect(innerField.querySelector('.inner-value').textContent).toBe('Inner React Value');

		// Update with new HTML that doesn't have the nested controller
		const updatedHtml = `
			<form class="syncengine-form">
				<div class="new-outer" id="outer-container">
					<input type="text" name="new_field" value="new value" />
				</div>
				<button type="submit">Save new</button>
			</form>
		`;

		act(() => {
			parentRef.current.updateHtml( updatedHtml );
		});

		// Verify nested data-controller element is preserved
		const preservedInnerField = document.querySelector('.inner-react-field');
		expect(preservedInnerField).toBeTruthy();
		expect(preservedInnerField.querySelector('.inner-value').textContent).toBe('Inner React Value');

		// Verify parent element is morphed (class updated)
		const preservedOuterContainer = document.getElementById('outer-container');
		expect(preservedOuterContainer).toBeTruthy();
		expect(preservedOuterContainer.classList.contains('new-outer')).toBe(true);
		expect(preservedOuterContainer.classList.contains('outer-container')).toBe(false);

		// Verify button text is updated
		const button = document.querySelector('button[type="submit"]');
		expect(button.textContent).toBe('Save new');

		// Verify new input is added
		const newInput = document.querySelector('input[name="new_field"]');
		expect(newInput).toBeTruthy();
		expect(newInput.value).toBe('new value');
	});

	it('handles nested data-controller elements correctly', () => {
		const parentRef = createParentRef();
		const htmlWithNestedControllers = `
			<form class="syncengine-form">
				<div class="outer-container">
					<div data-controller="react" data-type="nested">
						<div class="inner-react-field">
							<span class="inner-value">Inner React Value</span>
						</div>
					</div>
				</div>
				<button type="submit">Save</button>
			</form>
		`;

		render(
			<FormStaticWrapper
				type="storage"
				html={ htmlWithNestedControllers }
				parentRef={ parentRef }
			/>
		);

		const innerField = document.querySelector('.inner-react-field');
		expect(innerField).toBeTruthy();
		expect(innerField.querySelector('.inner-value').textContent).toBe('Inner React Value');

		// Update with new HTML that doesn't have the nested controller
		const updatedHtml = `
			<form class="syncengine-form">
				<div class="new-outer">
					<input type="text" name="new_field" value="new value" />
				</div>
				<button type="submit">Save new</button>
			</form>
		`;

		act(() => {
			parentRef.current.updateHtml( updatedHtml );
		});

		// Verify nested data-controller element is preserved
		const preservedInnerField = document.querySelector('.inner-react-field');
		expect(preservedInnerField).toBeTruthy();
		expect(preservedInnerField.querySelector('.inner-value').textContent).toBe('Inner React Value');

		// Verify parent element is morphed (class updated)
		const preservedOuterContainer = document.querySelector('.new-outer');
		expect(preservedOuterContainer).toBeTruthy();

		// Verify button text is updated
		const button = document.querySelector('button[type="submit"]');
		expect(button.textContent).toBe('Save new');

		// Verify new input is added
		const newInput = document.querySelector('input[name="new_field"]');
		expect(newInput).toBeTruthy();
		expect(newInput.value).toBe('new value');
	});
});
