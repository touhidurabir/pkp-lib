/**
 * @file cypress/tests/integration/EditorialMasthead.cy.js
 *
 * Copyright (c) 2026 Simon Fraser University
 * Copyright (c) 2026 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 */

describe('Editorial Masthead', function() {
	it('Loads editorial masthead route', function() {
		cy.request('index.php/publicknowledge/en/about/editorialMasthead').then((response) => {
			expect(response.status).to.eq(200);
		});
	});

	it('Shows editorial masthead page content', function() {
		cy.visit('index.php/publicknowledge/en/about/editorialMasthead');
		cy.get('h1').should('be.visible').and('not.be.empty');
		cy.url().should('include', '/about/editorialMasthead');
	});
});
