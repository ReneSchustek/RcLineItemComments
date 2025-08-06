import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

export default class LineItemCommentsPlugin extends Plugin {
    static options = {
        commentSelector: '.line-item-comment',
        checkoutButtonSelector: '.btn-buy',
        validationContainerSelector: '#line-item-comments-validation',
        errorListSelector: '#comment-validation-errors',
        saveCommentUrl: '',
        validateCommentsUrl: '',
        autoSaveDelay: 1000
    };

    init() {
        this.httpClient = new HttpClient();
        this.commentTextareas = document.querySelectorAll(this.options.commentSelector);
        this.checkoutButton = document.querySelector(this.options.checkoutButtonSelector);
        this.validationContainer = document.querySelector(this.options.validationContainerSelector);
        this.errorList = document.querySelector(this.options.errorListSelector);

        this._registerEvents();
    }

    _registerEvents() {
        // Auto-save für Kommentare
        this.commentTextareas.forEach(textarea => {
            let timeout;
            textarea.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    this._saveComment(
                        textarea.dataset.lineItemId,
                        textarea.value
                    );
                }, this.options.autoSaveDelay);
            });

            // Echtzeit-Validierung
            textarea.addEventListener('blur', () => {
                this._validateSingleComment(textarea);
            });
        });

        // Checkout-Validierung
        if (this.checkoutButton) {
            this.checkoutButton.addEventListener('click', (e) => {
                if (!this._validateAllComments()) {
                    e.preventDefault();
                    this._showValidationErrors();
                    return false;
                }
            });
        }
    }

    _saveComment(lineItemId, comment) {
        if (!this.options.saveCommentUrl) return;

        const formData = new FormData();
        formData.append('lineItemId', lineItemId);
        formData.append('comment', comment);

        this.httpClient.post(this.options.saveCommentUrl, formData, (response) => {
            try {
                const data = JSON.parse(response);
                if (!data.success) {
                    console.error('Fehler beim Speichern des Kommentars:', data.message);
                }
            } catch (e) {
                console.error('Invalid response format');
            }
        });
    }

    _validateSingleComment(textarea) {
        const comment = textarea.value.trim();
        const lineItemId = textarea.dataset.lineItemId;
        const isValid = comment.length > 0;

        if (!isValid) {
            textarea.classList.add('is-invalid');
            const errorDiv = document.getElementById(`comment-error-${lineItemId}`);
            if (errorDiv) {
                errorDiv.style.display = 'block';
            }
        } else {
            textarea.classList.remove('is-invalid');
            const errorDiv = document.getElementById(`comment-error-${lineItemId}`);
            if (errorDiv) {
                errorDiv.style.display = 'none';
            }
        }

        return isValid;
    }

    _validateAllComments() {
        let isValid = true;
        const errors = [];

        this.commentTextareas.forEach(textarea => {
            const singleValid = this._validateSingleComment(textarea);
            if (!singleValid) {
                isValid = false;
                const itemName = this._getItemName(textarea);
                errors.push(`Artikel "${itemName}" benötigt eine Bemerkung`);
            }
        });

        this.validationErrors = errors;
        return isValid;
    }

    _getItemName(textarea) {
        const cartItem = textarea.closest('.cart-item');
        if (cartItem) {
            const labelElement = cartItem.querySelector('.line-item-label');
            return labelElement ? labelElement.textContent.trim() : 'Unbekannter Artikel';
        }
        return 'Unbekannter Artikel';
    }

    _showValidationErrors() {
        if (!this.validationContainer || !this.errorList) return;

        this.errorList.innerHTML = '';

        this.validationErrors.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            this.errorList.appendChild(li);
        });

        this.validationContainer.classList.remove('d-none');
        this.validationContainer.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }

    _hideValidationErrors() {
        if (this.validationContainer) {
            this.validationContainer.classList.add('d-none');
        }
    }

    // Public API für externe Nutzung
    validateComments() {
        return this._validateAllComments();
    }

    saveAllComments() {
        this.commentTextareas.forEach(textarea => {
            if (textarea.value.trim()) {
                this._saveComment(textarea.dataset.lineItemId, textarea.value);
            }
        });
    }
}