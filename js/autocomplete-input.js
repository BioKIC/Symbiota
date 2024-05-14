//This File must be imported as a module
const template = document.createElement("template");
template.innerHTML = `<span style="display: inline-block; font-size 1em; position: relative; width:300px">
	<input id="dropdown-input" style="width:inherit;"></input>
	<div id="suggestions" style="max-height:20rem; overflow-y:scroll; width: inherit; position: absolute; background-color:#fff;cursor:pointer !important; display: none; border: 1px solid gray; box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);"></div>
</span>`

class AutocompleteInput extends HTMLElement {

	constructor() {
		super();
		this.completeUrl= this.getAttribute("completeUrl");

		//Represents what pattern in completeUrl will be replaced for the value
		this.url_delimter = this.getAttribute("url_delimiter")? 
			this.getAttribute("url_delimiter"):
			'??';

		//Delimter to single when multiple autocomple requests are needed
		this.input_delimter = this.getAttribute("input_delimiter")? 
			this.getAttribute("input_delimiter"):
			',';

		this.multi = this.getAttribute("multi")? 
			this.getAttribute("multi"):
			true;

		this.name = this.getAttribute("name")? 
			this.getAttribute("name"):
			"autocomplete-input";

		this.response_type = this.getAttribute("response_type")? this.getAttribute("response_type"):'html';

		this.json_label = this.getAttribute("json_label")? this.getAttribute("json_label"):'label';
		this.json_value = this.getAttribute("json_value")? this.getAttribute("json_value"):'value';

		this.shadow = this.attachShadow({ mode: "open" });
		this.shadowRoot.appendChild(template.content.cloneNode(true));
		this.selected_index = 0;
		this.highlight_color = "#E9E9ED";
	}

	getInputElement() {
		if(!this._inputEl) {
			this._inputEl = this.shadowRoot.querySelector("#dropdown-input");
		}

		return this._inputEl;
	}

	_swapSuggestionList(newInnerHmtl) {
		const suggestions = this.shadowRoot.querySelector("#suggestions");

		if(suggestions) {
			suggestions.style.display ='block';
			suggestions.scrollTop = 0;
			suggestions.innerHTML = newInnerHmtl;
			this._changeSelection(0);

			for(let i = 0; i < suggestions.children.length; i++) {
				suggestions.children[i].addEventListener('mouseenter', () => {
					this._changeSelection(i);
				});
			}
		}
	}

	getSelection() {
		const suggestions = this.shadowRoot.querySelector("#suggestions");
		if(!suggestions) return;

		const options = suggestions.children;
		if(options.length === 0) return;

		return options[this.selected_index];
	}

	_changeSelection(new_index) {
		const suggestions = this.shadowRoot.querySelector("#suggestions");
		if(!suggestions) return;

		const options = suggestions.children;
		if(options.length === 0) {
			suggestions.style.display = 'none';
			return;
		}

		if(!this.selected_index || this.selected_index >= options.length) this.selected_index = 0;

		options[this.selected_index].style['background-color'] = null;

		if(options.length - 1 < new_index) new_index = 0;
		if(new_index < 0) new_index = options.length - 1;

		this.selected_index = new_index;

		options[this.selected_index].style['background-color'] = this.highlight_color;
	}

	toggleMenu(val) { 
		this.menu.style.display = val && this.menu.children.length > 0? 
			'block': 
			'none';
	}

	findContainingForm() {
		// Can only be in a form in the same "scope", ShadowRoot or Document
		const root = this.getRootNode();
		const forms = Array.from(root.querySelectorAll('form'));
		// We can only be in one <form>, so the first
		// one to contain us is the correct one.
		return forms.find((form) => form.contains(this)) || null;
	}

	_handleFormData({formData}) {
		if(this._inputEl.disabled) return;

		formData.append(this.name, this._inputEl.value);
	}

	_select_option() {
		const selected_option = this.getSelection();
		if(selected_option) {
			let values = this._inputEl.value.split(this.input_delimter);
			let incoming_value = selected_option.getAttribute("data-value")? 
				selected_option.getAttribute("data-value"):
				selected_option.innerHTML

			if(values.length > 1 && this.multi) {
				if(!Array.isArray(this.value)) this.value = [this.value];

				values[values.length - 1] = selected_option.innerHTML
				this._inputEl.value = values.join(this.input_delimter);
				this.value.push(incoming_value);
			} else {
				this.value = selected_option.getAttribute("data-value")? 
					selected_option.getAttribute("data-value"):
					selected_option.innerHTML
				this._inputEl.value = selected_option.innerHTML;
			}
		}
	}

	connectedCallback() {
		const el = this.getInputElement();
		this.menu = this.shadowRoot.querySelector("#suggestions");

		this.menu.addEventListener('mousedown', () => {
			this._select_option();
			this.selected_index = 0;
		});

		//Setup to allow for parent form to access this input 
		this._form = this.findContainingForm();
		if (this._form) {
			this._form.addEventListener('formdata', e => this._handleFormData(e));
		}

		this.addEventListener("change", e => { console.log(e) })
		this.addEventListener("onchange", e => { console.log(e) })

		el.addEventListener('input', e => {
			this._inputEl = e.target;

			const values = e.target.value.split(this.input_delimter);
			let value = values.length > 1? values[values.length - 1]: values[0];

			this.onSearch(value.trim()).then(res => {
				this._swapSuggestionList(res);
				this.toggleMenu(true);
			});
		});

		el.addEventListener('blur', () => this.toggleMenu(false));

		el.addEventListener('focus', () => this.toggleMenu(true));

		el.addEventListener('keydown', e => {
			let scrollMenu= () => {
				const item_height = this.getSelection().clientHeight
				const selection_height = item_height * (this.selected_index + 1);
				const bottom_of_menu = this.menu.scrollTop + this.menu.clientHeight;

				if(selection_height > bottom_of_menu) { 
					this.menu.scrollTop += selection_height - bottom_of_menu;
				} else if(selection_height <= this.menu.scrollTop + item_height) {
					this.menu.scrollTop = selection_height - item_height;
				}
			}

			switch(e.key) {
				case "ArrowUp":
					this._changeSelection(this.selected_index - 1);
					scrollMenu();
					break;
				case "ArrowDown":
					this._changeSelection(this.selected_index + 1);
					scrollMenu();
					break;
				case "Enter":
					this._select_option();
					this.toggleMenu(false);
					break;
			}
		})
	}

	async resolveJson(response) {
		try { 
			let innerHtml ='';
			for(let option of await response.json()) {
				innerHtml += `<div data-value="${option[this.json_value]}" style="padding: 0.2rem">${option[this.json_label]}</div>`
			}
			return innerHtml;
		} catch(e) { 
			return "Error" 
		}
	}
	async resolveText() {
		try { 
			return await response.text() 
		} catch(e) { 
			return "Error" 
		}
	}

	async onSearch(value) {
		if(!this.completeUrl) {
			console.warn("completeUrl attribute is not set for autocomplete-input of id: " + this.id)
			return "";
		}

		let response = await fetch(this.completeUrl.replace(this.url_delimter, value), {
			method: "POST",
			mode: "cors",
		});

		switch (this.response_type) {
			case "json": 
				return await this.resolveJson(response);
			default: 
				return await this.resolveText(response)
		}
	}
}
customElements.define('autocomplete-input', AutocompleteInput);
