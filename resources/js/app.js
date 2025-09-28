import "./bootstrap";

import Alpine from "alpinejs";
import Sortable from "sortablejs";

// simple Alpine directive to initialize Sortable on an element
Alpine.directive('sortable', (el, {expression}, {evaluate}) => {
	// read options from x-sortable="{ ... }" if provided
	let options = {};
	try {
		options = expression ? evaluate(expression) : {};
	} catch (e) {
		options = {};
	}

	// initialize Sortable
	Sortable.create(el, Object.assign({}, options));
});

window.Alpine = Alpine;

Alpine.start();
