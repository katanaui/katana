class KatanaTooltipComponent extends HTMLElement {
    connectedCallback() {
        // Make sure content is preserved
        const target = this.firstElementChild;

        // Example: add tooltip behavior or an attribute
        if (target && this.hasAttribute('content')) {
            target.setAttribute('title', this.getAttribute('content'));
        }

        console.log('made it');

        // You can also add classes, wrap with elements, etc., here
    }
}
customElements.define('katana-tooltip', KatanaTooltipComponent);