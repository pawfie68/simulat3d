document.querySelectorAll('.s3d-contact-form').forEach(form => {
  form.addEventListener('submit', async event => {
    event.preventDefault();
    if (!form.reportValidity()) return;
    const button = form.querySelector('button[type="submit"]');
    const status = form.querySelector('.s3d-form-status');
    button.disabled = true;
    status.dataset.state = 'pending';
    status.textContent = 'Wysyłanie wiadomości…';
    try {
      const response = await fetch(form.getAttribute('action'), {method:'POST', body:new FormData(form), headers:{Accept:'application/json'}, credentials:'same-origin'});
      const data = await response.json();
      if (!response.ok || data.ok !== true) throw new Error(data.message || 'Wysyłka nie powiodła się.');
      status.dataset.state = 'success'; status.textContent = data.message; form.reset();
    } catch (error) {
      status.dataset.state = 'error';
      status.textContent = error instanceof SyntaxError ? 'Nie udało się potwierdzić wysyłki. Spróbuj później lub napisz na office@simulat3d.eu.' : error.message;
    } finally { button.disabled = false; }
  });
});

