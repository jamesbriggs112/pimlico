/**
 * @file
 * Inline validation for site forms (Webforms, the newsletter).
 *
 * On submit, each invalid field gets a message under it, in the same markup
 * as Drupal's Inline Form Errors, and focus moves to the first one. Messages
 * update as people type and clear once a field is valid. The browser's own
 * pop-up bubbles are turned off; Drupal still validates on the server.
 */
((Drupal, once) => {
  const FIELDS =
    'input:not([type=hidden]):not([type=submit]), select, textarea';

  const labelText = (field) => {
    const label =
      (field.labels && field.labels[0]) ||
      field.closest('fieldset')?.querySelector('legend');
    return (label ? label.textContent : field.name)
      .replace('*', '')
      .trim()
      .toLowerCase()
      .replace(/^your /, '');
  };

  const messageFor = (field) => {
    const v = field.validity;
    const label = labelText(field);
    if (v.valueMissing) {
      if (field.type === 'checkbox' || field.type === 'radio') {
        return Drupal.t('Choose an option for @label', { '@label': label });
      }
      if (field.tagName === 'SELECT') {
        return Drupal.t('Choose your @label', { '@label': label });
      }
      return Drupal.t('Enter your @label', { '@label': label });
    }
    if (v.typeMismatch && field.type === 'email') {
      return Drupal.t('Enter an email address like name@example.com');
    }
    if (v.typeMismatch && field.type === 'url') {
      return Drupal.t('Enter a web address starting with https://');
    }
    if (v.tooLong) {
      return Drupal.t('@label must be @max characters or fewer', {
        '@label': label.charAt(0).toUpperCase() + label.slice(1),
        '@max': field.maxLength,
      });
    }
    return field.validationMessage;
  };

  const wrapperFor = (field) =>
    field.closest('.js-form-item, .form-item') || field.parentElement;

  const clear = (field) => {
    const wrapper = wrapperFor(field);
    const error = wrapper.querySelector(':scope > .form-item--error-message');
    if (error) {
      error.remove();
    }
    wrapper.classList.remove('form-item--error');
    field.classList.remove('error');
    field.removeAttribute('aria-invalid');
    const ids = (field.getAttribute('aria-describedby') || '')
      .split(' ')
      .filter((id) => id && id !== `${field.id}--error`);
    if (ids.length) {
      field.setAttribute('aria-describedby', ids.join(' '));
    } else {
      field.removeAttribute('aria-describedby');
    }
  };

  const show = (field, text) => {
    clear(field);
    const wrapper = wrapperFor(field);
    const error = document.createElement('div');
    error.className = 'form-item--error-message';
    // Shown in the space under the field, so nothing moves (form.scss).
    error.dataset.inlineError = '';
    error.id = `${field.id}--error`;
    error.textContent = text;
    // Under the control, before any description.
    const description = wrapper.querySelector(':scope > .description');
    wrapper.insertBefore(error, description);
    wrapper.classList.add('form-item--error');
    field.classList.add('error');
    field.setAttribute('aria-invalid', 'true');
    field.setAttribute(
      'aria-describedby',
      `${field.getAttribute('aria-describedby') || ''} ${error.id}`.trim()
    );
  };

  const check = (field) => {
    if (!field.willValidate) {
      return true;
    }
    // validity.valid, not checkValidity(): that fires `invalid` events,
    // which other scripts (Webform's) answer with messages of their own.
    if (field.validity.valid) {
      clear(field);
      return true;
    }
    show(field, messageFor(field));
    return false;
  };

  /**
   * Shows a message under each invalid field; true when the form is valid.
   */
  const validate = (form) => {
    const invalid = [...form.querySelectorAll(FIELDS)].filter(
      (field) => !check(field)
    );
    if (invalid.length) {
      invalid[0].focus();
    }
    return invalid.length === 0;
  };

  // Webforms with AJAX on (Contact) submit on the button's mousedown,
  // before any submit event. jQuery Form aborts when beforeSerialize
  // returns false, so validate there too. Patched once, when Drupal's AJAX
  // is on the page; this script doesn't load it itself.
  const patchAjax = () => {
    if (!Drupal.Ajax || Drupal.Ajax.prototype.pimlicoValidates) {
      return;
    }
    const { beforeSerialize } = Drupal.Ajax.prototype;
    Drupal.Ajax.prototype.beforeSerialize = function pimlicoBeforeSerialize(
      element,
      options
    ) {
      const form = this.$form && this.$form[0];
      if (form && form.dataset.pimlicoValidate && !validate(form)) {
        return false;
      }
      return beforeSerialize.call(this, element, options);
    };
    Drupal.Ajax.prototype.pimlicoValidates = true;
  };

  Drupal.behaviors.pimlicoFormValidation = {
    attach(context) {
      patchAjax();
      once(
        'pimlico-form-validation',
        'form.webform-submission-form',
        context
      ).forEach((form) => {
        form.noValidate = true;
        form.dataset.pimlicoValidate = 'true';

        form.addEventListener(
          'submit',
          (event) => {
            if (!validate(form)) {
              event.preventDefault();
              event.stopImmediatePropagation();
            }
          },
          true
        );

        form.querySelectorAll(FIELDS).forEach((field) => {
          // Check once someone leaves a field they've typed in, then keep the
          // message current as they correct it.
          field.addEventListener('blur', (event) => {
            // Leave it to the submit check when focus is moving to the
            // submit button: a message appearing now would push the button
            // down mid-click.
            const next = event.relatedTarget;
            if (next && next.form === form && next.type === 'submit') {
              return;
            }
            if (field.value !== '' || field.getAttribute('aria-invalid')) {
              check(field);
            }
          });
          field.addEventListener('input', () => {
            if (field.getAttribute('aria-invalid')) {
              check(field);
            }
          });
          field.addEventListener('change', () => {
            if (field.getAttribute('aria-invalid')) {
              check(field);
            }
          });
        });
      });
    },
  };
})(Drupal, once);
