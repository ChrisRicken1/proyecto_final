document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm || '¿Continuar?')) event.preventDefault();
  });
});
document.querySelectorAll('input[type="date"]').forEach(input => {
  input.addEventListener('change', () => input.classList.toggle('has-value', !!input.value));
});
